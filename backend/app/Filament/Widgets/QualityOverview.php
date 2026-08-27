<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\FollowUpAction;
use App\Models\Survey;
use App\Services\OrganizationalScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QualityOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected ?string $heading = 'Perlu Anda tangani';

    protected ?string $description = 'Pekerjaan operasional dari unit yang dapat Anda kelola.';

    protected function getStats(): array
    {
        $unitIds = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());
        $surveyCounts = Survey::query()
            ->whereIn('owner_unit_id', $unitIds)
            ->whereIn('state', ['draft', 'returned', 'in_review', 'approved'])
            ->selectRaw('state, count(*) as total')
            ->groupBy('state')
            ->pluck('total', 'state');
        $needsCompletion = (int) ($surveyCounts['draft'] ?? 0) + (int) ($surveyCounts['returned'] ?? 0);
        $needsReview = (int) ($surveyCounts['in_review'] ?? 0);
        $readyToPublish = (int) ($surveyCounts['approved'] ?? 0);
        $overdue = FollowUpAction::query()
            ->whereHas('finding', fn ($query) => $query->whereIn('owner_unit_id', $unitIds))
            ->whereDate('due_on', '<', today())
            ->whereNotIn('state', ['verified', 'rejected'])
            ->count();

        return [
            Stat::make('Perlu dilengkapi', $needsCompletion)
                ->description('Draf atau survei yang dikembalikan')
                ->color($needsCompletion > 0 ? 'warning' : 'success')
                ->url(SurveyResource::getUrl()),
            Stat::make('Menunggu pemeriksaan', $needsReview)
                ->description('Perlu keputusan reviewer')
                ->color($needsReview > 0 ? 'info' : 'success')
                ->url(SurveyResource::getUrl()),
            Stat::make('Siap diterbitkan', $readyToPublish)
                ->description('Sudah disetujui dan perlu dijadwalkan')
                ->color($readyToPublish > 0 ? 'primary' : 'success')
                ->url(SurveyResource::getUrl()),
            Stat::make('Tindak lanjut terlambat', $overdue)
                ->description($overdue > 0 ? 'Memerlukan perhatian dan eskalasi' : 'Seluruh tindak lanjut sesuai tenggat')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->url(rtrim((string) config('app.frontend_url'), '/').'/app/follow-up'),
        ];
    }
}
