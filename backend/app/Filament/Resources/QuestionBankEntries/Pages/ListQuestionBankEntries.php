<?php

namespace App\Filament\Resources\QuestionBankEntries\Pages;

use App\Filament\Resources\QuestionBankEntries\QuestionBankEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuestionBankEntries extends ListRecords
{
    protected static string $resource = QuestionBankEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
