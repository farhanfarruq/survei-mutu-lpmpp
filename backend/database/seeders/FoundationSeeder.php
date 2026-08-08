<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            OrganizationalUnitSeeder::class,
            FoundationUserSeeder::class,
        ]);
    }
}
