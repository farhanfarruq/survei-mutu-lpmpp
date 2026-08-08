<?php

namespace App\Models;

use Database\Factories\SurveyTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['owner_unit_id', 'code', 'family_code', 'name', 'status', 'purpose', 'created_by', 'retired_at'])]
class SurveyTemplate extends Model
{
    /** @use HasFactory<SurveyTemplateFactory> */
    use HasFactory, HasUuids, LogsActivity;

    public function ownerUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'owner_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(InstrumentVersion::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['owner_unit_id', 'code', 'family_code', 'name', 'status', 'purpose', 'retired_at'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['retired_at' => 'datetime'];
    }
}
