<?php

namespace Database\Factories;

use App\Models\OrganizationalUnit;
use App\Models\QuestionBankEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuestionBankEntry> */
class QuestionBankEntryFactory extends Factory
{
    protected $model = QuestionBankEntry::class;

    public function definition(): array
    {
        return [
            'owner_unit_id' => OrganizationalUnit::factory(),
            'code' => 'QB-'.$this->faker->unique()->numerify('#####'),
            'family_code' => 'LAYANAN_AKADEMIK',
            'method' => 'SERVPERF',
            'category_label' => 'Layanan akademik',
            'indicator_label' => 'Kejelasan informasi',
            'item_text' => $this->faker->sentence(),
            'response_type' => 'scale',
            'measurement_purpose' => $this->faker->sentence(),
            'is_active' => true,
            'is_default' => false,
            'created_by' => User::factory(),
        ];
    }
}
