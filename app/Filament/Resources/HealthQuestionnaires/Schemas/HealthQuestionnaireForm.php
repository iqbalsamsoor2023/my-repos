<?php

namespace App\Filament\Resources\HealthQuestionnaires\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HealthQuestionnaireForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('user.health_questionnaire'))
                    ->description(__('user.questionnaires'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Textarea::make('question')
                            ->label(__('user.question'))
                            ->rows(10)
                            ->cols(20)
                            ->required(),
                        Textarea::make('question_th')
                            ->label(__('user.question_th'))
                            ->rows(10)
                            ->cols(20),
                        Toggle::make('is_active')
                            ->label(__('app.is_active'))
                            ->inline(false)
                            ->default(true),
                    ])
            ]);
    }
}
