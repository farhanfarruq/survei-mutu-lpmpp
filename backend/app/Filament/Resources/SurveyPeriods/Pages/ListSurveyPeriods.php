<?php

namespace App\Filament\Resources\SurveyPeriods\Pages;

use App\Filament\Resources\SurveyPeriods\SurveyPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSurveyPeriods extends ListRecords
{
    protected static string $resource = SurveyPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
