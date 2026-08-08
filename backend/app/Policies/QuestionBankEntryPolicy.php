<?php

namespace App\Policies;

use App\Models\QuestionBankEntry;
use App\Models\User;
use App\Services\OrganizationalScope;

class QuestionBankEntryPolicy
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('template.read');
    }

    public function view(User $user, QuestionBankEntry $entry): bool
    {
        return $user->can('template.read') && $this->scope->allows($user, $entry->owner_unit_id);
    }

    public function create(User $user): bool
    {
        return $user->can('template.create');
    }

    public function update(User $user, QuestionBankEntry $entry): bool
    {
        return $user->can('template.update') && $this->scope->allows($user, $entry->owner_unit_id);
    }

    public function delete(User $user, QuestionBankEntry $entry): bool
    {
        return $user->can('template.delete') && $this->scope->allows($user, $entry->owner_unit_id);
    }
}
