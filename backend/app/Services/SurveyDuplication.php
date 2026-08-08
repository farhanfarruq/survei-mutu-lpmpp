<?php

namespace App\Services;

use App\Enums\SurveyState;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SurveyDuplication
{
    public function duplicate(Survey $source, User $actor, string $code, string $name): Survey
    {
        return DB::transaction(function () use ($source, $actor, $code, $name): Survey {
            $source->load('targets');
            $target = Survey::create([
                ...$source->only(['instrument_version_id', 'survey_period_id', 'owner_unit_id', 'privacy_mode', 'opens_at', 'closes_at', 'timezone', 'privacy_notice', 'reporting_threshold', 'action_owner_id']),
                'code' => $code,
                'name' => $name,
                'state' => SurveyState::Draft,
                'created_by' => $actor->id,
            ]);

            foreach ($source->targets as $surveyTarget) {
                $target->targets()->create($surveyTarget->only(['respondent_group_id', 'target_unit_id', 'target_type', 'eligible_count', 'sampling', 'frame_checksum']));
            }

            activity('survey')->performedOn($target)->causedBy($actor)->event('duplicated')->withProperties(['source_survey_id' => $source->id])->log('Survey duplicated');

            return $target->refresh();
        });
    }
}
