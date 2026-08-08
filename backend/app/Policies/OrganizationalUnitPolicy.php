<?php

namespace App\Policies;

use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\OrganizationalScope;

class OrganizationalUnitPolicy
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('organizational-units.view');
    }

    public function view(User $user, OrganizationalUnit $unit): bool
    {
        return $user->can('organizational-units.view') && $this->scope->allows($user, $unit);
    }

    public function create(User $user): bool
    {
        return $user->can('organizational-units.create');
    }

    public function update(User $user, OrganizationalUnit $unit): bool
    {
        return $user->can('organizational-units.update') && $this->scope->allows($user, $unit);
    }

    public function delete(User $user, OrganizationalUnit $unit): bool
    {
        return $user->can('organizational-units.delete')
            && $this->scope->allows($user, $unit)
            && ! $unit->children()->exists()
            && ! $unit->users()->exists();
    }
}
