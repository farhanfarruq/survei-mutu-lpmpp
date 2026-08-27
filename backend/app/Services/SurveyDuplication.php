<?php

namespace App\Services;

use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Models\InstrumentVersion;
use App\Models\Survey;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveyDuplication
{
    public function __construct(private readonly InstrumentVersioning $versions) {}

    public function duplicate(Survey $source, User $actor, string $code, string $name): Survey
    {
        return DB::transaction(function () use ($source, $actor, $code, $name): Survey {
            return $this->copy($source, $actor, $code, $name, [], 'duplicated');
        });
    }

    public function revise(Survey $source, User $actor, array $data): Survey
    {
        $opensAt = CarbonImmutable::parse($data['opens_at'] ?? '');
        $closesAt = CarbonImmutable::parse($data['closes_at'] ?? '');
        if (blank($data['name'] ?? null) || ! $opensAt->isFuture() || $closesAt->lessThanOrEqualTo($opensAt)) {
            throw new DomainRuleViolation('survey_revision_invalid', 'Nama dan jadwal baru wajib diisi; jadwal harus berada di masa depan.');
        }

        return DB::transaction(function () use ($source, $actor, $data, $opensAt, $closesAt): Survey {
            [$bump, $comparability] = match ($data['change_type']) {
                'small' => ['patch', 'comparable'],
                'major' => ['major', 'not_comparable'],
                default => ['minor', 'partial'],
            };
            $instrument = $this->versions->duplicate(InstrumentVersion::query()->findOrFail($source->instrument_version_id), $actor, $bump, $data['reason'], $comparability);

            return $this->copy($source, $actor, 'SURVEI-'.Str::upper((string) Str::uuid()), $data['name'], [
                'instrument_version_id' => $instrument->id,
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
                'action_owner_id' => $actor->id,
            ], 'revision_created');
        });
    }

    private function copy(Survey $source, User $actor, string $code, string $name, array $overrides, string $event): Survey
    {
        $source->load('targets');
        $target = Survey::create([
            ...$source->only(['instrument_version_id', 'survey_period_id', 'owner_unit_id', 'privacy_mode', 'opens_at', 'closes_at', 'timezone', 'privacy_notice', 'reporting_threshold', 'action_owner_id']),
            ...$overrides,
            'code' => $code,
            'name' => $name,
            'state' => SurveyState::Draft,
            'created_by' => $actor->id,
        ]);

        foreach ($source->targets as $surveyTarget) {
            $target->targets()->create($surveyTarget->only(['respondent_group_id', 'target_unit_id', 'target_type', 'eligible_count', 'sampling', 'frame_checksum']));
        }

        activity('survey')->performedOn($target)->causedBy($actor)->event($event)->withProperties(['source_survey_id' => $source->id])->log($event === 'revision_created' ? 'Revisi survei dibuat' : 'Survey duplicated');

        return $target->refresh();
    }
}
