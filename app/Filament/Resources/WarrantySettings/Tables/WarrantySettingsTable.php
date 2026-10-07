<?php

namespace App\Filament\Resources\WarrantySettings\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class WarrantySettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.residence'))
                    ->sortable(),
                IconColumn::make('has_other_option')
                    ->label(__('maintenance.has_other_option'))
                    ->boolean(),
                TextColumn::make('remark')
                    ->label(__('app.remark'))
                    ->searchable(),
                IconColumn::make('has_warranty_reminder')
                    ->label(__('maintenance.has_warranty_reminder'))
                    ->boolean(),
                TextColumn::make('reminder_day')
                    ->label(__('maintenance.remind_day'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('has_appointment_schedule')
                    ->label(__('maintenance.has_appointment_schedule'))
                    ->boolean(),
                IconColumn::make('has_verification')
                    ->label(__('maintenance.has_verification'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('app.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
