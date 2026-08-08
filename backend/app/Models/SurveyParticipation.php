<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['survey_id', 'user_id', 'external_reference_hash', 'invitation_token_hash', 'completion_token_hash', 'invitation_expires_at', 'invitation_revoked_at', 'started_at', 'completed_at', 'declined_at', 'last_reminded_at', 'reminder_count'])]
class SurveyParticipation extends Model
{
    use HasUuids;

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeReminderEligible(Builder $query): Builder
    {
        return $query
            ->whereNull('completed_at')
            ->whereNull('declined_at')
            ->whereNull('invitation_revoked_at')
            ->where('reminder_count', '<', 3)
            ->where(fn (Builder $query) => $query
                ->whereNull('last_reminded_at')
                ->orWhere('last_reminded_at', '<=', now()->subDays(3)));
    }

    protected function casts(): array
    {
        return [
            'invitation_expires_at' => 'datetime',
            'invitation_revoked_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'declined_at' => 'datetime',
            'last_reminded_at' => 'datetime',
        ];
    }
}
