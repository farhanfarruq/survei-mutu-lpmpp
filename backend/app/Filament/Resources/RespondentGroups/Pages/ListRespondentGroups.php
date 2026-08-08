<?php

namespace App\Filament\Resources\RespondentGroups\Pages;

use App\Filament\Resources\RespondentGroups\RespondentGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRespondentGroups extends ListRecords
{
    protected static string $resource = RespondentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
