<?php

namespace App\Filament\Resources\BillPayeeSettings\Tables;

use App\Models\BillPayeeSetting;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class BillPayeeSettingsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->searchable()
                    ->description(fn(BillPayeeSetting $record): string => $record?->residence?->name_th ?? '-')
                    ->hidden($user->hasRole(['Property Management'])),
                TextColumn::make('payee_name')
                    ->label(__('app.name')),
                TextColumn::make('payee_name_th')
                    ->label(__('app.name_th')),
                TextColumn::make('payee_email')
                    ->label(__('app.email')),
                TextColumn::make('payee_phone_no')
                    ->label(__('app.phone_number')),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make()
                        ->visible($user->hasAnyRole(['Admin', 'Property Management Operation Center'])),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->hidden($user->hasRole(['Admin'])),
            ]);
    }
}
