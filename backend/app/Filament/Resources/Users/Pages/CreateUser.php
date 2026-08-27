<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;
        $user->load(['roles', 'organizationalUnits']);
        activity('user_access')->performedOn($user)->causedBy(auth()->user())->event('access_assigned')->withProperties([
            'roles' => $user->roles->pluck('name')->values()->all(),
            'units' => $user->organizationalUnits->pluck('code')->values()->all(),
        ])->log('Akses pengguna ditetapkan');
    }
}
