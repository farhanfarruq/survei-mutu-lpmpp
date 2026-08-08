<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['analysis_run_id', 'survey_id', 'owner_unit_id', 'survey_period_id', 'respondent_group_id', 'state', 'metrics', 'filter_provenance', 'limitations', 'response_count', 'eligible_count', 'reporting_threshold', 'suppressed', 'checksum', 'generated_at', 'released_at', 'released_by'])]
class AggregateSnapshot extends Model
{
    use HasUuids;

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, 'analysis_run_id');
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function ownerUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'owner_unit_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SurveyPeriod::class, 'survey_period_id');
    }

    public function respondentGroup(): BelongsTo
    {
        return $this->belongsTo(RespondentGroup::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    protected function casts(): array
    {
        return ['metrics' => 'array', 'filter_provenance' => 'array', 'limitations' => 'array', 'suppressed' => 'boolean', 'generated_at' => 'datetime', 'released_at' => 'datetime'];
    }
}
