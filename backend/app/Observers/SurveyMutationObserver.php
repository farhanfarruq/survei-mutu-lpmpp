<?php

namespace App\Observers;

use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Models\Survey;

class SurveyMutationObserver
{
    private const PROTECTED_FIELDS = [
        'instrument_version_id', 'survey_period_id', 'owner_unit_id', 'code', 'privacy_mode', 'opens_at',
        'closes_at', 'timezone', 'privacy_notice', 'reporting_threshold', 'action_owner_id',
    ];

    public function updating(Survey $survey): void
    {
        $originalState = SurveyState::from($survey->getRawOriginal('state'));

        if ((! $originalState->configurationEditable() || (int) $survey->getRawOriginal('responses_count') > 0)
            && $survey->isDirty(self::PROTECTED_FIELDS)) {
            throw new DomainRuleViolation('survey_configuration_locked', 'Konfigurasi survey terkunci setelah approval/publikasi atau setelah respons tersedia.');
        }
    }

    public function deleting(Survey $survey): void
    {
        if (! $survey->state->configurationEditable() || $survey->responses_count > 0) {
            throw new DomainRuleViolation('survey_in_use', 'Survey hanya dapat dihapus saat draft/returned dan belum memiliki respons.');
        }
    }
}
