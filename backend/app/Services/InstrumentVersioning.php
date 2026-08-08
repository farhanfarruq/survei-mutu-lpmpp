<?php

namespace App\Services;

use App\Enums\InstrumentStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\InstrumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InstrumentVersioning
{
    public function duplicate(InstrumentVersion $source, User $actor, string $bump, string $reason, string $comparability): InstrumentVersion
    {
        if (! in_array($bump, ['major', 'minor', 'patch'], true)) {
            throw new DomainRuleViolation('invalid_version_bump', 'Jenis version bump tidak valid.');
        }
        if (trim($reason) === '') {
            throw new DomainRuleViolation('change_reason_required', 'Alasan perubahan wajib diisi.');
        }

        return DB::transaction(function () use ($source, $actor, $bump, $reason, $comparability): InstrumentVersion {
            $source->load(['categories.indicators', 'scales.points', 'sections.questions.options']);
            [$major, $minor, $patch] = [$source->major, $source->minor, $source->patch];
            if ($bump === 'major') {
                [$major, $minor, $patch] = [$major + 1, 0, 0];
            }
            if ($bump === 'minor') {
                [$minor, $patch] = [$minor + 1, 0];
            }
            if ($bump === 'patch') {
                $patch++;
            }

            if (InstrumentVersion::query()->where('survey_template_id', $source->survey_template_id)->where(compact('major', 'minor', 'patch'))->exists()) {
                throw new DomainRuleViolation('instrument_version_exists', 'Nomor versi hasil bump sudah tersedia.');
            }

            $target = InstrumentVersion::create([
                'survey_template_id' => $source->survey_template_id, 'major' => $major, 'minor' => $minor, 'patch' => $patch,
                'status' => InstrumentStatus::Draft, 'comparability_status' => $comparability, 'change_reason' => trim($reason), 'created_by' => $actor->id,
            ]);

            $indicatorMap = [];
            foreach ($source->categories as $category) {
                $newCategory = $target->categories()->create($category->only(['code', 'name', 'description', 'position']));
                foreach ($category->indicators as $indicator) {
                    $newIndicator = $newCategory->indicators()->create($indicator->only(['code', 'name', 'construct', 'weight', 'interpretation']));
                    $indicatorMap[$indicator->id] = $newIndicator->id;
                }
            }

            $scaleMap = [];
            foreach ($source->scales as $scale) {
                $newScale = $target->scales()->create($scale->only(['code', 'name', 'scale_type', 'min_value', 'max_value', 'na_allowed', 'missing_policy']));
                foreach ($scale->points as $point) {
                    $newScale->points()->create($point->only(['code', 'numeric_value', 'label', 'position', 'is_na', 'is_neutral']));
                }
                $scaleMap[$scale->id] = $newScale->id;
            }

            foreach ($source->sections as $section) {
                $newSection = $target->sections()->create($section->only(['code', 'title', 'description', 'position', 'branch_rule']));
                foreach ($section->questions as $question) {
                    $data = $question->only(['question_bank_entry_id', 'code', 'item_text', 'response_type', 'is_required', 'position', 'help_text', 'validation_rules', 'branch_rule', 'measurement_purpose', 'method', 'pair_code']);
                    $data['indicator_id'] = $indicatorMap[$question->indicator_id];
                    $data['scale_id'] = $question->scale_id ? $scaleMap[$question->scale_id] : null;
                    $newQuestion = $newSection->questions()->create($data);
                    foreach ($question->options as $option) {
                        $newQuestion->options()->create($option->only(['code', 'label', 'position', 'score_value', 'is_exclusive']));
                    }
                }
            }

            activity('instrument')->performedOn($target)->causedBy($actor)->event('version_created')->withProperties(['source_version_id' => $source->id, 'bump' => $bump])->log('Instrument version duplicated');

            return $target->refresh();
        });
    }
}
