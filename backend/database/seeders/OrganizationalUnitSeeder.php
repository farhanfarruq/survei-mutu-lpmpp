<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class OrganizationalUnitSeeder extends Seeder
{
    public function run(): void
    {
        $university = OrganizationalUnit::query()->updateOrCreate(
            ['code' => 'UNIV-DEMO'],
            ['name' => 'Universitas Contoh', 'type' => 'university', 'is_active' => true],
        );

        foreach ([
            ['code' => 'FT-DEMO', 'name' => 'Fakultas Teknik Contoh'],
            ['code' => 'FEB-DEMO', 'name' => 'Fakultas Ekonomi dan Bisnis Contoh'],
            ['code' => 'LPMPP-DEMO', 'name' => 'LPMPP Contoh'],
        ] as $unit) {
            OrganizationalUnit::query()->updateOrCreate(
                ['code' => $unit['code']],
                $unit + ['parent_id' => $university->id, 'type' => 'faculty_or_unit', 'is_active' => true],
            );
        }
    }
}
