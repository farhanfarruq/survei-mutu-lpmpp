<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['finding_id', 'pic_user_id', 'verifier_user_id', 'title', 'root_cause', 'plan', 'expected_output', 'resource_needs', 'assignment_note', 'state', 'progress', 'due_on', 'revision_count', 'accepted_at', 'submitted_at', 'verified_at', 'resource_version'])]
class FollowUpAction extends Model
{
    use HasUuids;

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_user_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ActionEvidence::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(ActionVerification::class);
    }

    protected function casts(): array
    {
        return ['due_on' => 'date', 'accepted_at' => 'datetime', 'submitted_at' => 'datetime', 'verified_at' => 'datetime'];
    }
}
