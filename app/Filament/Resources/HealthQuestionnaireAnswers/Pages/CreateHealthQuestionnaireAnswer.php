<?php

namespace App\Filament\Resources\HealthQuestionnaireAnswers\Pages;

use App\Filament\Resources\HealthQuestionnaireAnswers\HealthQuestionnaireAnswerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHealthQuestionnaireAnswer extends CreateRecord
{
    protected static string $resource = HealthQuestionnaireAnswerResource::class;

    public function getTitle(): string
    {
        return __('menu.create_health_questionnaire_answer');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
