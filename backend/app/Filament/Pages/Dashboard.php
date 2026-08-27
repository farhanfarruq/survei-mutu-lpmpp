<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Surveys\SurveyResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard Mutu';

    protected static ?string $navigationLabel = 'Dashboard Mutu';

    protected ?string $subheading = 'Tangani survei yang menunggu tindakan, pantau pelaksanaan, dan lanjutkan pekerjaan prioritas.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSurveyForm')
                ->label('Buat formulir survei')
                ->icon(Heroicon::OutlinedDocumentPlus)
                ->url(CreateSurveyForm::getUrl())
                ->visible(fn (): bool => CreateSurveyForm::canAccess()),
            Action::make('manageSurveys')
                ->label('Kelola survei')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('gray')
                ->url(SurveyResource::getUrl())
                ->visible(fn (): bool => SurveyResource::canViewAny()),
            Action::make('activityHistory')
                ->label('Riwayat aktivitas')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->url(ActivityResource::getUrl())
                ->visible(fn (): bool => ActivityResource::canViewAny()),
        ];
    }
}
