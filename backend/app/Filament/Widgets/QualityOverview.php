<?php

namespace App\Filament\Widgets;

use App\Models\AggregateSnapshot;
use App\Models\FollowUpAction;
use App\Models\Survey;
use App\Services\OrganizationalScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QualityOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Ringkasan periode berjalan';

    protected ?string $description = 'Seluruh angka merupakan data agregat dalam scope organisasi Anda.';

    protected function getStats(): array
    {
        $unitIds = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());
        $surveys = Survey::query()
            ->whereIn('owner_unit_id', $unitIds)
            ->whereIn('state', ['active', 'closed'])
            ->withSum('targets', 'eligible_count')
            ->get();
        $eligible = (int) $surveys->sum('targets_sum_eligible_count');
        $responses = (int) $surveys->sum('responses_count');
        $responseRate = $eligible > 0 ? round(100 * $responses / $eligible, 1) : 0;
        $latest = AggregateSnapshot::query()
            ->with(['survey', 'period'])
            ->where('state', 'released')
            ->where('suppressed', false)
            ->whereIn('owner_unit_id', $unitIds)
            ->latest('generated_at')
            ->first();
        $score = $latest ? (float) data_get($latest->metrics, 'overall.normalized_score', 0) : null;
        $overdue = FollowUpAction::query()
            ->whereHas('finding', fn ($query) => $query->whereIn('owner_unit_id', $unitIds))
            ->whereDate('due_on', '<', today())
            ->whereNotIn('state', ['verified', 'rejected'])
            ->count();

        return [
            Stat::make('Survei aktif', $surveys->where('state.value', 'active')->count())
                ->description('Campaign yang sedang menerima respons')
                ->color('primary'),
            Stat::make('Tingkat respons', number_format($responseRate, 1, ',', '.').' %')
                ->description(number_format($responses, 0, ',', '.').' dari '.number_format($eligible, 0, ',', '.').' target')
                ->color($responseRate >= 70 ? 'success' : 'warning'),
            Stat::make('Indeks mutu terbaru', $score === null ? 'Belum tersedia' : number_format($score, 1, ',', '.'))
                ->description($latest ? data_get($latest, 'period.name').' · '.data_get($latest, 'survey.name') : 'Belum ada snapshot released')
                ->color($score !== null && $score >= 80 ? 'success' : 'warning'),
            Stat::make('Tindak lanjut terlambat', $overdue)
                ->description($overdue > 0 ? 'Memerlukan perhatian dan eskalasi' : 'Seluruh tindak lanjut sesuai tenggat')
                ->color($overdue > 0 ? 'danger' : 'success'),
        ];
    }
}
