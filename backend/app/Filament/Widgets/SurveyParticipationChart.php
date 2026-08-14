<?php

namespace App\Filament\Widgets;

use App\Models\Survey;
use App\Services\OrganizationalScope;
use Filament\Widgets\ChartWidget;

class SurveyParticipationChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Partisipasi per survei';

    protected ?string $description = 'Perbandingan jumlah respons final dengan populasi eligible.';

    protected ?string $maxHeight = '310px';

    protected function getData(): array
    {
        $unitIds = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());
        $surveys = Survey::query()
            ->whereIn('owner_unit_id', $unitIds)
            ->whereIn('state', ['active', 'closed'])
            ->with('period')
            ->withSum('targets', 'eligible_count')
            ->latest('opens_at')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        if ($surveys->isEmpty()) {
            return [];
        }

        return [
            'datasets' => [
                ['label' => 'Respons final', 'data' => $surveys->pluck('responses_count')->map(fn ($value) => (int) $value)->all(), 'backgroundColor' => '#0284c7', 'borderRadius' => 6],
                ['label' => 'Eligible', 'data' => $surveys->pluck('targets_sum_eligible_count')->map(fn ($value) => (int) $value)->all(), 'backgroundColor' => '#cbd5e1', 'borderRadius' => 6],
            ],
            'labels' => $surveys->map(fn (Survey $survey) => data_get($survey, 'period.name') ?: $survey->name)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['maintainAspectRatio' => false, 'scales' => ['y' => ['beginAtZero' => true]]];
    }
}
