<?php

namespace App\Policies;

use App\Models\InstrumentVersion;
use App\Models\User;
use App\Services\OrganizationalScope;

class InstrumentVersionPolicy
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('template.read');
    }

    public function view(User $user, InstrumentVersion $version): bool
    {
        return $user->can('template.read') && $this->scope->allows($user, $version->template->owner_unit_id);
    }

    public function create(User $user): bool
    {
        return $user->can('template.update');
    }

    public function update(User $user, InstrumentVersion $version): bool
    {
        return $user->can('template.update') && $version->isEditable() && $this->scope->allows($user, $version->template->owner_unit_id);
    }

    public function delete(User $user, InstrumentVersion $version): bool
    {
        return $user->can('template.delete') && $version->isEditable() && ! $version->surveys()->exists() && $this->scope->allows($user, $version->template->owner_unit_id);
    }

    public function submitReview(User $user, InstrumentVersion $version): bool
    {
        return $user->can('validation.create') && $version->isEditable() && $this->scope->allows($user, $version->template->owner_unit_id);
    }

    public function review(User $user, InstrumentVersion $version): bool
    {
        return $user->can('validation.approve') && $this->scope->allows($user, $version->template->owner_unit_id);
    }

    public function duplicate(User $user, InstrumentVersion $version): bool
    {
        return $user->can('template.update') && $this->scope->allows($user, $version->template->owner_unit_id);
    }
}
