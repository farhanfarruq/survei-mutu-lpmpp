<?php

namespace App\Http\Controllers\Api;

use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportExport;
use App\Jobs\RunSurveyAnalysis;
use App\Models\AggregateSnapshot;
use App\Models\AnalysisRun;
use App\Models\ReportDownloadTicket;
use App\Models\ReportExport;
use App\Models\Survey;
use App\Services\DeterministicStatistics;
use App\Services\LeadershipDashboard;
use App\Services\OrganizationalScope;
use App\Services\SurveyAnalytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsReportingController extends Controller
{
    public function run(Request $request, Survey $survey, OrganizationalScope $scope, SurveyAnalytics $analytics): JsonResponse
    {
        $this->assertScope($request, $survey, $scope);
        if (! in_array($survey->state, [SurveyState::Closed, SurveyState::Archived], true)) {
            throw new DomainRuleViolation('survey_not_closed', 'Analisis hanya dapat dijalankan untuk survei yang sudah ditutup.', 409);
        }
        if (! $survey->responses()->where('state', 'submitted')->exists()) {
            throw new DomainRuleViolation('analysis_not_eligible', 'Belum ada respons submitted untuk dianalisis.', 409);
        }

        $inputHash = $analytics->inputHash($survey);
        $cached = AnalysisRun::query()->with('snapshot')->where('survey_id', $survey->id)->where('input_hash', $inputHash)->where('formula_version', DeterministicStatistics::VERSION)->where('state', 'completed')->latest()->first();
        if ($cached?->snapshot) {
            return response()->json(['data' => $this->runData($cached), 'meta' => ['cached' => true]]);
        }

        $run = AnalysisRun::create(['survey_id' => $survey->id, 'requested_by' => $request->user()->id, 'state' => 'queued', 'input_hash' => $inputHash, 'formula_version' => DeterministicStatistics::VERSION, 'parameters' => ['response_state' => 'submitted']]);
        activity('analytics')->performedOn($run)->causedBy($request->user())->withProperties(['survey_id' => $survey->id, 'input_hash' => $inputHash])->log('analysis_requested');
        RunSurveyAnalysis::dispatch($run);
        $run->refresh()->load('snapshot');

        return response()->json(['data' => $this->runData($run), 'meta' => ['cached' => false]], 202);
    }

    public function showRun(Request $request, AnalysisRun $analysisRun, OrganizationalScope $scope): JsonResponse
    {
        $this->assertScope($request, $analysisRun->survey, $scope);

        return response()->json(['data' => $this->runData($analysisRun->load('snapshot'))]);
    }

    public function release(Request $request, AnalysisRun $analysisRun, OrganizationalScope $scope): JsonResponse
    {
        $this->assertScope($request, $analysisRun->survey, $scope);
        if ($analysisRun->requested_by === $request->user()->id) {
            throw new DomainRuleViolation('separation_of_duties', 'Perilis harus berbeda dari peminta analisis.', 409);
        }
        $snapshot = $analysisRun->snapshot;
        if (! $snapshot || $analysisRun->state !== 'completed') {
            throw new DomainRuleViolation('analysis_not_completed', 'Analisis belum selesai.', 409);
        }
        if ($snapshot->suppressed) {
            throw new DomainRuleViolation('small_sample_suppressed', 'Snapshot di bawah ambang pelaporan dan tidak dapat dirilis.', 409);
        }
        $snapshot->update(['state' => 'released', 'released_at' => now(), 'released_by' => $request->user()->id]);
        activity('analytics')->performedOn($snapshot)->causedBy($request->user())->withProperties(['analysis_run_id' => $analysisRun->id, 'checksum' => $snapshot->checksum])->log('aggregate_snapshot_released');

        return response()->json(['data' => $this->snapshotData($snapshot)]);
    }

    public function snapshot(Request $request, AggregateSnapshot $aggregateSnapshot, OrganizationalScope $scope): JsonResponse
    {
        $this->assertScope($request, $aggregateSnapshot->survey, $scope);
        if (! $request->user()->can('analysis.read') && $aggregateSnapshot->state !== 'released') {
            throw new DomainRuleViolation('not_found', 'Snapshot tidak tersedia.', 404);
        }

        return response()->json(['data' => $this->snapshotData($aggregateSnapshot)]);
    }

    public function leadership(Request $request, OrganizationalScope $scope, LeadershipDashboard $dashboard): JsonResponse
    {
        $validated = $request->validate(['unit_id' => ['nullable', 'uuid'], 'period_id' => ['nullable', 'uuid'], 'survey_id' => ['nullable', 'uuid'], 'group_id' => ['nullable', 'uuid'], 'drilldown' => ['nullable', 'in:item']]);
        if (($validated['unit_id'] ?? null) && ! $scope->accessibleUnitIds($request->user())->containsStrict($validated['unit_id'])) {
            throw new DomainRuleViolation('forbidden', 'Unit berada di luar cakupan organisasi Anda.', 403);
        }

        return response()->json(['data' => $dashboard->data($request->user(), $validated)]);
    }

    public function createExport(Request $request, OrganizationalScope $scope): JsonResponse
    {
        $validated = $request->validate(['aggregate_snapshot_id' => ['required', 'uuid', 'exists:aggregate_snapshots,id'], 'format' => ['required', 'in:csv,json'], 'filters' => ['nullable', 'array']]);
        $idempotencyKey = (string) $request->header('Idempotency-Key');
        if (! preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $idempotencyKey)) {
            throw new DomainRuleViolation('idempotency_key_required', 'Idempotency-Key 16–128 karakter diperlukan.', 428);
        }
        $snapshot = AggregateSnapshot::findOrFail($validated['aggregate_snapshot_id']);
        $this->assertScope($request, $snapshot->survey, $scope);
        if ($snapshot->state !== 'released') {
            throw new DomainRuleViolation('snapshot_not_released', 'Hanya snapshot released yang dapat diekspor.', 409);
        }
        $keyHash = hash('sha256', $idempotencyKey);
        $existing = ReportExport::where('requested_by', $request->user()->id)->where('idempotency_key_hash', $keyHash)->first();
        if ($existing) {
            return response()->json(['data' => $this->exportData($existing), 'meta' => ['idempotent_replay' => true]]);
        }

        $filters = $validated['filters'] ?? [];
        $export = ReportExport::create(['aggregate_snapshot_id' => $snapshot->id, 'requested_by' => $request->user()->id, 'format' => $validated['format'], 'filters' => $filters, 'filter_provenance' => ['requested_filters' => $filters, 'snapshot_provenance' => $snapshot->filter_provenance, 'suppression_applied' => true], 'idempotency_key_hash' => $keyHash]);
        activity('report_export')->performedOn($export)->causedBy($request->user())->withProperties(['snapshot_id' => $snapshot->id, 'format' => $export->format])->log('report_export_requested');
        GenerateReportExport::dispatch($export);
        $export->refresh();

        return response()->json(['data' => $this->exportData($export), 'meta' => ['idempotent_replay' => false]], 202);
    }

    public function showExport(Request $request, ReportExport $reportExport, OrganizationalScope $scope): JsonResponse
    {
        $this->assertExportAccess($request, $reportExport, $scope);
        $this->expire($reportExport);

        return response()->json(['data' => $this->exportData($reportExport)]);
    }

    public function ticket(Request $request, ReportExport $reportExport, OrganizationalScope $scope): JsonResponse
    {
        $this->assertExportAccess($request, $reportExport, $scope);
        $this->expire($reportExport);
        if ($reportExport->state !== 'completed' || $reportExport->revoked_at) {
            throw new DomainRuleViolation('export_not_available', 'Ekspor belum siap, kedaluwarsa, atau telah dicabut.', 409);
        }
        $token = Str::random(64);
        $ticketExpiry = now()->addMinutes(10);
        if ($reportExport->expires_at->lt($ticketExpiry)) {
            $ticketExpiry = $reportExport->expires_at->copy();
        }
        ReportDownloadTicket::create(['report_export_id' => $reportExport->id, 'requested_by' => $request->user()->id, 'token_hash' => hash('sha256', $token), 'expires_at' => $ticketExpiry]);

        return response()->json(['data' => ['download_token' => $token, 'expires_at' => $ticketExpiry->toIso8601String()]], 201);
    }

    public function download(Request $request, string $token): StreamedResponse
    {
        $ticket = DB::transaction(function () use ($request, $token) {
            $ticket = ReportDownloadTicket::with('export')->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $ticket || $ticket->requested_by !== $request->user()->id || $ticket->used_at || $ticket->expires_at->isPast()) {
                throw new DomainRuleViolation('download_ticket_invalid', 'Tiket unduh tidak valid atau sudah digunakan.', 410);
            }
            $export = $ticket->export;
            if ($export->state !== 'completed' || $export->expires_at?->isPast() || $export->revoked_at || ! $export->path || ! Storage::disk($export->disk)->exists($export->path)) {
                throw new DomainRuleViolation('export_not_available', 'Berkas ekspor tidak tersedia.', 410);
            }
            $ticket->update(['used_at' => now()]);
            $export->update(['downloaded_at' => now()]);
            activity('report_export')->performedOn($export)->causedBy($request->user())->withProperties(['ticket_id' => $ticket->id])->log('report_export_downloaded');

            return $ticket;
        });

        return Storage::disk($ticket->export->disk)->download($ticket->export->path, "report-{$ticket->export->id}.{$ticket->export->format}");
    }

    private function assertScope(Request $request, Survey $survey, OrganizationalScope $scope): void
    {
        if (! $survey->owner_unit_id || ! $scope->allows($request->user(), $survey->owner_unit_id)) {
            throw new DomainRuleViolation('forbidden', 'Survei berada di luar cakupan organisasi Anda.', 403);
        }
    }

    private function assertExportAccess(Request $request, ReportExport $export, OrganizationalScope $scope): void
    {
        $this->assertScope($request, $export->snapshot->survey, $scope);
        if ($export->requested_by !== $request->user()->id && ! $request->user()->can('report.approve')) {
            throw new DomainRuleViolation('forbidden', 'Ekspor ini bukan milik Anda.', 403);
        }
    }

    private function expire(ReportExport $export): void
    {
        if ($export->state === 'completed' && $export->expires_at?->isPast()) {
            $export->update(['state' => 'expired']);
        }
    }

    private function runData(AnalysisRun $run): array
    {
        return ['id' => $run->id, 'survey_id' => $run->survey_id, 'state' => $run->state, 'input_hash' => $run->input_hash, 'formula_version' => $run->formula_version, 'snapshot_id' => $run->snapshot?->id, 'started_at' => $run->started_at?->toIso8601String(), 'completed_at' => $run->completed_at?->toIso8601String(), 'error' => $run->state === 'failed' ? $run->error_message : null];
    }

    private function snapshotData(AggregateSnapshot $snapshot): array
    {
        return ['id' => $snapshot->id, 'survey_id' => $snapshot->survey_id, 'state' => $snapshot->state, 'metrics' => $snapshot->metrics, 'filter_provenance' => $snapshot->filter_provenance, 'limitations' => $snapshot->limitations, 'response_count' => $snapshot->response_count, 'eligible_count' => $snapshot->eligible_count, 'reporting_threshold' => $snapshot->reporting_threshold, 'suppressed' => $snapshot->suppressed, 'checksum' => $snapshot->checksum, 'last_updated_at' => $snapshot->generated_at?->toIso8601String(), 'released_at' => $snapshot->released_at?->toIso8601String()];
    }

    private function exportData(ReportExport $export): array
    {
        return ['id' => $export->id, 'snapshot_id' => $export->aggregate_snapshot_id, 'state' => $export->state, 'format' => $export->format, 'filter_provenance' => $export->filter_provenance, 'checksum' => $export->checksum, 'expires_at' => $export->expires_at?->toIso8601String(), 'downloaded_at' => $export->downloaded_at?->toIso8601String(), 'revoked_at' => $export->revoked_at?->toIso8601String(), 'error' => $export->state === 'failed' ? $export->error_message : null];
    }
}
