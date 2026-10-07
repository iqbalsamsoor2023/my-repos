<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\Company\InsuranceTypeEnum;
use App\Enums\User\RoleType;
use App\Enums\Vehicle\FuelType;
use App\Enums\Vehicle\VehicleType;
use App\Filament\Resources\Units\RelationManagers\VehiclesRelationManager;
use App\Filament\Resources\Vehicles\Pages\CreateVehicle;
use App\Models\Erp\ThailandProvince;
use App\Models\InsuranceCompany;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();

        return $schema
            ->components([
                Section::make(__('vehicle.vehicle'))
                    ->description(__('vehicle.vehicle_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('province_id')
                                    ->label(__('app.province'))
                                    ->options(ThailandProvince::all()->pluck('name_in_english', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreateVehicle) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('unit_id', null);
                                    })
                                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VehiclesRelationManager),
                                Select::make('unit_id')
                                    ->label(__('unit.unit_number'))
                                    ->relationship('unit', 'unit_number', function (callable $get) {
                                        return Unit::where('residence_id', $get('residence_id'));
                                    })
                                    ->required()
                                    ->reactive()
                                    ->preload()
                                    ->searchable()
                                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VehiclesRelationManager),
                                Select::make('user_id')
                                    ->label(__('app.resident'))
                                    ->options(function (Component $livewire, callable $get) {
                                        if (isset($livewire->ownerRecord)) {
                                            return User::whereHas('roles', function ($query) {
                                                $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                            })->whereHas('units', function (Builder $builder) use ($livewire) {
                                                return $builder->where('unit_user.unit_id', $livewire->ownerRecord->id);
                                            })->pluck('name', 'id');
                                        } else {
                                            return User::whereHas('roles', function ($query) {
                                                $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                            })->whereHas('units', function (Builder $builder) use ($get) {
                                                return $builder->where('unit_user.unit_id', $get('unit_id'));
                                            })->pluck('name', 'id');
                                        }
                                    })
                                    ->required(),
                                Radio::make('type')
                                    ->label(__('app.type'))
                                    ->options(
                                        collect(VehicleType::cases())
                                            ->mapWithKeys(fn (VehicleType $type) => [$type->value => $type->label()])
                                            ->toArray()
                                    )
                                    ->columns(5)
                                    ->reactive()
                                    ->required(),
                            ]),

                        Fieldset::make(__('vehicle.vehicle'))
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                Select::make('vehicle_brand_id')
                                    ->label(__('vehicle.brand_name'))
                                    ->options(
                                        fn (Get $get) => VehicleBrand::whereHas(
                                            'vehicleModels',
                                            fn ($query) => $query->where('type', $get('type'))
                                        )->pluck('name', 'id')->toArray()
                                            + ['Other' => 'Other']
                                    )
                                    ->reactive()
                                    ->required(),

                                TextInput::make('vehicle_brand')
                                    ->label(__('vehicle.brand_name'))
                                    ->maxLength(255)
                                    ->visible(fn (Get $get) => $get('vehicle_brand_id') === 'Other')
                                    ->requiredIf('vehicle_brand_id', 'Other'),

                                Select::make('vehicle_model_id')
                                    ->label(__('vehicle.model_name'))
                                    ->options(
                                        fn (Get $get) => VehicleModel::when(
                                            $get('vehicle_brand_id'),
                                            fn ($query) => $query->where('vehicle_brand_id', $get('vehicle_brand_id'))
                                                ->where('type', $get('type'))
                                        )->pluck('name', 'id')->toArray()
                                            + ['Other' => 'Other']
                                    )
                                    ->hidden(fn (Get $get) => ! $get('vehicle_brand_id'))
                                    ->reactive()
                                    ->required(),
                                TextInput::make('vehicle_model')
                                    ->label(__('vehicle.model'))
                                    ->maxLength(255)
                                    ->visible(fn (Get $get) => $get('vehicle_model_id') === 'Other')
                                    ->requiredIf('vehicle_model_id', 'Other'),
                                Select::make('fuel_type')
                                    ->label(__('vehicle.fuel_type'))
                                    ->options(
                                        collect(FuelType::cases())
                                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                                            ->toArray()
                                    )
                                    ->searchable()
                                    ->required(),
                                TextInput::make('plate_number')
                                    ->label(__('vehicle.plate_number'))
                                    ->required()
                                    ->unique(modifyRuleUsing: function (Unique $rule) {
                                        if (fn (Component $livewire): bool => $livewire instanceof CreateVehicle) {
                                            return $rule->withoutTrashed();
                                        }
                                    }, ignorable: fn (?Model $record): ?Model => $record)
                                    ->maxLength(255),
                                SpatieMediaLibraryFileUpload::make('image')
                                    ->label(__('app.image'))
                                    ->collection('vehicle_image')
                                    ->customProperties(['type' => 'vehicle'])
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true),
                            ]),

                        Fieldset::make(__('vehicle.vehicle_info'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('model_year')
                                    ->label(__('vehicle.model_year'))
                                    ->options(array_combine(range(date('Y'), 1969), range(date('Y'), 1969)))
                                    ->searchable()
                                    ->required(),
                                DatePicker::make('roadtax_expiry_date')
                                    ->label(__('vehicle.roadtax_expiry_date')),
                                SpatieMediaLibraryFileUpload::make('roadtax_image')
                                    ->label(__('vehicle.roadtax_image'))
                                    ->collection('roadtax_image')
                                    ->customProperties(['type' => 'roadtax'])
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true),
                                SpatieMediaLibraryFileUpload::make('vehicle_front_image')
                                    ->label(__('vehicle.front_image'))
                                    ->collection('vehicle_front_image')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true),
                                SpatieMediaLibraryFileUpload::make('vehicle_back_image')
                                    ->label(__('vehicle.back_image'))
                                    ->collection('vehicle_back_image')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true),
                                SpatieMediaLibraryFileUpload::make('vehicle_right_image')
                                    ->label(__('vehicle.right_image'))
                                    ->collection('vehicle_right_image')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true),
                                SpatieMediaLibraryFileUpload::make('vehicle_left_image')
                                    ->label(__('vehicle.left_image'))
                                    ->collection('vehicle_left_image')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true),
                            ]),

                        Fieldset::make(__('vehicle.vehicle_insurance'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('insurance_company_id')
                                    ->label(__('vehicle.insurance_company'))
                                    ->options(
                                        InsuranceCompany::where('type', InsuranceTypeEnum::VEHICLE_INSURANCE->value)
                                            ->pluck('name', 'id')
                                    )
                                    ->searchable(),

                                DatePicker::make('insurance_expiry_date')
                                    ->label(__('vehicle.insurance_expiry_date')),
                                TextInput::make('policy_no')
                                    ->label(__('vehicle.policy_no'))
                                    ->maxLength(100),
                            ]),

                        Fieldset::make(__('vehicle.access'))
                            ->columnSpanFull()
                            ->schema([
                                Toggle::make('is_access_card')
                                    ->label(__('vehicle.is_access_card'))
                                    ->required(),
                                Toggle::make('is_car_sticker')
                                    ->label(__('vehicle.is_car_sticker'))
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
