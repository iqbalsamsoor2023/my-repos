<?php

namespace App\Filament\Resources\VehicleBrands\Tables;

use App\Models\VehicleBrand;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class VehicleBrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.name')
                    ->label(__('app.country_of_origin'))
                    ->colors([
                        'primary', // default color for all countries
                    ])
                    ->sortable()
                    ->badge()
                    ->searchable()
                    ->toggleable(),
                ImageColumn::make('icon')
                    ->defaultImageUrl(fn ($record) => $record->getFirstMediaUrl('vehicle_brand_images') ?: 'https://dashboard.mymooban.co.th/images/no-image.png')
                    ->imageSize(size: 30),
                TextColumn::make('name')
                    ->label(__('vehicle.brand_name'))
                    ->description(fn (VehicleBrand $record): string => $record->name_th ?? '-')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('gpl_priority')
                    ->label(__('vehicle.gpl_ordering'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (VehicleBrand $record) {
                        return $record->created_at->format('d-M-y');
                    }),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (VehicleBrand $record) {
                        return $record->created_at->format('H:i:s');
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
