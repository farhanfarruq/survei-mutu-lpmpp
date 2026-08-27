<?php

namespace App\Filament\Resources\QuestionBankEntries\Pages;

use App\Filament\Resources\QuestionBankEntries\QuestionBankEntryResource;
use App\Models\QuestionBankEntry;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestionBankEntry extends EditRecord
{
    protected static string $resource = QuestionBankEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(function (): bool {
            /** @var QuestionBankEntry $record */
            $record = $this->record;

            return ! $record->questions()->exists();
        })];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return QuestionBankEntryResource::normalizeFormData($data);
    }
}
