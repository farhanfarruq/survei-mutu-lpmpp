<?php

namespace App\Filament\Resources\QuestionBankEntries\Pages;

use App\Filament\Resources\QuestionBankEntries\QuestionBankEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestionBankEntry extends EditRecord
{
    protected static string $resource = QuestionBankEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
