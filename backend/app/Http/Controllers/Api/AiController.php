<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AiProvider;
use App\Exceptions\DomainRuleViolation;
use App\Http\Controllers\Controller;
use App\Models\AiJob;
use App\Models\AiPromptTemplate;
use App\Models\AiProviderConfig;
use App\Models\AiResult;
use App\Models\AnalysisRun;
use App\Models\User;
use App\Services\AiOrchestrator;
use App\Services\AiSafety;
use App\Services\OrganizationalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiController extends Controller
{
    public function configs(): JsonResponse
    {
        return response()->json(['data' => AiProviderConfig::latest()->get()->map(fn ($config) => $this->configData($config))]);
    }

    public function saveConfig(Request $request, AiOrchestrator $orchestrator): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'max:40'], 'model' => ['required', 'string', 'max:120'], 'base_url' => ['required', 'url', 'max:500'], 'api_key' => ['nullable', 'string', 'min:8', 'max:1000'], 'enabled' => ['required', 'boolean'],
            'max_input_tokens' => ['required', 'integer', 'min:128', 'max:100000'], 'max_output_tokens' => ['required', 'integer', 'min:64', 'max:20000'], 'max_cost_micros' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'input_cost_micros_per_1k' => ['required', 'integer', 'min:0'], 'output_cost_micros_per_1k' => ['required', 'integer', 'min:0'], 'timeout_seconds' => ['required', 'integer', 'min:1', 'max:120'], 'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:120'],
        ]);
        if (! $orchestrator->baseUrlAllowed($validated['provider'], $validated['base_url'])) {
            throw new DomainRuleViolation('ai_governance_blocked', 'Base URL provider tidak termasuk allowlist.', 422);
        }
        $config = AiProviderConfig::firstOrNew(['provider' => $validated['provider'], 'model' => $validated['model']]);
        $config->fill(collect($validated)->except('api_key')->all());
        if (filled($validated['api_key'] ?? null)) {
            $config->secret_ciphertext = $validated['api_key'];
        }
        if (! $config->exists) {
            $config->created_by = $request->user()->id;
        }
        if ($validated['enabled'] && ! $config->secret_ciphertext) {
            throw new DomainRuleViolation('ai_governance_blocked', 'Secret provider harus tersimpan sebelum konfigurasi diaktifkan.', 422);
        }
        $config->save();
        activity('ai_config')->performedOn($config)->causedBy($request->user())->withProperties(['provider' => $config->provider, 'model' => $config->model, 'enabled' => $config->enabled])->log('ai_config_saved');

        return response()->json(['data' => $this->configData($config)], $config->wasRecentlyCreated ? 201 : 200);
    }

    public function testConnection(Request $request, AiProviderConfig $aiProviderConfig, AiProvider $provider, AiOrchestrator $orchestrator): JsonResponse
    {
        if (! $orchestrator->baseUrlAllowed($aiProviderConfig->provider, $aiProviderConfig->base_url) || ! $aiProviderConfig->secret_ciphertext) {
            throw new DomainRuleViolation('ai_governance_blocked', 'Konfigurasi provider belum lengkap.', 422);
        }
        try {
            $result = $provider->testConnection($aiProviderConfig);
            $aiProviderConfig->update(['connection_status' => $result['ok'] ? 'connected' : 'failed', 'last_tested_at' => now()]);
        } catch (\Throwable) {
            $aiProviderConfig->update(['connection_status' => 'failed', 'last_tested_at' => now()]);
        }
        activity('ai_config')->performedOn($aiProviderConfig)->causedBy($request->user())->withProperties(['status' => $aiProviderConfig->connection_status])->log('ai_connection_tested');

        return response()->json(['data' => ['status' => $aiProviderConfig->connection_status, 'tested_at' => $aiProviderConfig->last_tested_at?->toIso8601String()]]);
    }

    public function prompts(): JsonResponse
    {
        return response()->json(['data' => AiPromptTemplate::latest('version')->get()->map(fn ($template) => $this->promptData($template))]);
    }

    public function savePrompt(Request $request): JsonResponse
    {
        $validated = $request->validate(['use_case' => ['required', 'in:comprehensive_insight'], 'system_prompt' => ['required', 'string', 'max:12000'], 'active' => ['required', 'boolean']]);
        $schema = ['required' => ['summary', 'topics', 'sentiment', 'trend_explanation', 'recommendations', 'limitations'], 'version' => 1];
        $template = DB::transaction(function () use ($request, $validated, $schema) {
            $version = (int) AiPromptTemplate::where('use_case', $validated['use_case'])->max('version') + 1;
            if ($validated['active']) {
                AiPromptTemplate::where('use_case', $validated['use_case'])->update(['active' => false]);
            }

            return AiPromptTemplate::create(['use_case' => $validated['use_case'], 'version' => $version, 'system_prompt' => $validated['system_prompt'], 'output_schema' => $schema, 'active' => $validated['active'], 'checksum' => hash('sha256', json_encode([$validated['system_prompt'], $schema], JSON_THROW_ON_ERROR)), 'created_by' => $request->user()->id]);
        });
        activity('ai_config')->performedOn($template)->causedBy($request->user())->withProperties(['use_case' => $template->use_case, 'version' => $template->version, 'active' => $template->active])->log('ai_prompt_version_created');

        return response()->json(['data' => $this->promptData($template)], 201);
    }

    public function createJob(Request $request, AnalysisRun $analysisRun, OrganizationalScope $scope, AiOrchestrator $orchestrator): JsonResponse
    {
        if (! $analysisRun->survey->owner_unit_id || ! $scope->allows($request->user(), $analysisRun->survey->owner_unit_id)) {
            throw new DomainRuleViolation('forbidden', 'Analysis run berada di luar scope Anda.', 403);
        }
        $validated = $request->validate(['provider_config_id' => ['required', 'uuid', 'exists:ai_provider_configs,id'], 'prompt_template_id' => ['required', 'uuid', 'exists:ai_prompt_templates,id'], 'reviewer_id' => ['required', 'integer', 'exists:users,id']]);
        $reviewer = User::findOrFail($validated['reviewer_id']);
        if (! $scope->allows($reviewer, $analysisRun->survey->owner_unit_id)) {
            throw new DomainRuleViolation('ai_governance_blocked', 'Reviewer harus berada dalam scope unit sumber.', 422);
        }
        $job = $orchestrator->request($analysisRun->load('snapshot'), AiProviderConfig::findOrFail($validated['provider_config_id']), AiPromptTemplate::findOrFail($validated['prompt_template_id']), $request->user(), $reviewer);

        return response()->json(['data' => $this->jobData($job->load('result'))], 202);
    }

    public function showJob(Request $request, AiJob $aiJob, OrganizationalScope $scope): JsonResponse
    {
        $this->assertJobScope($request, $aiJob, $scope);

        return response()->json(['data' => $this->jobData($aiJob->load('result'))]);
    }

    public function showResult(Request $request, AiResult $aiResult, OrganizationalScope $scope): JsonResponse
    {
        $this->assertJobScope($request, $aiResult->job, $scope);
        if (! $request->user()->can('ai.review') && $aiResult->review_status !== 'approved') {
            throw new DomainRuleViolation('not_found', 'Hasil AI belum tersedia untuk pembaca.', 404);
        }

        return response()->json(['data' => $this->resultData($aiResult)]);
    }

    public function review(Request $request, AiResult $aiResult, OrganizationalScope $scope, AiSafety $safety): JsonResponse
    {
        $job = $aiResult->job;
        $this->assertJobScope($request, $job, $scope);
        if ($job->reviewer_id !== $request->user()->id || $job->requested_by === $request->user()->id) {
            throw new DomainRuleViolation('forbidden', 'Hanya reviewer independen yang ditugaskan dapat memutuskan.', 403);
        }
        $version = $this->ifMatch($request);
        if ($version !== $aiResult->resource_version) {
            throw new DomainRuleViolation('version_conflict', 'Hasil AI telah berubah.', 412);
        }
        $validated = $request->validate(['decision' => ['required', 'in:edit,approve,reject'], 'note' => ['required', 'string', 'min:3', 'max:4000'], 'content' => ['nullable', 'array']]);
        $updates = ['reviewed_by' => $request->user()->id, 'review_note' => $validated['note'], 'reviewed_at' => now(), 'resource_version' => $aiResult->resource_version + 1];
        if ($validated['decision'] === 'edit') {
            if (! isset($validated['content'])) {
                throw new DomainRuleViolation('validation_failed', 'Content terstruktur wajib untuk keputusan edit.', 422);
            }
            $updates += ['edited_content' => $safety->validateOutput($validated['content']), 'review_status' => 'edited'];
        } else {
            $updates['review_status'] = $validated['decision'] === 'approve' ? 'approved' : 'rejected';
        }
        $aiResult->update($updates);
        activity('ai')->performedOn($aiResult)->causedBy($request->user())->withProperties(['decision' => $validated['decision'], 'version' => $aiResult->resource_version])->log('ai_result_reviewed');

        return response()->json(['data' => $this->resultData($aiResult)]);
    }

    private function assertJobScope(Request $request, AiJob $job, OrganizationalScope $scope): void
    {
        if (! $scope->allows($request->user(), $job->snapshot->owner_unit_id)) {
            throw new DomainRuleViolation('forbidden', 'AI job berada di luar scope Anda.', 403);
        }
    }

    private function ifMatch(Request $request): int
    {
        if (! preg_match('/^(?:W\/)?"(\d+)"$/', (string) $request->header('If-Match'), $matches)) {
            throw new DomainRuleViolation('precondition_required', 'If-Match version diperlukan.', 428);
        }

        return (int) $matches[1];
    }

    private function configData(AiProviderConfig $config): array
    {
        return ['id' => $config->id, 'provider' => $config->provider, 'model' => $config->model, 'base_url' => $config->base_url, 'secret_masked' => $config->secret_ciphertext ? '••••••••' : null, 'enabled' => $config->enabled, 'limits' => ['max_input_tokens' => $config->max_input_tokens, 'max_output_tokens' => $config->max_output_tokens, 'max_cost_micros' => $config->max_cost_micros, 'timeout_seconds' => $config->timeout_seconds, 'rate_limit_per_minute' => $config->rate_limit_per_minute], 'connection_status' => $config->connection_status, 'last_tested_at' => $config->last_tested_at?->toIso8601String()];
    }

    private function promptData(AiPromptTemplate $template): array
    {
        return ['id' => $template->id, 'use_case' => $template->use_case, 'version' => $template->version, 'active' => $template->active, 'checksum' => $template->checksum, 'output_schema' => $template->output_schema];
    }

    private function jobData(AiJob $job): array
    {
        return ['id' => $job->id, 'analysis_run_id' => $job->analysis_run_id, 'state' => $job->state, 'use_case' => $job->use_case, 'source_scope' => $job->source_scope, 'failure_code' => $job->failure_code, 'result_id' => $job->result?->id, 'review_status' => $job->result?->review_status, 'created_at' => $job->created_at?->toIso8601String(), 'completed_at' => $job->completed_at?->toIso8601String()];
    }

    private function resultData(AiResult $result): array
    {
        return ['id' => $result->id, 'job_id' => $result->ai_job_id, 'label' => $result->label, 'content' => $result->edited_content ?? $result->content, 'source_scope' => $result->source_scope, 'provider' => $result->provider, 'model' => $result->model, 'generated_at' => $result->generated_at?->toIso8601String(), 'review_status' => $result->review_status, 'reviewed_at' => $result->reviewed_at?->toIso8601String(), 'review_note' => $result->review_note, 'version' => $result->resource_version];
    }
}
