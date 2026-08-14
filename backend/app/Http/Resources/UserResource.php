<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'identity_number' => $this->resource->getAttributes()['identity_number'] ?? null,
            'account_type' => $this->resource->getAttributes()['account_type'] ?? null,
            'is_active' => $this->is_active,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'permissions' => $this->when(
                $request->user()?->is($this->resource),
                fn () => $this->getAllPermissions()->pluck('name')->sort()->values(),
            ),
            'organizational_units' => $this->whenLoaded('organizationalUnits', fn () => $this->organizationalUnits->map(fn ($unit) => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'scope_mode' => $unit->pivot->scope_mode,
                'is_primary' => (bool) $unit->pivot->is_primary,
            ])->values()),
        ];
    }
}
