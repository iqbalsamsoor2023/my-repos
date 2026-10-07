<?php

namespace App\Filament\Resources\MaintenanceProgressions\Tables;

use App\Models\MaintenanceProgression;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class MaintenanceProgressionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('progress_description')
                    ->label(__('maintenance.progress_description'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->date(),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (MaintenanceProgression $record) {
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
            ->headerActions([
                CreateAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
