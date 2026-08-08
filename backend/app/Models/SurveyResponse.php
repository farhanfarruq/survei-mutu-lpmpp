<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['survey_id', 'respondent_session_id', 'state', 'resource_version', 'progress', 'consent_version', 'consented_at', 'submitted_at', 'receipt_code'])]
class SurveyResponse extends Model
{
    use HasUuids;

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function respondentSession(): BelongsTo
    {
        return $this->belongsTo(RespondentSession::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ResponseAnswer::class);
    }

    public function confidentialLink(): HasOne
    {
        return $this->hasOne(ConfidentialResponseLink::class);
    }

    public function idempotencyKeys(): HasMany
    {
        return $this->hasMany(ResponseIdempotencyKey::class);
    }

    protected function casts(): array
    {
        return ['consented_at' => 'datetime', 'submitted_at' => 'datetime'];
    }
}
