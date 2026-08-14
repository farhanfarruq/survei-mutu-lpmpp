<?php

namespace App\Filament\Widgets;

use App\Models\AggregateSnapshot;
use App\Services\OrganizationalScope;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class QualityTrendChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Tren indeks mutu';

    protected ?string $description = 'Perkembangan skor agregat released pada instrumen yang sebanding.';

    protected ?string $maxHeight = '310px';

    protected function getData(): array
    {
        $unitIds = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());
        $snapshots = AggregateSnapshot::query()
            ->with(['period'])
            ->where('state', 'released')
            ->where('suppressed', false)
            ->whereIn('owner_unit_id', $unitIds)
            ->latest('generated_at')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        if ($snapshots->isEmpty()) {
            return [];
        }

        return [
            'datasets' => [[
                'label' => 'Indeks mutu',
                'data' => $snapshots->map(fn (AggregateSnapshot $snapshot) => (float) data_get($snapshot->metrics, 'overall.normalized_score'))->all(),
                'borderColor' => '#0369a1',
                'backgroundColor' => 'rgba(14, 165, 233, 0.18)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $snapshots->map(fn (AggregateSnapshot $snapshot) => data_get($snapshot, 'period.name') ?: Carbon::parse($snapshot->generated_at)->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return ['maintainAspectRatio' => false, 'scales' => ['y' => ['min' => 0, 'max' => 100]]];
    }
}
