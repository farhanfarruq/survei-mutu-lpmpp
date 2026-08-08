<?php

namespace App\Services;

use App\Enums\InstrumentStatus;
use App\Models\Survey;

class SurveyPreflight
{
    /** @return list<string> */
    public function errors(Survey $survey): array
    {
        $survey->loadMissing(['instrumentVersion', 'period', 'targets']);
        $errors = [];

        if ($survey->instrumentVersion->status !== InstrumentStatus::Approved) {
            $errors[] = 'Versi instrumen harus approved.';
        }
        if ($survey->opens_at->greaterThanOrEqualTo($survey->closes_at)) {
            $errors[] = 'Waktu buka harus lebih awal dari waktu tutup.';
        }
        if (! in_array($survey->timezone, timezone_identifiers_list(), true)) {
            $errors[] = 'Timezone bukan identifier IANA yang valid.';
        }
        if (trim($survey->privacy_notice) === '') {
            $errors[] = 'Privacy notice wajib tersedia.';
        }
        if ($survey->reporting_threshold < 10) {
            $errors[] = 'Reporting threshold minimum baseline adalah 10.';
        }
        if ($survey->action_owner_id === null) {
            $errors[] = 'Pemilik tindak lanjut wajib ditetapkan.';
        }
        if ($survey->targets->isEmpty()) {
            $errors[] = 'Minimal satu target wajib tersedia.';
        }

        foreach ($survey->targets as $target) {
            $references = (int) ($target->respondent_group_id !== null) + (int) ($target->target_unit_id !== null);
            if ($references !== 1) {
                $errors[] = 'Setiap target harus merujuk tepat satu group atau unit.';
            }
        }

        return array_values(array_unique($errors));
    }
}
