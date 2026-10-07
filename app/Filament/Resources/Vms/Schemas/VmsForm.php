<?php

namespace App\Filament\Resources\Vms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VmsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Residence'))
                    ->description(__('Residence Detail'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('Residence/MooBan'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('MooBan Name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(),
                                TextInput::make('name_th')
                                    ->label(__('MooBan Name (TH)'))
                                    ->maxLength(255)
                                    ->disabled(),
                            ]),
                        Fieldset::make(__('Visitor Activation Setting Module'))
                            ->schema([
                                ...collect([
                                    'brand' => __('vehicle.brand'),
                                    'purposeOfVisit' => __('visitor.purpose_of_visit'),
                                    'contactNumber' => __('app.phone_number'),
                                    'temperature' => __('visitor.temperature'),
                                    'companyName' => __('app.company_name'),
                                    'passenger' => __('visitor.passenger'),
                                    'remark' => __('app.remark'),
                                    'color' => __('vehicle.color'),
                                    'pdpa' => __('PDPA'),
                                    'visitorPhoto' => __('visitor.visitor_photo'),
                                    'scanVisitorCard' => __('visitor.scan_visitor_card'),
                                    'vehiclePhoto' => __('vehicle.vehicle_image'),
                                    'foodAndParcel' => __('Food & Parcel'),
                                ])->map(
                                    fn($label, $key) => Toggle::make("activationModules.$key")
                                        ->label($label)
                                        ->default(true)
                                )->toArray(),
                            ]),
                        Fieldset::make(__('Vistor Slip Activation Setting Module'))
                            ->schema([
                                ...collect([
                                    'vsVisitorCard' => __('visitor.visitor_card'),
                                    'vsVisitorName' => __('visitor.visitor_name'),
                                    'vsVehiclePlateNo' => __('vehicle.vehicle_plate_number'),
                                    'vsProvinceOfVehicle' => __('vehicle.vehicle_province'),
                                    'vsBrand' => __('vehicle.vehicle_brand'),
                                    'vsColor' => __('vehicle.vehicle_color'),
                                    'vsPurposeOfVisit' => __('Purpose Of Visit'),
                                    'vsVehicleType' => __('vehicle.vehicle_type'),
                                    'vsContactAt' => __('app.contact_at'),
                                    'vsContactNumber' => __('app.contact_no'),
                                    'vsTemperature' => __('visitor.temperature'),
                                    'vsCompanyName' => __('app.company_name'),
                                    'vsPassenger' => __('visitor.passenger'),
                                    'vsRemark' => __('app.remark'),
                                    'vsStamp' => __('Sign/Stamp'),
                                    'vsMoobanLogo' => __('MooBan Logo'),
                                    'vsOnlySignature' => __('Only Signature'),
                                    'vsOnlyStamp' => __('Only Stamp'),
                                ])->map(
                                    fn($label, $key) => Toggle::make("activationModules.$key")->label($label)->default(true)
                                )->toArray(),

                                Toggle::make('is_qr_active')
                                    ->label(__('Visitor Qr Code'))
                                    ->default(true),
                            ]),
                    ])
            ]);
    }
}
