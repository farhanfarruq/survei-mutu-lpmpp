<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['aggregate_snapshot_id', 'owner_unit_id', 'code', 'source_type', 'source_indicator_code', 'source_score', 'title', 'description', 'source_evidence', 'severity', 'state', 'due_on', 'created_by', 'resource_version'])]
class Finding extends Model
{
    use HasUuids;

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AggregateSnapshot::class, 'aggregate_snapshot_id');
    }

    public function ownerUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'owner_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(FollowUpAction::class);
    }

    protected function casts(): array
    {
        return ['source_score' => 'decimal:6', 'due_on' => 'date'];
    }
}
