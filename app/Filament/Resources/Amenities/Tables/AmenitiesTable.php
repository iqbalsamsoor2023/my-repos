<?php

namespace App\Filament\Resources\Amenities\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class AmenitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('amenity_name')
                    ->label(__('maintenance.amenity_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('warranty_period')
                    ->label(__('maintenance.warranty_period'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('period_type')
                    ->label(__('maintenance.period_type'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('supplier')
                    ->label(__('maintenance.supplier'))
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_out_warranty')
                    ->label(__('maintenance.is_out_warranty'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label(__('app.remark'))
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
