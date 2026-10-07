<?php

namespace App\Filament\Resources\Visitors\Schemas;

use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\IdType;
use App\Enums\Visitor\VehicleType;
use App\Forms\Components\Visitor\Image;
use App\Forms\Components\Visitor\VehicleImage;
use App\Forms\Components\Visitor\VisitorImage;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VisitorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Visitor Information'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Section::make(__('Residence & Unit Visit'))
                                    ->columnSpanFull()
                                    ->schema([
                                        TextEntry::make('residence.name')
                                            ->label(__('app.mooban_or_residence'))
                                            ->inlineLabel()
                                            ->default('-')
                                            ->color('primary'),
                                        ViewField::make('visiting_arrangements')
                                            ->view('filament.resources.visitors.widgets.visiting-arrangement'),
                                    ]),

                                Section::make(__('Visitor Details'))
                                    ->columnSpanFull()
                                    ->schema([
                                        TextEntry::make('visitor.name')
                                            ->label(__('app.name'))
                                            ->inlineLabel(),
                                        TextEntry::make('visitor.contact_no')
                                            ->label(__('app.phone_number'))
                                            ->inlineLabel(),
                                        TextEntry::make('visitor.id_type')
                                            ->label(__('ID Type'))
                                            ->formatStateUsing(fn($record) => match ($record->visitor?->id_type) {
                                                IdType::IC->value => 'IC',
                                                IdType::PASSPORT->value => 'Passport',
                                                IdType::DRIVING_LICENSE->value => 'Driving License',
                                                default => '-',
                                            })
                                            ->inlineLabel(),
                                        TextEntry::make('visitor.id_number')
                                            ->label(__('user.thai_id_or_license_id'))
                                            ->inlineLabel(),
                                        TextEntry::make('temperature')
                                            ->label(__('visitor.temperature'))
                                            ->inlineLabel(),
                                    ]),

                                Section::make(__('Visiting Informations'))
                                    ->columnSpanFull()
                                    ->schema([
                                        TextEntry::make('is_pre_register')
                                            ->label(__('visitor.is_pre_register'))
                                            ->inlineLabel()
                                            ->formatStateUsing(fn($state) => $state ? __('app.yes') : __('app.no')),
                                        TextEntry::make('arrival_time')
                                            ->label(__('visitor.arrival_time'))
                                            ->inlineLabel(),
                                        TextEntry::make('arrival_type')
                                            ->label(__('visitor.arrival_type'))
                                            ->inlineLabel()
                                            ->formatStateUsing(fn($record) => match ($record->arrival_type) {
                                                ArrivalType::DRIVE_IN->value => 'Drive In',
                                                ArrivalType::WALK_IN->value => 'Walk In',
                                                default => '-',
                                            }),
                                        TextEntry::make('vehicle_type')
                                            ->label(__('vehicle.vehicle_type'))
                                            ->inlineLabel()
                                            ->formatStateUsing(fn($record) => match ($record->vehicle_type) {
                                                VehicleType::CAR->value => 'Car',
                                                VehicleType::TRUCK->value => 'Truck',
                                                VehicleType::MOTORBIKE->value => 'Motorbike',
                                                VehicleType::VAN->value => 'Van',
                                                VehicleType::TAXI->value => 'Taxi',
                                                VehicleType::PICKUP->value => 'Pickup',
                                                default => '-',
                                            })
                                            ->visible(fn($record) => $record->arrival_type === ArrivalType::DRIVE_IN->value),
                                        // TextEntry::make('vehicle_info')
                                        //     ->label(__('Vehicle Information'))
                                        //     ->inlineLabel()
                                        //     ->html()
                                        //     ->getStateUsing(function (VisitorLog $record) {
                                        //         if ($record->arrival_type !== ArrivalType::DRIVE_IN->value) {
                                        //             return '-';
                                        //         }

                                        //         $vehicle = json_decode($record->vehicle_info);
                                        //         if (! $vehicle) {
                                        //             return '-';
                                        //         }

                                        //         $lines = [];

                                        //         if (!empty($record->vehicle_plate_no)) {
                                        //             $lines[] = 'Plate Number: ' . $record->vehicle_plate_no;
                                        //         }
                                        //         if (!empty($vehicle->province)) {
                                        //             $lines[] = 'Province: ' . $vehicle->province;
                                        //         }
                                        //         if (!empty($vehicle->vehicle_brand)) {
                                        //             $lines[] = 'Brand: ' . $vehicle->vehicle_brand;
                                        //         }
                                        //         if (!empty($vehicle->vehicle_color)) {
                                        //             $lines[] = 'Color: ' . $vehicle->vehicle_color;
                                        //         }

                                        //         return implode('<br>', $lines);
                                        //     })
                                        //     ->visible(fn(VisitorLog $record) => $record->arrival_type === ArrivalType::DRIVE_IN->value),
                                        TextEntry::make('visitor_purpose')
                                            ->label(__('visitor.purpose_of_visit'))
                                            ->inlineLabel(),
                                        TextEntry::make('company_name')
                                            ->label(__('app.company_name'))
                                            ->inlineLabel(),
                                        TextEntry::make('passenger_count')
                                            ->label(__('visitor.no_of_passenger'))
                                            ->inlineLabel(),
                                        TextEntry::make('leave_time')
                                            ->label(__('visitor.leave_time'))
                                            ->inlineLabel(),
                                        TextEntry::make('remark')
                                            ->label(__('app.remark'))
                                            ->inlineLabel(),
                                    ]),

                                Section::make(__('Images'))
                                    ->columnSpan(3)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Image::make('id_image')
                                                    ->label(__('user.id_image')),
                                                VisitorImage::make('visitor_image')
                                                    ->label(__('visitor.visitor_image')),
                                                VehicleImage::make('vehicle_image')
                                                    ->label(__('vehicle.vehicle_image')),
                                            ]),
                                    ]),
                             ]),
                    ]),
            ]);
    }
}
