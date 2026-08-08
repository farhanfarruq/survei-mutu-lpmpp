<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ai_job_id', 'provider', 'model', 'input_tokens', 'output_tokens', 'cost_micros', 'latency_ms', 'outcome'])]
class AiUsageLog extends Model
{
    use HasUuids;

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiJob::class);
    }
}
