<?php

namespace App\Observers;

use App\Exceptions\DomainRuleViolation;
use App\Models\SurveyTarget;

class SurveyTargetObserver
{
    public function saving(SurveyTarget $target): void
    {
        $this->assertEditable($target);
    }

    public function deleting(SurveyTarget $target): void
    {
        $this->assertEditable($target);
    }

    private function assertEditable(SurveyTarget $target): void
    {
        $survey = $target->survey;

        if (! $survey->state->configurationEditable() || $survey->responses_count > 0) {
            throw new DomainRuleViolation('survey_targets_locked', 'Target survey terkunci setelah approval/publikasi atau setelah respons tersedia.');
        }
    }
}
