<?php

namespace App\Filament\Resources\HealthQuestionnaireAnswers\Tables;

use App\Models\HealthQuestionnaireAnswer;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class HealthQuestionnaireAnswersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('answer')
                    ->label(__('user.answer'))
                    ->limit(100),
                TextColumn::make('answer_th')
                    ->label(__('user.answer_th'))
                    ->limit(100),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->date(),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (HealthQuestionnaireAnswer $record) {
                        return $record->created_at->format('H:i');
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
