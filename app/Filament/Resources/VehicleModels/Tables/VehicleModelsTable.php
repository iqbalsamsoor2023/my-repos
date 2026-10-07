<?php

namespace App\Filament\Resources\VehicleModels\Tables;

use App\Models\VehicleModel;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class VehicleModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicleBrand.country.name')
                    ->label(__('app.country_of_origin'))
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('vehicleBrand.name')
                    ->label(__('vehicle.brand_name'))
                    ->description(fn (VehicleModel $record): string => $record->vehicleBrand->name_th ?? '-')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('vehicle.model_name'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('fuel_type')
                    ->label(__('vehicle.fuel_type'))
                    ->getStateUsing(function (VehicleModel $record): string {
                        return $record->vehicles
                            ->pluck('fuel_type')
                            ->unique()
                            ->filter()
                            ->implode(', ') ?: '-';
                    })
                    ->toggleable(),
                TextColumn::make('body_type')
                    ->label(__('vehicle.body_type'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('residence.created_at'))
                    ->formatStateUsing(fn ($state) => $state?->format('d-M-y H:i:s'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('residence.updated_at'))
                    ->formatStateUsing(fn ($state) => $state?->format('d-M-y H:i:s'))
                    ->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
