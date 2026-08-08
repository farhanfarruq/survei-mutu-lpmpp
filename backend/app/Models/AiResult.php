<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ai_job_id', 'content', 'edited_content', 'label', 'source_scope', 'provider', 'model', 'review_status', 'reviewed_by', 'review_note', 'generated_at', 'reviewed_at', 'resource_version'])]
class AiResult extends Model
{
    use HasUuids;

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiJob::class, 'ai_job_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return ['content' => 'array', 'edited_content' => 'array', 'source_scope' => 'array', 'generated_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }
}
