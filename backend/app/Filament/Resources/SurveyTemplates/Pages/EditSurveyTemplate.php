<?php

namespace App\Filament\Resources\SurveyTemplates\Pages;

use App\Filament\Resources\SurveyTemplates\SurveyTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSurveyTemplate extends EditRecord
{
    protected static string $resource = SurveyTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
