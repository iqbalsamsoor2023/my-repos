<?php

namespace App\Filament\Resources\Furnitures\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FurnituresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('icon')
                    ->defaultImageUrl(fn ($record) => $record->getFirstMediaUrl('furniture') ?: 'https://dashboard.mymooban.co.th/images/no-image.png')
                    ->imageSize(size: 30),
                TextColumn::make('name')
                    ->label(__('app.item_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_th')
                    ->label(__('app.item_name_th'))
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label(__('app.is_active'))
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('app.is_active'))
                    ->multiple()
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
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
