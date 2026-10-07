<?php

namespace App\Filament\Resources\HealthQuestionnaires\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class HealthQuestionnairesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->label(__('user.question'))
                    ->limit(100)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('question_th')
                    ->label(__('user.question_th'))
                    ->limit(100)
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('app.is_active'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (Model $record) {
                        return $record->created_at->format('d-M-y');
                    }),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (Model $record) {
                        return $record->created_at->format('H:i:s');
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
