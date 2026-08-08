<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['survey_id', 'token_hash', 'expires_at'])]
class RespondentSession extends Model
{
    use HasUuids;

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(SurveyResponse::class);
    }

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
