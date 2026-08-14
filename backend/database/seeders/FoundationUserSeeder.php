<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class FoundationUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Akun demo dengan password default tidak boleh dibuat di production.');
        }

        $profiles = [
            ['name' => 'Super Admin ITDA', 'email' => 'superadmin@example.test', 'role' => 'super_admin'],
            ['name' => 'Admin LPMPP ITDA', 'email' => 'admin.lpmpp@example.test', 'role' => 'admin_lpmpp'],
            ['name' => 'Pimpinan ITDA', 'email' => 'pimpinan@example.test', 'role' => 'leader'],
            ['name' => 'Responden ITDA', 'email' => 'responden@example.test', 'role' => 'respondent'],
        ];

        foreach ($profiles as $profile) {
            if (User::role($profile['role'])->exists()) {
                continue;
            }

            $user = User::query()->firstOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );
            $user->assignRole($profile['role']);
        }

        $units = OrganizationalUnit::query()->whereIn('code', ['ITDA', 'LPMPP', 'PRODI-IF'])->get()->keyBy('code');

        User::query()->with('roles')->eachById(function (User $user) use ($units): void {
            [$unitCode, $scope] = match (true) {
                $user->hasRole('admin_lpmpp') => ['LPMPP', 'subtree'],
                $user->hasRole('respondent') => ['PRODI-IF', 'self'],
                default => ['ITDA', 'subtree'],
            };

            $unit = $units->get($unitCode);
            $user->organizationalUnits()->sync([
                $unit->id => ['scope_mode' => $scope, 'is_primary' => true],
            ]);
        });
    }
}
