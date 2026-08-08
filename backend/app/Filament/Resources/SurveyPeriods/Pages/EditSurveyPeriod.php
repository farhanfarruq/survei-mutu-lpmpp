<?php

namespace App\Filament\Resources\SurveyPeriods\Pages;

use App\Filament\Resources\SurveyPeriods\SurveyPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSurveyPeriod extends EditRecord
{
    protected static string $resource = SurveyPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
