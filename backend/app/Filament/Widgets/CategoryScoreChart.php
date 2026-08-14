<?php

namespace App\Filament\Widgets;

use App\Models\AggregateSnapshot;
use App\Services\OrganizationalScope;
use Filament\Widgets\ChartWidget;

class CategoryScoreChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Capaian per kategori';

    protected ?string $description = 'Kategori pada snapshot released terbaru; skala 0–100.';

    protected ?string $maxHeight = '310px';

    protected function getData(): array
    {
        $unitIds = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());
        $snapshot = AggregateSnapshot::query()
            ->where('state', 'released')
            ->where('suppressed', false)
            ->whereIn('owner_unit_id', $unitIds)
            ->latest('generated_at')
            ->first();
        $categories = collect(data_get($snapshot?->metrics, 'categories', []))->reject(fn (array $category) => $category['suppressed'] ?? false)->values();

        if ($categories->isEmpty()) {
            return [];
        }

        return [
            'datasets' => [[
                'label' => 'Skor kategori',
                'data' => $categories->pluck('normalized_score')->map(fn ($value) => (float) $value)->all(),
                'backgroundColor' => ['#0ea5e9', '#f59e0b', '#14b8a6', '#6366f1'],
                'borderRadius' => 6,
            ]],
            'labels' => $categories->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['indexAxis' => 'y', 'maintainAspectRatio' => false, 'scales' => ['x' => ['min' => 0, 'max' => 100]]];
    }
}
