<?php

namespace App\Jobs;

use App\Models\AggregateSnapshot;
use App\Models\AnalysisRun;
use App\Services\SurveyAnalytics;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunSurveyAnalysis implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly AnalysisRun $run) {}

    public function handle(SurveyAnalytics $analytics): void
    {
        $this->run->update(['state' => 'running', 'started_at' => now(), 'error_message' => null]);
        try {
            $result = $analytics->compute($this->run->survey, $this->run->input_hash);
            $checksum = hash('sha256', json_encode($result['metrics'], JSON_THROW_ON_ERROR));
            $groupIds = $this->run->survey->targets->pluck('respondent_group_id')->filter()->unique()->values();
            AggregateSnapshot::create([
                'analysis_run_id' => $this->run->id,
                'survey_id' => $this->run->survey_id,
                'owner_unit_id' => $this->run->survey->owner_unit_id,
                'survey_period_id' => $this->run->survey->survey_period_id,
                'respondent_group_id' => $groupIds->count() === 1 ? $groupIds->first() : null,
                'metrics' => $result['metrics'],
                'filter_provenance' => $result['provenance'],
                'limitations' => $result['limitations'],
                'response_count' => $result['responseCount'],
                'eligible_count' => $result['eligibleCount'],
                'reporting_threshold' => $result['threshold'],
                'suppressed' => $result['overallSuppressed'],
                'checksum' => $checksum,
                'generated_at' => now(),
            ]);
            $this->run->update(['state' => 'completed', 'completed_at' => now()]);
            activity('analytics')->performedOn($this->run)->causedBy($this->run->requester)->withProperties(['survey_id' => $this->run->survey_id, 'input_hash' => $this->run->input_hash, 'checksum' => $checksum])->log('analysis_completed');
        } catch (\Throwable $error) {
            $this->run->update(['state' => 'failed', 'completed_at' => now(), 'error_message' => str($error->getMessage())->limit(1000)]);
            throw $error;
        }
    }
}
