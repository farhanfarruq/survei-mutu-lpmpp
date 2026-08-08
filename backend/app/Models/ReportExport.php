<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['aggregate_snapshot_id', 'requested_by', 'state', 'format', 'filters', 'filter_provenance', 'idempotency_key_hash', 'disk', 'path', 'checksum', 'expires_at', 'downloaded_at', 'revoked_at', 'error_message'])]
class ReportExport extends Model
{
    use HasUuids;

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AggregateSnapshot::class, 'aggregate_snapshot_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ReportDownloadTicket::class);
    }

    protected function casts(): array
    {
        return ['filters' => 'array', 'filter_provenance' => 'array', 'expires_at' => 'datetime', 'downloaded_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
