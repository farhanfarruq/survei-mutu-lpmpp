<?php

namespace App\Policies;

use App\Enums\SurveyState;
use App\Models\Survey;
use App\Models\User;
use App\Services\OrganizationalScope;

class SurveyPolicy
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('campaign.read');
    }

    public function view(User $user, Survey $survey): bool
    {
        return $user->can('campaign.read') && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function create(User $user): bool
    {
        return $user->can('campaign.create');
    }

    public function update(User $user, Survey $survey): bool
    {
        return $user->can('campaign.update') && $survey->state->configurationEditable() && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function delete(User $user, Survey $survey): bool
    {
        return $user->can('campaign.delete') && $survey->state->configurationEditable() && $survey->responses_count === 0 && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function submitReview(User $user, Survey $survey): bool
    {
        return $user->can('campaign.review') && $survey->state->configurationEditable() && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function review(User $user, Survey $survey): bool
    {
        return $user->can('campaign.approve') && $survey->state === SurveyState::InReview && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function publish(User $user, Survey $survey): bool
    {
        return $user->can('campaign.publish') && $survey->state === SurveyState::Approved && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function close(User $user, Survey $survey): bool
    {
        return $user->can('campaign.update') && in_array($survey->state, [SurveyState::Scheduled, SurveyState::Active], true) && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function reschedule(User $user, Survey $survey): bool
    {
        return $user->can('campaign.update')
            && in_array($survey->getRawOriginal('state'), [SurveyState::Scheduled->value, SurveyState::Active->value], true)
            && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function archive(User $user, Survey $survey): bool
    {
        return $user->can('campaign.update') && $survey->state === SurveyState::Closed && $this->scope->allows($user, $survey->owner_unit_id);
    }

    public function duplicate(User $user, Survey $survey): bool
    {
        return $user->can('campaign.create') && $this->scope->allows($user, $survey->owner_unit_id);
    }
}
