<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\NotificationHub;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly ReportExport $export) {}

    public function handle(NotificationHub $notifications): void
    {
        $this->export->update(['state' => 'running', 'error_message' => null]);
        try {
            $snapshot = $this->export->snapshot;
            $path = "report-exports/{$this->export->id}.{$this->export->format}";
            $content = $this->export->format === 'json'
                ? json_encode(['provenance' => $this->export->filter_provenance, 'metrics' => $snapshot->metrics, 'last_updated_at' => $snapshot->generated_at?->toIso8601String()], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
                : $this->csv($snapshot->metrics, $this->export->filter_provenance, $snapshot->generated_at?->toIso8601String());
            if (! Storage::disk($this->export->disk)->put($path, $content)) {
                throw new \RuntimeException('Gagal menyimpan berkas ekspor.');
            }
            $this->export->update(['state' => 'completed', 'path' => $path, 'checksum' => hash('sha256', $content), 'expires_at' => now()->addHours(24)]);
            activity('report_export')->performedOn($this->export)->causedBy($this->export->requester)->withProperties(['snapshot_id' => $snapshot->id, 'format' => $this->export->format, 'expires_at' => $this->export->expires_at?->toIso8601String()])->log('report_export_completed');
            $notifications->send($this->export->requester, 'report_completion', 'Laporan selesai', 'Berkas laporan telah selesai dibuat dan siap diunduh.', '/app/analytics', ['export_id' => $this->export->id], "report:{$this->export->id}:completed");
        } catch (\Throwable $error) {
            $this->export->update(['state' => 'failed', 'error_message' => str($error->getMessage())->limit(1000)]);
            throw $error;
        }
    }

    private function csv(array $metrics, array $provenance, ?string $lastUpdated): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['formula_version', $metrics['methodology_version'] ?? null]);
        fputcsv($stream, ['last_updated_at', $lastUpdated]);
        fputcsv($stream, ['filter_provenance', json_encode($provenance, JSON_THROW_ON_ERROR)]);
        fputcsv($stream, []);
        fputcsv($stream, ['level', 'code', 'label', 'n', 'missing', 'score', 'interpretation', 'suppressed']);
        foreach (['categories' => 'category', 'indicators' => 'indicator', 'items' => 'item'] as $key => $level) {
            foreach ($metrics[$key] ?? [] as $row) {
                fputcsv($stream, [$level, $row['code'] ?? null, $row['name'] ?? $row['text'] ?? null, $row['n'] ?? null, $row['missing'] ?? null, $row['normalized_score'] ?? null, $row['interpretation'] ?? null, ($row['suppressed'] ?? false) ? 'true' : 'false']);
            }
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }
}
