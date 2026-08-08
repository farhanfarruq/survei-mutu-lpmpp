<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['organizational_unit_id', 'code', 'name', 'source_type', 'schema_version', 'filter_definition', 'source_snapshot_hash', 'is_active'])]
class RespondentGroup extends Model
{
    use HasUuids, LogsActivity;

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SurveyTarget::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['organizational_unit_id', 'code', 'name', 'source_type', 'schema_version', 'filter_definition', 'source_snapshot_hash', 'is_active'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['filter_definition' => 'array', 'is_active' => 'boolean'];
    }
}
