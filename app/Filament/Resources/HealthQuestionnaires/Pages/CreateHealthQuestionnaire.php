<?php

namespace App\Filament\Resources\HealthQuestionnaires\Pages;

use App\Filament\Resources\HealthQuestionnaires\HealthQuestionnaireResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHealthQuestionnaire extends CreateRecord
{
    protected static string $resource = HealthQuestionnaireResource::class;

    public function getTitle(): string
    {
        return __('menu.create_health_questionnaire');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
