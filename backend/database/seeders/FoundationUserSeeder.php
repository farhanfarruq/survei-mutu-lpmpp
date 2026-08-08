<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Database\Seeder;

class FoundationUserSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            ['name' => 'Admin LPMPP Fiktif', 'email' => 'admin.lpmpp@example.test', 'role' => 'admin_lpmpp', 'unit' => 'LPMPP-DEMO', 'scope' => 'subtree'],
            ['name' => 'Pimpinan Fiktif', 'email' => 'pimpinan@example.test', 'role' => 'leader', 'unit' => 'UNIV-DEMO', 'scope' => 'subtree'],
            ['name' => 'Reviewer Fiktif', 'email' => 'reviewer@example.test', 'role' => 'reviewer', 'unit' => 'LPMPP-DEMO', 'scope' => 'subtree'],
            ['name' => 'Responden Fiktif', 'email' => 'responden@example.test', 'role' => 'respondent', 'unit' => 'FT-DEMO', 'scope' => 'self'],
        ];

        foreach ($profiles as $profile) {
            $user = User::query()->firstOrCreate(
                ['email' => $profile['email']],
                User::factory()->make(['name' => $profile['name'], 'email' => $profile['email']])->getAttributes(),
            );
            $user->syncRoles([$profile['role']]);

            $unit = OrganizationalUnit::query()->where('code', $profile['unit'])->firstOrFail();
            $user->organizationalUnits()->sync([
                $unit->id => ['scope_mode' => $profile['scope'], 'is_primary' => true],
            ]);
        }
    }
}
