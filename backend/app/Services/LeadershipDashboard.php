<?php

namespace App\Services;

use App\Models\AggregateSnapshot;
use App\Models\User;

final class LeadershipDashboard
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function data(User $user, array $filters): array
    {
        $unitIds = $this->scope->accessibleUnitIds($user);
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
        $sameInstrument = $snapshots->pluck('survey.instrument_version_id')->unique()->count() === 1;
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
            'summary' => $latest ? [
                'survey' => $latest->survey->name,
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
            'drilldown' => ($filters['drilldown'] ?? null) === 'item' && $latest ? data_get($latest->metrics, 'items', []) : [],
            'filter_provenance' => ['requested' => $filters, 'effective_unit_ids' => $unitIds->values(), 'released_only' => true, 'suppression_applied' => true],
            'accessible_summary' => $latest
                ? sprintf('%s, %s. Skor keseluruhan %s dari 100 berdasarkan %d respons. Diperbarui %s.', $latest->survey->name, $latest->ownerUnit?->name, data_get($latest->metrics, 'overall.normalized_score', 'disembunyikan'), $latest->response_count, $latest->generated_at?->format('d-m-Y H:i'))
                : 'Belum ada hasil agregat yang dirilis untuk cakupan dan filter ini.',
        ];
    }
}
