<?php

namespace App\Policies;

use App\Models\User;
use App\Services\OrganizationalScope;

class UserPolicy
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.view') && $this->sharesScope($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.update') && $this->sharesScope($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isNot($target) && $user->can('users.delete') && $this->sharesScope($user, $target);
    }

    private function sharesScope(User $user, User $target): bool
    {
        if ($user->can('organization.scope.all')) {
            return true;
        }

        return $target->organizationalUnits()
            ->whereIn('organizational_units.id', $this->scope->accessibleUnitIds($user))
            ->exists();
    }
}
