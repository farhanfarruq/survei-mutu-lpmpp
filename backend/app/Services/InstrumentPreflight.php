<?php

namespace App\Services;

use App\Models\InstrumentVersion;

class InstrumentPreflight
{
    /** @return list<string> */
    public function errors(InstrumentVersion $version): array
    {
        $version->loadMissing(['sections.questions.options', 'categories.indicators', 'scales.points']);
        $errors = [];

        if ($version->sections->isEmpty()) {
            $errors[] = 'Minimal satu bagian wajib tersedia.';
        }
        if ($version->categories->isEmpty()) {
            $errors[] = 'Minimal satu kategori wajib tersedia.';
        }
        if ($version->categories->flatMap->indicators->isEmpty()) {
            $errors[] = 'Minimal satu indikator wajib tersedia.';
        }
        if ($version->scales->isEmpty()) {
            $errors[] = 'Minimal satu skala wajib tersedia.';
        }
        if ($version->sections->flatMap->questions->isEmpty()) {
            $errors[] = 'Minimal satu pertanyaan wajib tersedia.';
        }

        foreach ($version->scales as $scale) {
            if ($scale->points->where('is_na', false)->count() < 2) {
                $errors[] = "Skala {$scale->code} harus mempunyai minimal dua titik non-N/A.";
            }
            if ($scale->min_value !== null && $scale->max_value !== null && (float) $scale->min_value >= (float) $scale->max_value) {
                $errors[] = "Rentang skala {$scale->code} tidak valid.";
            }
        }

        $indicatorIds = $version->categories->flatMap->indicators->pluck('id');
        $scaleIds = $version->scales->pluck('id');

        foreach ($version->sections->flatMap->questions as $question) {
            if (! $indicatorIds->contains($question->indicator_id)) {
                $errors[] = "Indikator pertanyaan {$question->code} bukan milik versi ini.";
            }
            if ($question->response_type === 'scale' && ! $scaleIds->contains($question->scale_id)) {
                $errors[] = "Pertanyaan {$question->code} membutuhkan skala dari versi ini.";
            }
            if (in_array($question->response_type, ['single_choice', 'multiple_choice'], true) && $question->options->count() < 2) {
                $errors[] = "Pertanyaan {$question->code} membutuhkan minimal dua pilihan.";
            }
            if (preg_match('/<\s*script\b/i', $question->item_text)) {
                $errors[] = "Pertanyaan {$question->code} mengandung markup yang tidak diizinkan.";
            }
        }

        return array_values(array_unique($errors));
    }

    public function contentHash(InstrumentVersion $version): string
    {
        $version->loadMissing(['sections.questions.options', 'categories.indicators', 'scales.points']);

        return hash('sha256', json_encode([
            'version' => [$version->major, $version->minor, $version->patch, $version->comparability_status, $version->change_reason],
            'categories' => $version->categories->map(fn ($category) => [$category->code, $category->name, $category->position, $category->indicators->map(fn ($indicator) => [$indicator->code, $indicator->name, $indicator->construct, $indicator->weight])->values()])->values(),
            'scales' => $version->scales->map(fn ($scale) => [$scale->code, $scale->scale_type, $scale->min_value, $scale->max_value, $scale->na_allowed, $scale->missing_policy, $scale->points->map(fn ($point) => [$point->code, $point->numeric_value, $point->label, $point->position, $point->is_na, $point->is_neutral])->values()])->values(),
            'sections' => $version->sections->map(fn ($section) => [$section->code, $section->title, $section->position, $section->questions->map(fn ($question) => [$question->code, $question->indicator_id, $question->scale_id, $question->item_text, $question->response_type, $question->is_required, $question->position, $question->method, $question->pair_code, $question->options->map(fn ($option) => [$option->code, $option->label, $option->position, $option->score_value, $option->is_exclusive])->values()])->values()])->values(),
        ], JSON_THROW_ON_ERROR));
    }
}
