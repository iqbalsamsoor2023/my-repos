<?php

namespace App\Filament\Resources\HealthQuestionnaires\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\HealthQuestionnaires\HealthQuestionnaireResource;
use Filament\Resources\Pages\EditRecord;

class EditHealthQuestionnaire extends EditRecord
{
    protected static string $resource = HealthQuestionnaireResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_health_questionnaire');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
