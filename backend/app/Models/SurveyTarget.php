<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['survey_id', 'respondent_group_id', 'target_unit_id', 'target_type', 'eligible_count', 'sampling', 'frame_checksum'])]
class SurveyTarget extends Model
{
    use HasUuids;

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function respondentGroup(): BelongsTo
    {
        return $this->belongsTo(RespondentGroup::class);
    }

    public function targetUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'target_unit_id');
    }

    protected function casts(): array
    {
        return ['sampling' => 'array'];
    }
}
