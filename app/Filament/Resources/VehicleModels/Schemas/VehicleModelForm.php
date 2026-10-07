<?php

namespace App\Filament\Resources\VehicleModels\Schemas;

use App\Enums\Vehicle\CarBodyType;
use App\Enums\Vehicle\MotorcycleBodyType;
use App\Enums\Vehicle\VehicleType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class VehicleModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('vehicle.vehicle_model'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('vehicle.vehicle_model'))
                            ->columnSpanFull()
                            ->schema([
                                // ✅ Add this Select for Vehicle Brand
                                Select::make('vehicle_brand_id')
                                    ->label(__('vehicle.brand_name'))
                                    ->relationship('vehicleBrand', 'name') // or ->options(VehicleBrand::pluck('name', 'id')->toArray()) for manual
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Select::make('type')
                                    ->label(__('app.type'))
                                    ->options(
                                        collect(VehicleType::cases())
                                            ->mapWithKeys(fn (VehicleType $type) => [
                                                $type->value => $type->label(),
                                            ])
                                            ->toArray()
                                    )
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                        if ($state == VehicleType::MOTORCYCLE->value) {
                                            $set('body_type', null);
                                        }
                                    }),
                                Select::make('body_type')
                                    ->label(__('vehicle.body_type'))
                                    ->options(function (Get $get) {
                                        if ($get('type') == VehicleType::CAR->value) {
                                            return collect(CarBodyType::cases())
                                                ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                                                ->toArray();
                                        } elseif ($get('type') == VehicleType::MOTORCYCLE->value) {
                                            return collect(MotorcycleBodyType::cases())
                                                ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                                                ->toArray();
                                        }

                                        return [];
                                    })
                                    ->required(fn (Get $get) => $get('type') !== null)
                                    ->dehydrated()
                                    ->live(),
                                TextInput::make('name')
                                    ->label(__('app.name'))
                                    ->unique(ignorable: fn (?Model $record): ?Model => $record)
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Fieldset::make(__('vehicle.specifications'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('launched_year')
                                    ->label(__('app.launched_year'))
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(date('Y'))
                                    ->helperText('Enter only the year, e.g. 2022'),
                                TextInput::make('price_min')
                                    ->label(__('app.price_min_thb'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1000000),
                                TextInput::make('price_max')
                                    ->label(__('app.price_max_thb'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1000000),
                            ]),
                    ]),
            ]);
    }
}
