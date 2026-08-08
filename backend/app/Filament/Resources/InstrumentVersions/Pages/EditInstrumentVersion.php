<?php

namespace App\Filament\Resources\InstrumentVersions\Pages;

use App\Filament\Resources\InstrumentVersions\InstrumentVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInstrumentVersion extends EditRecord
{
    protected static string $resource = InstrumentVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }
}
