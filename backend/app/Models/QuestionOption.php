<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['question_id', 'code', 'label', 'position', 'score_value', 'is_exclusive'])]
class QuestionOption extends Model
{
    use HasUuids;

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    protected function casts(): array
    {
        return ['score_value' => 'decimal:6', 'is_exclusive' => 'boolean'];
    }
}
