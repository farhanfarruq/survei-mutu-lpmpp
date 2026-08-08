<?php

namespace App\Models;

use App\Enums\InstrumentStatus;
use Database\Factories\InstrumentVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['survey_template_id', 'major', 'minor', 'patch', 'status', 'comparability_status', 'change_reason', 'content_hash', 'created_by', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'review_note'])]
class InstrumentVersion extends Model
{
    /** @use HasFactory<InstrumentVersionFactory> */
    use HasFactory, HasUuids, LogsActivity;

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(InstrumentSection::class)->orderBy('position');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('position');
    }

    public function scales(): HasMany
    {
        return $this->hasMany(Scale::class)->orderBy('code');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function versionLabel(): string
    {
        return "{$this->major}.{$this->minor}.{$this->patch}";
    }

    public function isEditable(): bool
    {
        return $this->status->editable();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'comparability_status', 'change_reason', 'content_hash', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'review_note'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['status' => InstrumentStatus::class, 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    }
}
