<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array{roles: array<int, string>, units: array<int, string>} */
    protected array $previousAccess = ['roles' => [], 'units' => []];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function beforeSave(): void
    {
        $user = $this->user();
        $user->load(['roles', 'organizationalUnits']);
        $this->previousAccess = [
            'roles' => $user->roles->pluck('name')->sort()->values()->all(),
            'units' => $user->organizationalUnits->pluck('code')->sort()->values()->all(),
        ];
    }

    protected function afterSave(): void
    {
        $user = $this->user();
        $user->load(['roles', 'organizationalUnits']);
        $current = [
            'roles' => $user->roles->pluck('name')->sort()->values()->all(),
            'units' => $user->organizationalUnits->pluck('code')->sort()->values()->all(),
        ];

        if ($current !== $this->previousAccess) {
            activity('user_access')->performedOn($user)->causedBy(auth()->user())->event('access_updated')->withProperties([
                'old' => $this->previousAccess,
                'attributes' => $current,
            ])->log('Akses pengguna diperbarui');
        }
    }

    private function user(): User
    {
        /** @var User $user */
        $user = $this->record;

        return $user;
    }
}
