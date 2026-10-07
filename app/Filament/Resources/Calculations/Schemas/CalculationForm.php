<?php

namespace App\Filament\Resources\Calculations\Schemas;

use App\Enums\Vehicle\VehicleType;
use App\Models\Calculation;
use Closure;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Component;

class CalculationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Calculations'))
                    ->description(__('Calculation Details'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->columns(3)
                            ->schema([
                                Radio::make('vehicle_type')
                                    ->label(__('vehicle.vehicle_type'))
                                    ->options([
                                        VehicleType::CAR->value => __('vehicle.'.strtolower(VehicleType::CAR->name)),
                                        VehicleType::MOTORCYCLE->value => __('vehicle.'.strtolower(VehicleType::MOTORCYCLE->name)),
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn (Component $livewire): bool => $livewire->mountedActions[0] === 'edit'),
                                Toggle::make('is_stamp')
                                    ->label(__('visitor.is_stamp'))
                                    ->required()
                                    ->reactive()
                                    ->rules(function (Component $livewire, Get $get) { // Unique combination rule: vehicle_type + is_stamp per residence
                                        return [
                                            function (string $attribute, $value, Closure $fail) use ($livewire, $get) {

                                                $residenceId = $livewire->ownerRecord->residence_id;

                                                $query = Calculation::whereHas('parking', function ($q) use ($residenceId) {
                                                    $q->where('residence_id', $residenceId);
                                                })
                                                    ->where('vehicle_type', $get('vehicle_type'))
                                                    ->where('is_stamp', $get('is_stamp'));

                                                // Detect edit mode and ignore current record
                                                $action = $livewire->mountedActions[0]['name'] ?? 'create';
                                                $editingId = $action === 'edit' ? $livewire->mountedActions[0]['data']['id'] ?? null : null;

                                                if ($editingId) {
                                                    $query->where('id', '!=', $editingId);
                                                }

                                                if ($query->exists()) {
                                                    $fail(__('This vehicle type and stamp already exists for this residence.'));
                                                }
                                            },
                                        ];
                                    }),
                                TextInput::make('rate_per_hour')
                                    ->label(__('visitor.rate_per_hour'))
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->helperText(__('app.parking_rate_per_hour_helper'))
                                    ->required(),
                                TimePicker::make('free_parking_minutes')
                                    ->label(__('visitor.free_parking_minutes'))
                                    ->native(false)
                                    ->seconds(false)
                                    ->default('00:00')
                                    ->helperText(__('app.free_parking_helper')),
                                TextInput::make('penalty')
                                    ->label(__('visitor.penalty'))
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->helperText(__('app.penalty_amount_helper'))
                                    ->required()
                                    ->default(0)
                                    ->reactive()
                                    ->hidden(
                                        fn (Component $livewire, Get $get): bool => $livewire->mountedActions[0] === 'create' &&
                                            Calculation::where('parking_id', $livewire)
                                                ->where('vehicle_type', $get('vehicle_type'))
                                                ->exists()
                                    ),
                                TimePicker::make('chartered_duration')
                                    ->label(__('visitor.chartered_duration'))
                                    ->native(false)
                                    ->seconds(false)
                                    ->default('00:00')
                                    ->helperText(__('app.chartered_parking_duration_helper'))
                                    ->required(fn (Get $get) => $get('chartered_price') > 0),
                                TextInput::make('chartered_price')
                                    ->label(__('visitor.chartered_price'))
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->helperText(__('app.chartered_parking_price_helper'))
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
