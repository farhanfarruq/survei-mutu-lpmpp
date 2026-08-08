<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['survey_id', 'requested_by', 'state', 'input_hash', 'formula_version', 'parameters', 'started_at', 'completed_at', 'error_message'])]
class AnalysisRun extends Model
{
    use HasUuids;

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(AggregateSnapshot::class);
    }

    public function aiJobs(): HasMany
    {
        return $this->hasMany(AiJob::class);
    }

    protected function casts(): array
    {
        return ['parameters' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
