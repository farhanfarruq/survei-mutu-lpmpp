<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Collection;

final class SurveyAnalytics
{
    public function __construct(private readonly DeterministicStatistics $statistics) {}

    public function inputHash(Survey $survey): string
    {
        $survey->loadMissing('instrumentVersion');
        $responses = $survey->responses()->where('state', 'submitted')->with('answers')->orderBy('id')->get();
        $payload = [
            'survey' => $survey->only(['id', 'population_snapshot_hash', 'policy_snapshot', 'reporting_threshold']),
            'instrument' => [$survey->instrument_version_id, $survey->instrumentVersion->content_hash],
            'responses' => $responses->map(fn ($response) => [
                $response->id,
                $response->submitted_at?->toJSON(),
                $response->answers->sortBy('question_id')->map(fn ($answer) => [$answer->question_id, $answer->value, $answer->updated_at?->toJSON()])->values()->all(),
            ])->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function compute(Survey $survey, string $inputHash): array
    {
        $survey->load([
            'instrumentVersion.template',
            'instrumentVersion.sections.questions.scale.points',
            'instrumentVersion.sections.questions.options',
            'instrumentVersion.categories.indicators.questions.scale.points',
            'targets', 'ownerUnit', 'period',
            'responses' => fn ($query) => $query->where('state', 'submitted')->with('answers'),
        ]);

        $questions = $survey->instrumentVersion->sections->flatMap->questions->keyBy('id');
        $responses = $survey->responses;
        $responseCount = $responses->count();
        $eligibleCount = (int) $survey->targets->sum('eligible_count');
        $threshold = max(10, (int) $survey->reporting_threshold);
        $overallSuppressed = $responseCount < $threshold;
        $answers = [];
        $numeric = [];
        $categorical = [];
        $naCounts = [];

        foreach ($responses as $response) {
            foreach ($response->answers as $answer) {
                $question = $questions->get($answer->question_id);
                if (! $question) {
                    continue;
                }
                $value = $this->numericValue($question, $answer->value);
                if ($value === 'na') {
                    $naCounts[$question->id] = ($naCounts[$question->id] ?? 0) + 1;
                } elseif ($value !== null) {
                    $answers[$response->id][$question->id] = $value;
                    $numeric[$question->id][] = $value;
                } elseif (in_array($question->response_type, ['single_choice', 'multiple_choice'], true)) {
                    $selected = is_array($answer->value) ? $answer->value : [$answer->value];
                    $categorical[$question->id][$response->id] = array_values(array_map('strval', $selected));
                }
            }
        }

        $items = [];
        foreach ($questions as $question) {
            if (in_array($question->response_type, ['single_choice', 'multiple_choice'], true)) {
                $rows = $categorical[$question->id] ?? [];
                $suppressed = count($rows) < $threshold;
                $distribution = [];
                if (! $suppressed) {
                    foreach ($question->options as $option) {
                        $count = count(array_filter($rows, fn (array $selected) => in_array((string) $option->code, $selected, true)));
                        $distribution[] = ['value' => $option->code, 'label' => $option->label, 'count' => $count, 'percentage' => round(100 * $count / count($rows), 1, PHP_ROUND_HALF_UP)];
                    }
                }
                $items[] = ['id' => $question->id, 'code' => $question->code, 'text' => $question->item_text, 'method' => $question->method, 'n' => count($rows), 'missing' => $responseCount - count($rows), 'na' => 0, 'suppressed' => $suppressed, 'distribution' => $distribution, 'median' => null, 'mode' => [], 'mean' => null, 'sd' => null, 'top_two_box' => null, 'normalized_score' => null, 'interpretation' => null];

                continue;
            }
            if (! in_array($question->response_type, ['scale', 'number'], true)) {
                continue;
            }
            $bounds = $this->bounds($question);
            if (! $bounds) {
                continue;
            }
            [$lower, $upper, $ordered] = $bounds;
            $values = $numeric[$question->id] ?? [];
            $n = count($values);
            $suppressed = $n < $threshold;
            $result = $this->statistics->describe($values, $lower, $upper, $ordered);
            $items[] = array_merge([
                'id' => $question->id,
                'code' => $question->code,
                'text' => $question->item_text,
                'method' => $question->method,
                'n' => $n,
                'missing' => max(0, $responseCount - $n - ($naCounts[$question->id] ?? 0)),
                'na' => $naCounts[$question->id] ?? 0,
                'suppressed' => $suppressed,
            ], $suppressed ? $this->suppressedDescription() : $result);
        }

        $indicators = $this->dimensionMetrics(
            $survey->instrumentVersion->categories->flatMap->indicators,
            fn ($indicator) => $indicator->questions,
            $answers,
            $responseCount,
            $threshold
        );
        $categories = $this->dimensionMetrics(
            $survey->instrumentVersion->categories,
            fn ($category) => $category->indicators->flatMap->questions,
            $answers,
            $responseCount,
            $threshold
        );

        $overallValues = $this->respondentDimensionValues($questions, $answers);
        $overall = count($overallValues) < $threshold
            ? array_merge(['n' => count($overallValues), 'suppressed' => true], $this->suppressedDescription(false))
            : array_merge(['suppressed' => false], $this->statistics->describe($overallValues, 0, 100, [0, 20, 40, 60, 80, 100]));

        $special = $this->specialMethods($questions, $answers, $threshold);
        $reliability = $this->reliability($survey, $answers);
        $limitations = array_values(array_filter([
            $overallSuppressed ? "Jumlah respons di bawah ambang pelaporan {$threshold}; hasil disembunyikan." : null,
            $responseCount < 30 ? 'n < 30: statistik deskriptif saja; perbandingan dan tren tidak diinterpretasikan.' : null,
            'Dimensi kelompok responden tidak tersedia pada payload jawaban anonim; sistem tidak menghubungkan identitas dengan isi respons.',
        ]));

        $metrics = [
            'methodology_version' => DeterministicStatistics::VERSION,
            'response_rate' => [
                'submitted' => $responseCount,
                'eligible' => $eligibleCount,
                'percentage' => $eligibleCount > 0 ? round(100 * $responseCount / $eligibleCount, 1, PHP_ROUND_HALF_UP) : null,
            ],
            'overall' => $overall,
            'categories' => $categories,
            'indicators' => $indicators,
            'items' => $items,
            'reliability' => $reliability,
            'special_methods' => $special,
            'comparison_eligible' => ! $overallSuppressed && $responseCount >= 30,
        ];
        $provenance = [
            'survey_id' => $survey->id,
            'survey_code' => $survey->code,
            'instrument_version_id' => $survey->instrument_version_id,
            'instrument_content_hash' => $survey->instrumentVersion->content_hash,
            'instrument_template_id' => $survey->instrumentVersion->survey_template_id,
            'comparability_status' => $survey->instrumentVersion->comparability_status,
            'unit_id' => $survey->owner_unit_id,
            'period_id' => $survey->survey_period_id,
            'input_hash' => $inputHash,
            'formula_version' => DeterministicStatistics::VERSION,
            'filters' => ['response_state' => 'submitted'],
        ];

        return compact('metrics', 'provenance', 'limitations', 'responseCount', 'eligibleCount', 'threshold', 'overallSuppressed');
    }

    private function dimensionMetrics(Collection $dimensions, callable $questionsFor, array $answers, int $responseCount, int $threshold): array
    {
        return $dimensions->map(function ($dimension) use ($questionsFor, $answers, $responseCount, $threshold): array {
            $questions = collect($questionsFor($dimension))->filter(fn ($question) => in_array($question->response_type, ['scale', 'number'], true));
            $values = $this->respondentDimensionValues($questions, $answers);
            $suppressed = count($values) < $threshold;

            return array_merge([
                'id' => $dimension->id,
                'code' => $dimension->code,
                'name' => $dimension->name,
                'n' => count($values),
                'missing' => max(0, $responseCount - count($values)),
                'suppressed' => $suppressed,
            ], $suppressed ? $this->suppressedDescription(false) : $this->statistics->describe($values, 0, 100, [0, 20, 40, 60, 80, 100]));
        })->values()->all();
    }

    private function respondentDimensionValues(Collection $questions, array $answers): array
    {
        $questions = $questions->filter(fn ($question) => $this->bounds($question) !== null)->values();
        $required = (int) ceil(.8 * $questions->count());
        if ($required === 0) {
            return [];
        }
        $values = [];
        foreach ($answers as $responseAnswers) {
            $row = [];
            foreach ($questions as $question) {
                if (! array_key_exists($question->id, $responseAnswers)) {
                    continue;
                }
                [$lower, $upper] = $this->bounds($question);
                $row[] = $this->statistics->normalize($responseAnswers[$question->id], $lower, $upper);
            }
            if (count($row) >= $required) {
                $values[] = array_sum($row) / count($row);
            }
        }

        return $values;
    }

    private function reliability(Survey $survey, array $answers): array
    {
        $result = [];
        foreach ($survey->instrumentVersion->categories->flatMap->indicators as $indicator) {
            $questions = $indicator->questions->filter(fn ($question) => $this->bounds($question) !== null)->values();
            if (! str_contains(strtolower((string) $indicator->construct), 'reflective')) {
                $result[] = ['indicator_id' => $indicator->id, 'code' => $indicator->code, 'status' => 'not_applicable_non_reflective'];

                continue;
            }
            $rows = [];
            foreach ($answers as $responseAnswers) {
                $rows[] = $questions->map(fn ($question) => $responseAnswers[$question->id] ?? null)->all();
            }
            $result[] = array_merge(['indicator_id' => $indicator->id, 'code' => $indicator->code], $this->statistics->cronbachAlpha($rows));
        }

        return $result;
    }

    private function specialMethods(Collection $questions, array $answers, int $threshold): array
    {
        $pairs = $questions->filter(fn ($question) => filled($question->pair_code))->groupBy('pair_code');
        $servqualGaps = [];
        $ipa = [];
        foreach ($pairs as $pairCode => $pair) {
            $expectation = $pair->first(fn ($question) => $this->role($question) === 'expectation');
            $importance = $pair->first(fn ($question) => $this->role($question) === 'importance');
            $performance = $pair->first(fn ($question) => $this->role($question) === 'performance');
            if ($expectation && $performance) {
                foreach ($answers as $row) {
                    if (isset($row[$expectation->id], $row[$performance->id])) {
                        $servqualGaps[] = $row[$performance->id] - $row[$expectation->id];
                    }
                }
            }
            if ($importance && $performance) {
                $i = array_values(array_filter(array_map(fn ($row) => $row[$importance->id] ?? null, $answers), fn ($value) => $value !== null));
                $p = array_values(array_filter(array_map(fn ($row) => $row[$performance->id] ?? null, $answers), fn ($value) => $value !== null));
                $n = min(count($i), count($p));
                if ($n >= $threshold) {
                    $meanI = array_sum($i) / count($i);
                    $meanP = array_sum($p) / count($p);
                    $ipa[] = ['pair_code' => $pairCode, 'n' => $n, 'importance' => round($meanI, 2, PHP_ROUND_HALF_UP), 'performance' => round($meanP, 2, PHP_ROUND_HALF_UP), 'quadrant' => $this->statistics->ipa($meanI, $meanP)];
                }
            }
        }

        $servqualBounds = $pairs->flatten()->first(fn ($question) => $this->role($question) === 'expectation');
        $bounds = $servqualBounds ? $this->bounds($servqualBounds) : null;
        $servqual = count($servqualGaps) >= $threshold && $bounds
            ? $this->statistics->servqual($servqualGaps, $bounds[0], $bounds[1])
            : ['status' => 'prerequisites_not_met', 'n' => count($servqualGaps)];

        $csi = ['status' => 'prerequisites_not_met'];
        if ($ipa !== []) {
            $score = $this->statistics->csi(array_column($ipa, 'importance'), array_map(fn ($point) => $this->statistics->normalize($point['performance'], 1, 5), $ipa));
            if ($score !== null) {
                $csi = ['status' => 'calculated', 'label' => 'CSI internal', 'score' => $score];
            }
        }

        $ikmQuestions = $questions->filter(fn ($question) => preg_match('/(?:^|\W)(?:ikm|skm)(?:\W|$)/i', (string) $question->method) && ($bounds = $this->bounds($question)) && $bounds[0] === 1.0 && $bounds[1] === 4.0)->groupBy('indicator_id');
        $ikmMeans = [];
        foreach ($ikmQuestions as $group) {
            $values = [];
            foreach ($answers as $row) {
                $answered = $group->filter(fn ($question) => array_key_exists($question->id, $row))->map(fn ($question) => $row[$question->id]);
                if ($answered->count() >= ceil(.8 * $group->count())) {
                    $values[] = $answered->average();
                }
            }
            if (count($values) >= $threshold) {
                $ikmMeans[] = array_sum($values) / count($values);
            }
        }
        $ikm = count($ikmMeans) === 9 ? $this->statistics->ikm($ikmMeans) : ['status' => 'prerequisites_not_met'];

        $servperfQuestions = $questions->filter(fn ($question) => str_contains(strtolower((string) $question->method), 'servperf'));
        $servperfValues = $this->respondentDimensionValues($servperfQuestions, $answers);
        $servperf = count($servperfValues) >= $threshold
            ? ['status' => 'calculated', 'n' => count($servperfValues), 'score' => round(array_sum($servperfValues) / count($servperfValues), 2, PHP_ROUND_HALF_UP), 'label' => 'SERVPERF']
            : ['status' => 'prerequisites_not_met', 'n' => count($servperfValues)];

        return ['servperf' => $servperf, 'servqual' => $servqual, 'ipa' => $ipa === [] ? ['status' => 'prerequisites_not_met', 'points' => []] : ['status' => 'calculated', 'crosshair' => ['importance' => 4, 'performance' => 4], 'points' => $ipa], 'csi' => $csi, 'ikm' => $ikm];
    }

    private function role($question): ?string
    {
        $text = strtolower(implode(' ', [(string) $question->method, (string) $question->measurement_purpose, (string) $question->code]));
        if (preg_match('/importance|kepentingan/', $text)) {
            return 'importance';
        }
        if (preg_match('/expectation|harapan|ekspektasi/', $text)) {
            return 'expectation';
        }
        if (preg_match('/performance|perception|persepsi|kinerja|servperf/', $text)) {
            return 'performance';
        }
        if (preg_match('/[-_.]i$/', $text)) {
            return 'importance';
        }
        if (preg_match('/[-_.]e$/', $text)) {
            return 'expectation';
        }
        if (preg_match('/[-_.]p$/', $text)) {
            return 'performance';
        }

        return null;
    }

    private function numericValue($question, mixed $answer): float|string|null
    {
        if ($question->response_type === 'number') {
            return is_numeric($answer) ? (float) $answer : null;
        }
        if ($question->response_type !== 'scale') {
            return null;
        }
        $point = $question->scale?->points->first(fn ($candidate) => (string) $candidate->code === (string) $answer);
        if (! $point) {
            return null;
        }

        return $point->is_na ? 'na' : (float) $point->numeric_value;
    }

    private function bounds($question): ?array
    {
        if ($question->response_type === 'number') {
            $rules = $question->validation_rules ?? [];

            return isset($rules['min'], $rules['max']) && $rules['max'] > $rules['min'] ? [(float) $rules['min'], (float) $rules['max'], []] : null;
        }
        if ($question->response_type !== 'scale' || ! $question->scale) {
            return null;
        }
        $ordered = $question->scale->points->reject->is_na->pluck('numeric_value')->map(fn ($value) => (float) $value)->all();

        return [(float) $question->scale->min_value, (float) $question->scale->max_value, $ordered];
    }

    private function suppressedDescription(bool $withDistribution = true): array
    {
        return array_merge($withDistribution ? ['distribution' => []] : [], ['median' => null, 'mode' => [], 'mean' => null, 'sd' => null, 'top_two_box' => null, 'normalized_score' => null, 'interpretation' => null]);
    }
}
