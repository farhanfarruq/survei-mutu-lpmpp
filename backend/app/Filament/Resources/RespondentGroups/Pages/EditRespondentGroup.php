<?php

namespace App\Filament\Resources\RespondentGroups\Pages;

use App\Filament\Resources\RespondentGroups\RespondentGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRespondentGroup extends EditRecord
{
    protected static string $resource = RespondentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
