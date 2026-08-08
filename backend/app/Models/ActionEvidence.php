<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['follow_up_action_id', 'submitted_by', 'title', 'description', 'reference_url', 'checksum', 'version'])]
class ActionEvidence extends Model
{
    use HasUuids;

    public function action(): BelongsTo
    {
        return $this->belongsTo(FollowUpAction::class, 'follow_up_action_id');
    }
}
