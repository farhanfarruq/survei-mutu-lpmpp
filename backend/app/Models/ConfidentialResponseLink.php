<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['survey_response_id', 'survey_participation_id'])]
class ConfidentialResponseLink extends Model
{
    use HasUuids;

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    public function participation(): BelongsTo
    {
        return $this->belongsTo(SurveyParticipation::class, 'survey_participation_id');
    }
}
