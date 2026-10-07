<?php

namespace App\Filament\Resources\HealthQuestionnaireAnswers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\HealthQuestionnaireAnswers\HealthQuestionnaireAnswerResource;
use Filament\Resources\Pages\EditRecord;

class EditHealthQuestionnaireAnswer extends EditRecord
{
    protected static string $resource = HealthQuestionnaireAnswerResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_health_questionnaire_and_answer');
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
