<?php

namespace App\Filament\Resources\PrebookVisitors\Schemas;

use App\Enums\Vehicle\VehicleColor;
use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\IdType;
use App\Enums\Visitor\VehicleType;
use App\Models\PreregisterVisitor;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;

class PrebookVisitorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('visitor.prebook_visitor'))
                    ->description(__('visitor.prebook_visitor_details'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Section::make(__('visitor.arrival_informations'))
                                    ->columnSpanFull()
                                    ->schema([
                                        TextEntry::make(
                                            app()->getLocale() === 'th'
                                                ? 'unit.residence.name_th'
                                                : 'unit.residence.name'
                                        )
                                            ->label(__('app.mooban_or_residence'))
                                            ->inlineLabel()
                                            ->color('primary')
                                            ->placeholder('-'),
                                        TextEntry::make('unit_id')
                                            ->label(__('unit.unit_number'))
                                            ->inlineLabel()
                                            ->getStateUsing(function (PreregisterVisitor $record) {
                                                return optional($record?->unit)->unit_number;
                                            })
                                            ->color('primary'),
                                        TextEntry::make('user_id')
                                            ->label(__('app.resident'))
                                            ->inlineLabel()
                                            ->getStateUsing(function (PreregisterVisitor $record) {
                                                return optional($record?->user)->name;
                                            })
                                            ->color('primary'),
                                        TextEntry::make('visitor_purpose')
                                            ->label(__('visitor.purpose_of_visit'))
                                            ->formatStateUsing(fn($record) => match ($record->visitor_purpose) {
                                                'Receive/Delivery'   => __('visitor.receive_or_delivery'),
                                                'Drop Off/Pick Up'   => __('visitor.dropoff_or_pickup'),
                                                'Contractor/Worker' => __('visitor.contractor_or_worker'),
                                                'Visitor Parking'   => __('visitor.visitor_parking'),
                                                'VIP'               => __('visitor.vip'),
                                                'Other'             => __('app.other'),
                                                default             => $record->visitor_purpose,
                                            })
                                            ->inlineLabel()
                                            ->color('primary'),
                                        TextEntry::make('visitor_purpose_other')
                                            ->label(__('Purpose of Visit'))
                                            ->visible(fn($record) => $record->visitor_purpose === 'Other')
                                            ->inlineLabel()
                                            ->color('secondary'),
                                        TextEntry::make('arrival_type')
                                            ->label(__('visitor.arrival_type'))
                                            ->formatStateUsing(
                                                fn($state) =>
                                                ArrivalType::tryFrom($state)?->getLabel() ?? '-'
                                            )
                                            ->inlineLabel()
                                            ->color('primary'),
                                        IconEntry::make('is_multiple_entry')
                                            ->label(__('visitor.is_multiple_entry'))
                                            ->boolean()
                                            ->trueIcon('heroicon-o-check-circle')
                                            ->falseIcon('heroicon-o-x-circle')
                                            ->trueColor('success')
                                            ->falseColor('gray')
                                            ->inlineLabel(),
                                        IconEntry::make('is_qr_code_expired')
                                            ->label(__('visitor.is_qr_code_expired'))
                                            ->boolean()
                                            ->trueIcon('heroicon-o-x-circle')
                                            ->falseIcon('heroicon-o-check-circle')
                                            ->trueColor('danger')
                                            ->falseColor('success')
                                            ->inlineLabel(),
                                        TextEntry::make('validity_start_date')
                                            ->label(__('visitor.validity_start_date'))
                                            ->dateTime('Y-m-d H:i')
                                            ->inlineLabel()
                                            ->color('primary'),                              
                                        TextEntry::make('validity_end_date')
                                            ->label(__('visitor.validity_end_date'))
                                            ->dateTime('Y-m-d H:i')
                                            ->inlineLabel()
                                            ->visible(fn ($record) => $record->is_multiple_entry == true)
                                            ->color('primary'),
                                    ]),

                                Section::make(__('vehicle.vehicle_details'))
                                    ->columnSpanFull()
                                    ->schema([
                                        TextEntry::make('vehicle_type')
                                            ->label(__('vehicle.vehicle_type'))
                                            ->formatStateUsing(
                                                fn($state) =>
                                                VehicleType::tryFrom($state)?->getLabel() ?? '-'
                                            )
                                            ->inlineLabel()
                                            ->color('primary'),
                                        TextEntry::make('vehicle_plate_no')
                                            ->label(__('vehicle.vehicle_plate_number'))
                                            ->inlineLabel()
                                            ->placeholder('-')
                                            ->color('primary'),
                                        TextEntry::make('province.name_in_english')
                                            ->label(__('app.province'))
                                            ->formatStateUsing(
                                                fn($state, $record) =>
                                                $record->province
                                                    ? 'th-' . $record->province->code . ': ' . $record->province->name_in_english
                                                    : '-'
                                            )
                                            ->inlineLabel()
                                            ->color('primary'),
                                        TextEntry::make('vehicleBrand')
                                            ->label(__('vehicle.brand'))
                                            ->formatStateUsing(
                                                fn($state, $record) =>
                                                App::getLocale() === 'th'
                                                    ? ($record->vehicleBrand?->name_th ?? '-')
                                                    : ($record->vehicleBrand?->name ?? '-')
                                            )
                                            ->inlineLabel()
                                            ->color('primary'),
                                        TextEntry::make('vehicle_color')
                                            ->label(__('vehicle.color'))
                                            ->formatStateUsing(
                                                fn($state) =>
                                                VehicleColor::tryFrom($state)?->label() ?? '-'
                                            )
                                            ->inlineLabel()
                                            ->color('primary'),
                                    ])
                                    ->columns(3)
                                    ->visible(
                                        fn($record) =>
                                        ! in_array($record->arrival_type, [null, ArrivalType::WALK_IN->value])
                                    ),

                                Section::make("Visitor's Personal Informations")
                                    ->columnSpanFull()
                                    ->columns(4)
                                    ->schema([
                                        TextEntry::make('visitor.name')
                                            ->label(__('app.name'))
                                            ->inlineLabel()
                                            ->placeholder('-')
                                            ->color('primary'),
                                        TextEntry::make('visitor.contact_no')
                                            ->label(__('app.phone_number'))
                                            ->inlineLabel()
                                            ->placeholder('-')
                                            ->color('primary'),
                                        TextEntry::make('visitor.id_type')
                                            ->label(__('user.id_type'))
                                            ->formatStateUsing(fn($state) => match ($state) {
                                                IdType::IC->value              => __(IdType::IC->name),
                                                IdType::PASSPORT->value        => __('user.' . strtolower(IdType::PASSPORT->name)),
                                                IdType::DRIVING_LICENSE->value => __('user.' . strtolower(IdType::DRIVING_LICENSE->name)),
                                                default                        => '-',
                                            })
                                            ->inlineLabel()
                                            ->color('primary'),
                                        TextEntry::make('visitor.id_number')
                                            ->label(__('user.id_number'))
                                            ->inlineLabel()
                                            ->placeholder('-')
                                            ->color('primary'),
                                    ]),

                                Section::make("Visitor's Extra Informations")
                                    ->columnSpan(3)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('passenger_count')
                                                    ->label(__('Passenger Count'))
                                                    ->inlineLabel()
                                                    ->placeholder('-')
                                                    ->color('primary'),
                                                TextEntry::make('company_name')
                                                    ->label(__('app.company_name'))
                                                    ->inlineLabel()
                                                    ->placeholder('-')
                                                    ->color('primary'),
                                                TextEntry::make('remark')
                                                    ->label(__('app.remark'))
                                                    ->inlineLabel()
                                                    ->placeholder('-')
                                                    ->limit(255)
                                                    ->color('primary'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
