<?php

namespace Database\Factories;

use App\Models\SurveyPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SurveyPeriod> */
class SurveyPeriodFactory extends Factory
{
    protected $model = SurveyPeriod::class;

    public function definition(): array
    {
        $start = now()->startOfMonth();

        return ['code' => 'PER-'.$this->faker->unique()->numerify('#####'), 'name' => $this->faker->sentence(3), 'starts_on' => $start, 'ends_on' => $start->copy()->addMonths(5), 'timezone' => 'Asia/Jakarta', 'status' => 'active'];
    }
}
