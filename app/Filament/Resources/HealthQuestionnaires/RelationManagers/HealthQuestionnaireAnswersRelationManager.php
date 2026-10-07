<?php

namespace App\Filament\Resources\HealthQuestionnaires\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use App\Filament\Resources\HealthQuestionnaireAnswers\HealthQuestionnaireAnswerResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class HealthQuestionnaireAnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'healthQuestionnaireAnswers';

    protected static ?string $recordTitleAttribute = 'answer';

    public function form(Schema $schema): Schema
    {
        return HealthQuestionnaireAnswerResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = HealthQuestionnaireAnswerResource::table($table);

        return $resource_table
                ->headerActions([
                    CreateAction::make()
                        ->label(__('menu.new_health_questionnaire_answer')),
                ]);
    }
}
