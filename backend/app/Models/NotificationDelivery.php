<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'event_type', 'channel', 'logical_key', 'state', 'provider_reference', 'failure_code', 'attempt_count', 'last_attempt_at', 'sent_at'])]
class NotificationDelivery extends Model
{
    use HasUuids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'last_attempt_at' => 'datetime', 'sent_at' => 'datetime'];
    }
}
