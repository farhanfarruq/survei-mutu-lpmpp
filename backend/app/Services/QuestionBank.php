<?php

namespace App\Services;

use App\Exceptions\DomainRuleViolation;
use App\Models\Indicator;
use App\Models\InstrumentSection;
use App\Models\Question;
use App\Models\QuestionBankEntry;
use App\Models\Scale;

class QuestionBank
{
    public function addToSection(QuestionBankEntry $entry, InstrumentSection $section, Indicator $indicator, ?Scale $scale, string $code, int $position): Question
    {
        $versionId = $section->instrument_version_id;
        if ($indicator->category->instrument_version_id !== $versionId || ($scale && $scale->instrument_version_id !== $versionId)) {
            throw new DomainRuleViolation('question_reference_mismatch', 'Indikator/skala harus berasal dari versi instrumen yang sama.');
        }

        $question = $section->questions()->create([
            'indicator_id' => $indicator->id,
            'scale_id' => $scale?->id,
            'question_bank_entry_id' => $entry->id,
            'code' => $code,
            'item_text' => $entry->item_text,
            'response_type' => $entry->response_type,
            'is_required' => true,
            'position' => $position,
            'help_text' => $entry->help_text,
            'measurement_purpose' => $entry->measurement_purpose,
            'method' => $entry->method,
        ]);

        foreach ($entry->default_options ?? [] as $index => $option) {
            $question->options()->create([
                'code' => $option['code'], 'label' => $option['label'], 'position' => $index + 1,
                'score_value' => $option['score_value'] ?? null, 'is_exclusive' => $option['is_exclusive'] ?? false,
            ]);
        }

        return $question->refresh();
    }
}
