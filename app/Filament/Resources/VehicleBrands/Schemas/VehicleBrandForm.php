<?php

namespace App\Filament\Resources\VehicleBrands\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class VehicleBrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('vehicle.vehicle_brand'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name'))
                            ->unique(ignorable: fn (?Model $record): ?Model => $record)
                            ->required()
                            ->maxLength(50),
                        TextInput::make('name_th')
                            ->label(__('app.name_th'))
                            ->maxLength(20),
                        Select::make('country_id')
                            ->label(__('vehicle.country_of_origin'))
                            ->relationship('country', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('gpl_priority')
                            ->label(__('vehicle.gpl_ordering'))
                            ->numeric()
                            ->maxLength(20),
                        SpatieMediaLibraryFileUpload::make('brand_image')
                            ->label(__('app.logo'))
                            ->collection('vehicle_brand_images')
                            ->disk('cos')
                            ->image()
                            ->openable(true),
                    ]),
            ]);
    }
}
