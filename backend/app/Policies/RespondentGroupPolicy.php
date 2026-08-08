<?php

namespace App\Policies;

use App\Models\RespondentGroup;
use App\Models\User;
use App\Services\OrganizationalScope;

class RespondentGroupPolicy
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('campaign.read');
    }

    public function view(User $user, RespondentGroup $group): bool
    {
        return $user->can('campaign.read') && $this->scope->allows($user, $group->organizational_unit_id);
    }

    public function create(User $user): bool
    {
        return $user->can('population.manage');
    }

    public function update(User $user, RespondentGroup $group): bool
    {
        return $user->can('population.manage') && $this->scope->allows($user, $group->organizational_unit_id);
    }

    public function delete(User $user, RespondentGroup $group): bool
    {
        return $user->can('population.manage') && $this->scope->allows($user, $group->organizational_unit_id) && ! $group->targets()->exists();
    }
}
