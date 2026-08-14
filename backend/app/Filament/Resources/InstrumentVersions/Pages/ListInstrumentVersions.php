<?php

namespace App\Filament\Resources\InstrumentVersions\Pages;

use App\Filament\Pages\CreateSurveyForm;
use App\Filament\Resources\InstrumentVersions\InstrumentVersionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListInstrumentVersions extends ListRecords
{
    protected static string $resource = InstrumentVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSimpleForm')
                ->label('Buat formulir')
                ->icon(Heroicon::OutlinedPlus)
                ->url(CreateSurveyForm::getUrl()),
        ];
    }
}
