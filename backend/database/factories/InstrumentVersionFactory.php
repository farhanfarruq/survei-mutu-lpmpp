<?php

namespace Database\Factories;

use App\Enums\InstrumentStatus;
use App\Models\InstrumentVersion;
use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InstrumentVersion> */
class InstrumentVersionFactory extends Factory
{
    protected $model = InstrumentVersion::class;

    public function definition(): array
    {
        return [
            'survey_template_id' => SurveyTemplate::factory(),
            'major' => 1,
            'minor' => 0,
            'patch' => 0,
            'status' => InstrumentStatus::Draft,
            'comparability_status' => 'pending',
            'change_reason' => 'Versi awal fiktif untuk pengujian.',
            'created_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => InstrumentStatus::Approved, 'content_hash' => hash('sha256', 'fixture'), 'approved_at' => now()]);
    }
}
