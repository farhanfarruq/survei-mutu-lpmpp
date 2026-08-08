<?php

namespace Database\Factories;

use App\Models\OrganizationalUnit;
use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SurveyTemplate> */
class SurveyTemplateFactory extends Factory
{
    protected $model = SurveyTemplate::class;

    public function definition(): array
    {
        return [
            'owner_unit_id' => OrganizationalUnit::factory(),
            'code' => 'TPL-'.$this->faker->unique()->numerify('#####'),
            'family_code' => 'LAYANAN_AKADEMIK',
            'name' => $this->faker->sentence(4),
            'status' => 'active',
            'purpose' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
