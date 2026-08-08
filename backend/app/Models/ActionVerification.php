<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['follow_up_action_id', 'verified_by', 'decision', 'reason', 'evidence_review', 'revision_number'])]
class ActionVerification extends Model
{
    use HasUuids;

    public function action(): BelongsTo
    {
        return $this->belongsTo(FollowUpAction::class, 'follow_up_action_id');
    }
}
