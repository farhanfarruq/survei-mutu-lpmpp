<?php

namespace Database\Factories;

use App\Enums\SurveyState;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Survey> */
class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    public function definition(): array
    {
        return [
            'instrument_version_id' => InstrumentVersion::factory()->approved(),
            'survey_period_id' => SurveyPeriod::factory(),
            'owner_unit_id' => OrganizationalUnit::factory(),
            'code' => 'SRV-'.$this->faker->unique()->numerify('#####'),
            'name' => $this->faker->sentence(4),
            'state' => SurveyState::Draft,
            'privacy_mode' => 'anonymous',
            'opens_at' => now()->addDay(),
            'closes_at' => now()->addMonth(),
            'timezone' => 'Asia/Jakarta',
            'privacy_notice' => 'Data fiktif digunakan untuk evaluasi mutu internal.',
            'reporting_threshold' => 10,
            'action_owner_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
