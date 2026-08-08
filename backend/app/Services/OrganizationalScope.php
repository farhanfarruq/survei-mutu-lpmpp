<?php

namespace App\Services;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Collection;

class OrganizationalScope
{
    /** @return Collection<int, string> */
    public function accessibleUnitIds(User $user): Collection
    {
        if ($user->can('organization.scope.all')) {
            return OrganizationalUnit::query()->pluck('id');
        }

        $memberships = $user->organizationalUnits()->get(['organizational_units.id', 'organizational_units.parent_id']);
        $ids = $memberships->pluck('id');
        $subtreeRoots = $memberships
            ->filter(fn (OrganizationalUnit $unit): bool => $unit->pivot->scope_mode === 'subtree')
            ->pluck('id');

        if ($subtreeRoots->isEmpty()) {
            return $ids->unique()->values();
        }

        // ponytail: in-memory traversal is sufficient for the small foundation hierarchy;
        // replace with a recursive CTE when capacity evidence warrants it.
        $childrenByParent = OrganizationalUnit::query()
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');
        $queue = $subtreeRoots->values();

        while ($queue->isNotEmpty()) {
            $parentId = $queue->shift();
            $children = $childrenByParent->get($parentId, collect())->pluck('id');
            $ids = $ids->merge($children);
            $queue = $queue->merge($children);
        }

        return $ids->unique()->values();
    }

    public function allows(User $user, OrganizationalUnit|string $unit): bool
    {
        $unitId = $unit instanceof OrganizationalUnit ? $unit->getKey() : $unit;

        return $this->accessibleUnitIds($user)->containsStrict($unitId);
    }
}
