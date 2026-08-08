<?php

namespace App\Models;

use App\Enums\SurveyState;
use Database\Factories\SurveyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['instrument_version_id', 'survey_period_id', 'owner_unit_id', 'code', 'name', 'state', 'privacy_mode', 'opens_at', 'closes_at', 'timezone', 'privacy_notice', 'reporting_threshold', 'action_owner_id', 'policy_snapshot', 'population_snapshot_hash', 'responses_count', 'created_by', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'published_at', 'closed_at', 'archived_at', 'review_note'])]
class Survey extends Model
{
    /** @use HasFactory<SurveyFactory> */
    use HasFactory, HasUuids, LogsActivity;

    public function instrumentVersion(): BelongsTo
    {
        return $this->belongsTo(InstrumentVersion::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SurveyPeriod::class, 'survey_period_id');
    }

    public function ownerUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'owner_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actionOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_owner_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SurveyTarget::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(SurveyParticipation::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function analysisRuns(): HasMany
    {
        return $this->hasMany(AnalysisRun::class);
    }

    public function aggregateSnapshots(): HasMany
    {
        return $this->hasMany(AggregateSnapshot::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['instrument_version_id', 'survey_period_id', 'owner_unit_id', 'code', 'name', 'state', 'privacy_mode', 'opens_at', 'closes_at', 'timezone', 'privacy_notice', 'reporting_threshold', 'action_owner_id', 'policy_snapshot', 'population_snapshot_hash', 'responses_count', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'published_at', 'closed_at', 'archived_at', 'review_note'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['state' => SurveyState::class, 'opens_at' => 'datetime', 'closes_at' => 'datetime', 'policy_snapshot' => 'array', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'published_at' => 'datetime', 'closed_at' => 'datetime', 'archived_at' => 'datetime'];
    }
}
