<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Enums\Residence\MoobanType;
use App\Enums\Residence\SubType;
use App\Enums\Unit\HouseType;
use App\Enums\Unit\PaymentFrequency;
use App\Enums\Unit\StatusType;
use App\Enums\Unit\UnitSizeType;
use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Forms\Components\Unit\InvitationCodeOwner;
use App\Forms\Components\Unit\InvitationCodeTenant;
use App\Models\Residence;
use App\Policies\UnitPolicy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();

        return $schema
            ->components([
                Section::make(__('unit.unit'))
                    ->description(__('unit.unit_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreateUnit) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->default(function () {
                                        $user = Auth::user();

                                        if ($user->hasRole('Property Management')) {
                                            return $user->propertyManagement?->id;
                                        }

                                        return null;
                                    })
                                    ->afterStateUpdated(function ($state, $set) {
                                        $residence = Residence::find($state);
                                        $showSubType = $residence && in_array($residence->mooban_type, [
                                            MoobanType::PUBLIC->value,
                                            MoobanType::RESIDENCE->value,
                                            MoobanType::PMOC->value,
                                        ]);

                                        $set('sub_type_visible', $showSubType);
                                    }),
                                Select::make('sub_type')
                                    ->label(__('residence.sub_type'))
                                    ->options(SubType::publicOptions())
                                    ->default(fn ($record) => $record?->residence?->sub_type?->value)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => setHouseTypeOptions($state, $set))
                                    ->afterStateHydrated(fn ($state, callable $set, $get, $record) => setHouseTypeOptions(
                                        $state ?? $record?->residence?->sub_type,
                                        $set
                                    ))
                                    ->reactive(),
                                Select::make('house_type')
                                    ->label(__('unit.house_type'))
                                    ->options(fn ($get) => $get('house_type_options') ?? [])
                                    ->default(function ($get, $record) {
                                        if ($record?->house_type) {
                                            return $record->house_type;
                                        }
                                        $subType = SubType::tryFrom($get('sub_type'));

                                        return $subType
                                            ? HouseType::defaultBySubType($subType)?->value
                                            : null;
                                    })
                                    ->visible(fn ($get, $record) => MoobanType::isSubTypeVisible($get, $record)),
                                TextInput::make('unit_number')
                                    ->label(__('unit.unit_number'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('street')
                                    ->label(__('unit.soi'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('floor')
                                    ->label(__('unit.floor'))
                                    ->maxLength(255),
                                TextInput::make('block')
                                    ->label(__('unit.block'))
                                    ->maxLength(255),
                                DatePicker::make('move_in_at')
                                    ->label(__('unit.move_in_at')),
                                Select::make('status')
                                    ->label(__('app.status'))
                                    ->options(StatusType::class)
                                    ->searchable()
                                    ->required(),
                                TextInput::make('unit_size')
                                    ->label(function (callable $get) {
                                        $residenceId = $get('residence_id');
                                        $label = __('unit.space_in_sqm');

                                        if (! $residenceId) {
                                            return $label;
                                        }

                                        $residence = Residence::find($residenceId);

                                        if (! $residence) {
                                            return $label;
                                        }

                                        if (in_array($residence->mooban_type, [SubType::SINGLE_HOME->value, SubType::TOWN_HOME->value])) {
                                            $label = __('unit.space_in_sqw');
                                        }

                                        return $label;
                                    })
                                    ->numeric()
                                    ->reactive()
                                    ->visible(function (callable $get) {
                                        $subType = $get('sub_type');
                                        if (! $subType) {
                                            return false;
                                        }

                                        // Show for landed (1-5) and condos (6-7)
                                        return in_array($subType, [
                                            SubType::POOL_VILLA->value,
                                            SubType::SINGLE_HOME->value,
                                            SubType::TWIN_HOME->value,
                                            SubType::TOWN_HOME->value,
                                            SubType::HOME_OFFICE->value,
                                            SubType::CONDO_HIGH_RISE->value,
                                            SubType::CONDO_LOW_RISE->value,
                                        ]);
                                    }),
                                TextInput::make('land_size')
                                    ->label(__('unit.land_size'))
                                    ->numeric()
                                    ->reactive()
                                    ->visible(function (callable $get) {
                                        $subType = $get('sub_type');
                                        if (! $subType) {
                                            return false;
                                        }

                                        // Show only for landed properties (1-5)
                                        return in_array($subType, [
                                            SubType::POOL_VILLA->value,
                                            SubType::SINGLE_HOME->value,
                                            SubType::TWIN_HOME->value,
                                            SubType::TOWN_HOME->value,
                                            SubType::HOME_OFFICE->value,
                                        ]);
                                    }),
                            ])
                            ->hidden(fn (Component $livewire): bool => $livewire instanceof EditUnit && UnitPolicy::isResalesOrSales(Auth::user())), // disable edit for rtm & sm,

                        Fieldset::make(__('unit.vr_links'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('myseevr_link')
                                    ->label(__('unit.empty_room_vr_link'))
                                    ->url()
                                    ->maxLength(100),
                                TextInput::make('sample_room_vr_link')
                                    ->label(__('unit.sample_room_vr_link'))
                                    ->url()
                                    ->maxLength(100),
                            ]),

                        Fieldset::make(__('unit.unit_contract_and_floor_plan'))
                            ->columnSpanFull()
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('booking_form')
                                    ->label(__('unit.booking_form_pdf'))
                                    ->collection('booking_form')
                                    ->customProperties(['attachment' => 'booking_form'])
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->disk('cos')
                                    ->openable(true)
                                    ->downloadable(true)
                                    ->placeholder(__('sale.file_upload_placeholder')),
                                SpatieMediaLibraryFileUpload::make('house_contract')
                                    ->label(__('unit.house_contract_pdf'))
                                    ->collection('house_contract')
                                    ->customProperties(['attachment' => 'house_contract'])
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->disk('cos')
                                    ->openable(true)
                                    ->downloadable(true)
                                    ->placeholder(__('sale.file_upload_placeholder')),
                                SpatieMediaLibraryFileUpload::make('floor_plan')
                                    ->label(__('unit.floor_plan'))
                                    ->collection('floor_plan')
                                    ->customProperties(['attachment' => 'floor_plan'])
                                    ->disk('cos')
                                    ->openable(true)
                                    ->downloadable(true)
                                    ->placeholder(__('sale.file_upload_placeholder')),
                                SpatieMediaLibraryFileUpload::make('floor_plan_pdf')
                                    ->label(__('unit.floor_plan_pdf'))
                                    ->collection('floor_plan_pdf')
                                    ->customProperties(['attachment' => 'floor_plan_pdf'])
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->disk('cos')
                                    ->openable(true)
                                    ->downloadable(true)
                                    ->placeholder(__('sale.file_upload_placeholder')),
                            ]),

                        Fieldset::make(__('unit.payment_settings'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('charge_type')
                                    ->label(__('unit.charge_type'))
                                    ->options(UnitSizeType::class)
                                    ->searchable()
                                    ->visible(fn () => UnitPolicy::hasPaymentSettingsAccess(Auth::user())),
                                Select::make('maintenance_cycle')
                                    ->label(__('unit.maintenance_cycle'))
                                    ->options(PaymentFrequency::class)
                                    ->searchable()
                                    ->visible(fn () => UnitPolicy::hasPaymentSettingsAccess(Auth::user())),
                            ]),
                    ]),

                Section::make(__('unit.invitation_code'))
                    ->description(__('unit.invitation_code_detail'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('unit.codes'))
                            ->schema([
                                InvitationCodeOwner::make('invitation_code_owner')
                                    ->label(__('unit.invitation_code_owner'))
                                    ->dehydrated(false),
                                InvitationCodeTenant::make('invitation_code_tenant')
                                    ->label(__('unit.invitation_code_tenant'))
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof EditUnit && UnitPolicy::isResalesOrSales(Auth::user())) // disable edit for rtm & sm,
                    ->visible(fn (Component $livewire): bool => $livewire instanceof EditUnit),
            ]);
    }
}
