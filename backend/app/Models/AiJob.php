<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['analysis_run_id', 'aggregate_snapshot_id', 'ai_provider_config_id', 'ai_prompt_template_id', 'requested_by', 'reviewer_id', 'use_case', 'state', 'source_scope', 'input_checksum', 'resource_version', 'failure_code', 'started_at', 'completed_at'])]
class AiJob extends Model
{
    use HasUuids;

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, 'analysis_run_id');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AggregateSnapshot::class, 'aggregate_snapshot_id');
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(AiProviderConfig::class, 'ai_provider_config_id');
    }

    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(AiPromptTemplate::class, 'ai_prompt_template_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(AiResult::class);
    }

    protected function casts(): array
    {
        return ['source_scope' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
