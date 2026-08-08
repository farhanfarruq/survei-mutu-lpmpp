<?php

namespace App\Policies;

use App\Models\SurveyPeriod;
use App\Models\User;

class SurveyPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('campaign.read');
    }

    public function view(User $user, SurveyPeriod $period): bool
    {
        return $user->can('campaign.read');
    }

    public function create(User $user): bool
    {
        return $user->can('campaign.create');
    }

    public function update(User $user, SurveyPeriod $period): bool
    {
        return $user->can('campaign.update');
    }

    public function delete(User $user, SurveyPeriod $period): bool
    {
        return $user->can('campaign.delete') && ! $period->surveys()->exists();
    }
}
