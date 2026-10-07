<?php

namespace App\Filament\Resources\Banks\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class BanksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label(__('bank.logo'))
                    ->defaultImageUrl(fn($record) => $record->getFirstMediaUrl('bank') ?: 'https://dashboard.mymooban.co.th/images/no-image.png')
                    ->imageSize(size: 30),
                TextColumn::make('name')
                    ->label(__('bank.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_th')
                    ->label(__('bank.name_th'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('bank.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('bank.updated_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
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
