<?php

namespace App\Filament\Resources\HealthQuestionnaires\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\HealthQuestionnaires\HealthQuestionnaireResource;
use Filament\Resources\Pages\ListRecords;

class ListHealthQuestionnaires extends ListRecords
{
    protected static string $resource = HealthQuestionnaireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_health_questionnaire')),
        ];
    }
}
