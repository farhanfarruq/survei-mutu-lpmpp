<?php

namespace Database\Factories;

use App\Models\OrganizationalUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationalUnit> */
class OrganizationalUnitFactory extends Factory
{
    protected $model = OrganizationalUnit::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('UNIT-###')),
            'name' => fake()->company(),
            'type' => 'unit',
            'is_active' => true,
        ];
    }
}
