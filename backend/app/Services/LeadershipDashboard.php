<?php

namespace App\Services;

use App\Enums\SurveyState;
use App\Models\AggregateSnapshot;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class LeadershipDashboard
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function data(User $user, array $filters): array
    {
        $unitIds = $this->scope->accessibleUnitIds($user);
        $surveyOptions = Survey::query()
            ->leftJoin('organizational_units as survey_units', 'survey_units.id', '=', 'surveys.owner_unit_id')
            ->leftJoin('survey_periods as survey_periods_filter', 'survey_periods_filter.id', '=', 'surveys.survey_period_id')
            ->select([
                'surveys.*',
                'survey_units.name as unit_name',
                'survey_periods_filter.name as period_name',
            ])
            ->withExists(['aggregateSnapshots as has_released_result' => fn ($query) => $query->where('state', 'released')])
            ->whereIn('surveys.owner_unit_id', $unitIds)
            ->whereIn('surveys.state', [
                SurveyState::Scheduled->value,
                SurveyState::Active->value,
                SurveyState::Closed->value,
                SurveyState::Archived->value,
            ])
            ->latest('surveys.created_at')
            ->get()
            ->map(fn (Survey $survey): array => [
                'id' => $survey->id,
                'name' => $survey->name,
                'state' => $survey->state->value,
                'responses_count' => $survey->responses_count,
                'reporting_threshold' => $survey->reporting_threshold,
                'has_released_result' => (bool) $survey->has_released_result,
                'unit_id' => $survey->owner_unit_id,
                'unit' => $survey->unit_name,
                'period_id' => $survey->survey_period_id,
                'period' => $survey->period_name,
            ])
            ->values();
        $selectedSurvey = $surveyOptions->firstWhere('id', $filters['survey_id'] ?? null);
        $query = AggregateSnapshot::query()->with(['survey.instrumentVersion', 'ownerUnit', 'period', 'respondentGroup'])
            ->where('state', 'released')->whereIn('owner_unit_id', $unitIds);
        if ($filters['unit_id'] ?? null) {
            $query->where('owner_unit_id', $filters['unit_id']);
        }
        if ($filters['period_id'] ?? null) {
            $query->where('survey_period_id', $filters['period_id']);
        }
        if ($filters['survey_id'] ?? null) {
            $query->where('survey_id', $filters['survey_id']);
        }
        if ($filters['group_id'] ?? null) {
            $query->where('respondent_group_id', $filters['group_id']);
        }
        $snapshots = $query->latest('generated_at')->get();
        $latest = $snapshots->first();
        $comparable = $snapshots->filter(fn ($snapshot) => ! $snapshot->suppressed && $snapshot->response_count >= 30);
        $sameInstrument = $snapshots
            ->map(fn (AggregateSnapshot $snapshot) => $snapshot->survey->instrument_version_id)
            ->unique()
            ->count() === 1;
        $comparisonAllowed = $snapshots->isNotEmpty() && $comparable->count() === $snapshots->count() && $sameInstrument;
        $series = $snapshots->map(fn ($snapshot) => [
            'snapshot_id' => $snapshot->id,
            'survey_id' => $snapshot->survey_id,
            'survey' => $snapshot->survey->name,
            'unit_id' => $snapshot->owner_unit_id,
            'unit' => $snapshot->ownerUnit?->name,
            'period_id' => $snapshot->survey_period_id,
            'period' => $snapshot->period?->name,
            'group_id' => $snapshot->respondent_group_id,
            'group' => $snapshot->respondentGroup?->name,
            'n' => $snapshot->response_count,
            'score' => $comparisonAllowed ? data_get($snapshot->metrics, 'overall.normalized_score') : null,
            'comparison_eligible' => $snapshot->response_count >= 30,
            'last_updated_at' => $snapshot->generated_at?->toIso8601String(),
        ])->values();

        return [
            'survey_options' => $surveyOptions,
            'selected_survey' => $selectedSurvey,
            'summary' => $latest ? [
                'survey' => $latest->survey->name,
                'survey_id' => $latest->survey_id,
                'unit' => $latest->ownerUnit?->name,
                'period' => $latest->period?->name,
                'response_rate' => data_get($latest->metrics, 'response_rate'),
                'overall' => data_get($latest->metrics, 'overall'),
                'categories' => data_get($latest->metrics, 'categories', []),
                'limitations' => $latest->limitations ?? [],
                'last_updated_at' => $latest->generated_at?->toIso8601String(),
            ] : null,
            'comparison' => ['allowed' => $comparisonAllowed, 'minimum_n' => 30, 'series' => $series],
            'trend' => ['allowed' => $comparisonAllowed && $snapshots->count() > 1, 'series' => $series->sortBy('period')->values()],
            'drilldown' => ($filters['drilldown'] ?? null) === 'item' && $latest ? $this->items($latest) : [],
            'filter_provenance' => ['requested' => $filters, 'effective_unit_ids' => $unitIds->values(), 'released_only' => true, 'suppression_applied' => true],
            'accessible_summary' => $latest
                ? sprintf('%s, %s. Skor keseluruhan %s dari 100 berdasarkan %d respons. Diperbarui %s.', $latest->survey->name, $latest->ownerUnit?->name, data_get($latest->metrics, 'overall.normalized_score', 'disembunyikan'), $latest->response_count, $latest->generated_at?->format('d-m-Y H:i'))
                : 'Belum ada hasil agregat yang dirilis untuk cakupan dan filter ini.',
        ];
    }

    private function items(AggregateSnapshot $snapshot): array
    {
        $items = data_get($snapshot->metrics, 'items', []);
        if ($items === []) {
            return [];
        }

        $questions = DB::table('questions')
            ->join('instrument_sections', 'questions.section_id', '=', 'instrument_sections.id')
            ->join('indicators', 'questions.indicator_id', '=', 'indicators.id')
            ->join('categories', 'indicators.category_id', '=', 'categories.id')
            ->where('instrument_sections.instrument_version_id', $snapshot->survey->instrument_version_id)
            ->get([
                'questions.id', 'questions.code', 'questions.response_type', 'questions.scale_id',
                'indicators.name as indicator_name', 'categories.code as category_code',
                'categories.name as category_name',
            ])
            ->keyBy('code');
        $scaleLabels = DB::table('scale_points')
            ->whereIn('scale_id', $questions->pluck('scale_id')->filter()->unique())
            ->orderBy('position')
            ->get(['scale_id', 'numeric_value', 'label'])
            ->groupBy('scale_id')
            ->map(fn ($points) => $points->mapWithKeys(fn ($point) => [(string) (float) $point->numeric_value => $point->label]));

        return collect($items)->map(function (array $item) use ($questions, $scaleLabels, $snapshot): array {
            $question = $questions->get($item['code'] ?? '');
            $labels = $question?->scale_id ? ($scaleLabels->get($question->scale_id) ?? collect()) : collect();
            $distribution = collect($item['distribution'] ?? [])->map(fn (array $row): array => $row + [
                'label' => $labels->get((string) (float) ($row['value'] ?? 0), (string) ($row['value'] ?? '')),
            ])->values()->all();

            return array_merge([
                'id' => $question->id ?? ($item['id'] ?? $item['code']),
                'category_code' => $question?->category_code,
                'category_name' => $question->category_name ?? 'Tanpa kategori',
                'indicator_name' => $question?->indicator_name,
                'response_type' => $question?->response_type,
                'missing' => max(0, $snapshot->response_count - (int) ($item['n'] ?? 0)),
                'mean' => null,
                'distribution' => [],
            ], $item, ['distribution' => $distribution]);
        })->values()->all();
    }
}
