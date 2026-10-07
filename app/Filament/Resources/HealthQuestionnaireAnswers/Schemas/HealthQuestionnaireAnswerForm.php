<?php

namespace App\Filament\Resources\HealthQuestionnaireAnswers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HealthQuestionnaireAnswerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('user.health_questionnaire_answer'))
                    ->description(__('user.questionnaire_answers'))
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('answer')
                            ->label(__('user.answer'))
                            ->rows(5)
                            ->cols(5)
                            ->required(),
                        Textarea::make('answer_th')
                            ->label(__('user.answer_th'))
                            ->rows(5)
                            ->cols(5),
                    ])
            ]);
    }
}
