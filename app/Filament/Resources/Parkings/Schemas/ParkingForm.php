<?php

namespace App\Filament\Resources\Parkings\Schemas;

use App\Enums\Parking\DiscountType;
use App\Enums\Parking\ParkingType;
use App\Filament\Resources\Parkings\Pages\CreateParking;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ParkingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.setting'))
                    ->description(__('Disclaimer: Starting 1st January 2024, Old data calculation will be different.'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->visible(fn (Component $livewire): bool => $livewire instanceof CreateParking)
                            ->options(list_residences())
                            ->searchable()
                            ->unique(ignorable: fn (?Model $record): ?Model => $record)
                            ->required(),
                        Radio::make('type')
                            ->label(__('app.type'))
                            ->options([
                                ParkingType::FREE->value => __('visitor.'.strtolower(ParkingType::FREE->name)),
                                ParkingType::PAID->value => ucfirst(strtolower(ParkingType::PAID->name)),
                            ])
                            ->inline(true)
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state === '1') {
                                    $set('rate_mode', 0);
                                    $set('discount_type', 0);
                                    $set('is_discount_coupon', 0);
                                } else {
                                    $set('rate_mode', 1);
                                }
                            })
                            ->reactive()
                            ->required(),
                        Hidden::make('rate_mode')
                            ->default('1'),
                        Radio::make('discount_type')
                            ->label(__('visitor.discount_type'))
                            ->inline()
                            ->options([
                                DiscountType::NO_DISCOUNT_COUPON->value => 'No discount Coupon',
                                DiscountType::PRICE->value => 'Price based discounts',
                                DiscountType::TIME->value => 'Time based discounts',
                            ])
                            ->descriptions([
                                DiscountType::NO_DISCOUNT_COUPON->value => 'No discount coupon offered to any visitors.',
                                DiscountType::PRICE->value => 'Discount offered to visitors calculated by price.',
                                DiscountType::TIME->value => 'Discount offered to visitors calculated by time.',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state == 1 || $state == 2) {
                                    $bool = 1;
                                    $set('is_discount_coupon', $bool);
                                } else {
                                    $bool = 0;
                                    $set('is_discount_coupon', $bool);
                                }
                            })
                            ->visible(fn (Get $get) => $get('type') == '2')
                            ->required(fn (Get $get) => $get('type') !== '1'),
                        Toggle::make('activationModules.parkingFee')
                            ->label(__('Show on Guard Panel'))
                            ->visible(fn (Get $get) => $get('type') == '2')
                            ->default(true)
                            ->reactive(),
                        Hidden::make('is_discount_coupon')
                            ->required(fn (Get $get) => $get('type') !== '1'),
                    ]),
                
            ]);
    }
}
