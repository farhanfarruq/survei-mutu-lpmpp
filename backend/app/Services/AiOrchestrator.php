<?php

namespace App\Services;

use App\Exceptions\DomainRuleViolation;
use App\Jobs\RunAiInsight;
use App\Models\AiJob;
use App\Models\AiPromptTemplate;
use App\Models\AiProviderConfig;
use App\Models\AnalysisRun;
use App\Models\User;

final class AiOrchestrator
{
    public function request(AnalysisRun $run, AiProviderConfig $config, AiPromptTemplate $template, User $requester, User $reviewer): AiJob
    {
        if (! config('ai.enabled')) {
            throw new DomainRuleViolation('ai_feature_disabled', 'Fitur AI sedang dinonaktifkan.', 403);
        }
        if ($run->state !== 'completed' || ! $run->snapshot || $run->snapshot->state !== 'released' || $run->snapshot->suppressed) {
            throw new DomainRuleViolation('ai_governance_blocked', 'AI hanya dapat memakai snapshot released yang lolos suppression.', 422);
        }
        if (! $config->enabled || ! $config->secret_ciphertext || ! $template->active || ! $this->baseUrlAllowed($config->provider, $config->base_url)) {
            throw new DomainRuleViolation('ai_governance_blocked', 'Konfigurasi provider atau prompt belum aktif dan lengkap.', 422);
        }
        if ($reviewer->id === $requester->id || ! $reviewer->can('ai.review')) {
            throw new DomainRuleViolation('ai_governance_blocked', 'Reviewer independen dengan izin ai.review diperlukan.', 422);
        }
        $maximumCost = (int) ceil($config->max_input_tokens / 1000 * $config->input_cost_micros_per_1k + $config->max_output_tokens / 1000 * $config->output_cost_micros_per_1k);
        if ($maximumCost > $config->max_cost_micros) {
            throw new DomainRuleViolation('ai_budget_exceeded', 'Batas biaya konfigurasi tidak mencukupi untuk request maksimum.', 429);
        }

        $scope = ['snapshot_id' => $run->snapshot->id, 'survey_id' => $run->survey_id, 'unit_id' => $run->snapshot->owner_unit_id, 'period_id' => $run->snapshot->survey_period_id, 'response_count' => $run->snapshot->response_count, 'prompt_version' => $template->version];
        $job = AiJob::create(['analysis_run_id' => $run->id, 'aggregate_snapshot_id' => $run->snapshot->id, 'ai_provider_config_id' => $config->id, 'ai_prompt_template_id' => $template->id, 'requested_by' => $requester->id, 'reviewer_id' => $reviewer->id, 'use_case' => $template->use_case, 'source_scope' => $scope, 'input_checksum' => hash('sha256', json_encode([$run->snapshot->checksum, $template->checksum, $scope], JSON_THROW_ON_ERROR))]);
        activity('ai')->performedOn($job)->causedBy($requester)->withProperties(['snapshot_id' => $run->snapshot->id, 'provider' => $config->provider, 'model' => $config->model, 'prompt_version' => $template->version])->log('ai_job_requested');
        RunAiInsight::dispatch($job);

        return $job->refresh();
    }

    public function baseUrlAllowed(string $provider, string $url): bool
    {
        $normalized = rtrim(strtolower($url), '/');

        return in_array($normalized, array_map(fn ($allowed) => rtrim(strtolower($allowed), '/'), config("ai.allowed_base_urls.{$provider}", [])), true);
    }
}
