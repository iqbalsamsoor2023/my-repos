<?php

namespace App\Filament\Resources\HealthQuestionnaireAnswers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\HealthQuestionnaireAnswers\HealthQuestionnaireAnswerResource;
use Filament\Resources\Pages\ListRecords;

class ListHealthQuestionnaireAnswers extends ListRecords
{
    protected static string $resource = HealthQuestionnaireAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
