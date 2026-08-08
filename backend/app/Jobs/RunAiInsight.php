<?php

namespace App\Jobs;

use App\Contracts\AiProvider;
use App\Exceptions\DomainRuleViolation;
use App\Models\AiJob;
use App\Models\AiResult;
use App\Models\AiUsageLog;
use App\Services\AiSafety;
use App\Services\NotificationHub;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\RateLimiter;

class RunAiInsight implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly AiJob $aiJob) {}

    public function handle(AiProvider $provider, AiSafety $safety, NotificationHub $notifications): void
    {
        $job = $this->aiJob->load(['config', 'promptTemplate', 'snapshot', 'requester']);
        $job->update(['state' => 'running', 'started_at' => now(), 'failure_code' => null]);
        try {
            if (RateLimiter::tooManyAttempts("ai-provider:{$job->ai_provider_config_id}", $job->config->rate_limit_per_minute)) {
                throw new \RuntimeException('provider_rate_limited');
            }
            RateLimiter::hit("ai-provider:{$job->ai_provider_config_id}", 60);
            $projection = $safety->projection($job->snapshot);
            $generated = $provider->generate($job->config, $job->promptTemplate, $projection);
            $inputTokens = (int) $generated['input_tokens'];
            $outputTokens = (int) $generated['output_tokens'];
            $cost = (int) ceil($inputTokens / 1000 * $job->config->input_cost_micros_per_1k + $outputTokens / 1000 * $job->config->output_cost_micros_per_1k);
            if ($inputTokens > $job->config->max_input_tokens || $outputTokens > $job->config->max_output_tokens || $cost > $job->config->max_cost_micros) {
                throw new \RuntimeException('provider_budget_exceeded');
            }
            $content = $safety->validateOutput($generated['content']);
            AiUsageLog::create(['ai_job_id' => $job->id, 'provider' => $job->config->provider, 'model' => $job->config->model, 'input_tokens' => $inputTokens, 'output_tokens' => $outputTokens, 'cost_micros' => $cost, 'latency_ms' => $generated['latency_ms'], 'outcome' => 'success']);
            AiResult::create(['ai_job_id' => $job->id, 'content' => $content, 'label' => 'AI-generated draft — requires human review', 'source_scope' => $job->source_scope, 'provider' => $job->config->provider, 'model' => $job->config->model, 'generated_at' => now()]);
            $job->update(['state' => 'completed', 'completed_at' => now(), 'resource_version' => $job->resource_version + 1]);
            activity('ai')->performedOn($job)->causedBy($job->requester)->withProperties(['outcome' => 'success', 'source_scope' => $job->source_scope])->log('ai_job_completed');
        } catch (\Throwable $error) {
            $code = $error instanceof DomainRuleViolation ? $error->ruleCode : (in_array($error->getMessage(), ['provider_rate_limited', 'provider_budget_exceeded'], true) ? $error->getMessage() : 'provider_failed');
            AiUsageLog::create(['ai_job_id' => $job->id, 'provider' => $job->config->provider, 'model' => $job->config->model, 'outcome' => 'fallback']);
            AiResult::firstOrCreate(['ai_job_id' => $job->id], ['content' => $this->fallback($job), 'label' => 'Deterministic fallback — not AI-generated', 'source_scope' => $job->source_scope, 'provider' => $job->config->provider, 'model' => $job->config->model, 'generated_at' => now()]);
            $job->update(['state' => 'completed_with_fallback', 'failure_code' => $code, 'completed_at' => now(), 'resource_version' => $job->resource_version + 1]);
            $notifications->send($job->requester, 'ai_failure', 'AI memakai fallback', 'Provider AI gagal atau output dikarantina. Gunakan dashboard statistik dan review fallback.', "/app/ai?job={$job->id}", ['ai_job_id' => $job->id, 'status' => 'fallback'], $job->id);
            activity('ai')->performedOn($job)->causedBy($job->requester)->withProperties(['outcome' => 'fallback', 'failure_code' => $code])->log('ai_job_fallback');
        }
    }

    private function fallback(AiJob $job): array
    {
        return ['summary' => 'Insight AI tidak tersedia. Gunakan hasil statistik deterministik pada snapshot sebagai sumber utama.', 'topics' => [], 'sentiment' => ['label' => 'neutral', 'confidence' => 0], 'trend_explanation' => 'Tidak dibuat karena provider gagal atau output tidak valid.', 'recommendations' => ['Lakukan interpretasi manusia terhadap indikator agregat yang telah dirilis.'], 'limitations' => ['Fallback ini bukan keluaran AI dan tidak menambah atau menghitung ulang statistik.']];
    }
}
