<?php

namespace App\Filament\Resources\Residences\Schemas;

use App\Enums\Company\CompanyTypeEnum;
use App\Enums\Residence\EntranceBarrierType;
use App\Enums\Residence\EntryLaneType;
use App\Enums\Residence\InternetProviderCompany;
use App\Enums\Residence\LocationTagType;
use App\Enums\Residence\MoobanType;
use App\Enums\Residence\SubType;
use App\Filament\Resources\Residences\Pages\CreateResidence;
use App\Models\Erp\CdpCompany;
use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandProvince;
use App\Models\Erp\ThailandSubDistrict;
use App\Models\ErpLocationTag;
use App\Services\ThailandLocationService;
use App\Support\ResidenceOptionsSupport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;

class ResidenceDetailsForm
{
    public static function getForm(): array
    {
        return self::getFormSchema();
    }

    /**
     * Keep the large schema in a dedicated method to improve IDE inference on the public API.
     *
     * @return array<int, mixed>
     */
    private static function getFormSchema(): array
    {
        return [
            Grid::make(1)
                ->columnSpanFull()
                ->schema([
                    Fieldset::make(__('residence.site_details'))
                        ->columnSpanFull()
                        ->schema([
                            self::siteNameFieldset(),
                            self::siteTypeFieldset(),
                            self::siteLocationsFieldset(),
                            self::siteInfoFieldset(),
                            self::subscriptionsFieldset(),
                            self::activationStatusFieldset(),
                        ]),
                ]),
        ];
    }

    private static function siteNameFieldset(): Fieldset
    {
        return Fieldset::make(__('residence.site_name'))
            ->columnSpanFull()
            ->schema([
                TextInput::make('name')
                    ->label(__('residence.site_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_th')
                    ->label(__('residence.site_name_th'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    private static function siteTypeFieldset(): Fieldset
    {
        return Fieldset::make(__('residence.site_type'))
            ->columnSpanFull()
            ->schema([
                Select::make('mooban_type')
                    ->label(__('residence.site_type'))
                    ->options([
                        MoobanType::PUBLIC->value => str_replace('_', ' ', Str::title(MoobanType::PUBLIC->name)),
                        MoobanType::RESIDENCE->value => str_replace('_', ' ', Str::title(MoobanType::RESIDENCE->name)),
                        MoobanType::FACTORY->value => Str::title(MoobanType::FACTORY->name),
                        MoobanType::PMOC->value => Str::upper(MoobanType::PMOC->name),
                        MoobanType::DEMO_FOR_SG->value => str_replace('_', ' ', Str::title(MoobanType::DEMO_FOR_SG->name)),
                    ])
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        $set('sub_type', null);
                    })
                    ->searchable()
                    ->required()
                    ->reactive(),
                Select::make('sub_type')
                    ->label(__('app.type'))
                    ->translateLabel()
                    ->options(function (Get $get) {
                        $moobanType = MoobanType::tryFrom($get('mooban_type')); // Convert integer to enum safely
                        $allSubTypes = collect(SubType::cases());

                        if (! $moobanType) {
                            return [];
                        }

                        $filteredTypes = match ($moobanType) {
                            MoobanType::PUBLIC => $allSubTypes->filter(fn ($type) => in_array($type, [
                                SubType::POOL_VILLA,
                                SubType::SINGLE_HOME,
                                SubType::TWIN_HOME,
                                SubType::TOWN_HOME,
                                SubType::HOME_OFFICE,
                                SubType::CONDO_HIGH_RISE,
                                SubType::CONDO_LOW_RISE,
                            ])),
                            MoobanType::RESIDENCE => $allSubTypes->filter(fn ($type) => in_array($type, [
                                SubType::SERVICE_APARTMENT,
                                SubType::DORMITORY,
                            ])),
                            MoobanType::PMOC => $allSubTypes->filter(fn ($type) => in_array($type, [
                                SubType::HOTEL,
                                SubType::OFFICE_BUILDING,
                                SubType::SHOPPING_MALL,
                                SubType::HOSPITAL,
                                SubType::SCHOOL,
                                SubType::SHOWROOM,
                                SubType::RELIGIOUS_ORGANIZATION,
                                SubType::ART_GALLERY,
                                SubType::SPORT_CLUB,
                                SubType::GOVERNMENT_OFFICE,
                                SubType::LOGISTIC_CENTER,
                                SubType::RESTAURANT,
                                SubType::STATION_TRAIN,
                                SubType::STATION_BUS,
                                SubType::STATION_BTS_OR_MRT,
                                SubType::TERMINAL_FERRY_OR_PORTS,
                                SubType::AIRPORT,
                                SubType::UNIVERSITY,
                                SubType::EXHIBITION_HALL,
                                SubType::PARKING_BUILDING,
                                SubType::BANK,
                                SubType::STADIUM,
                                SubType::MUSEUM,
                                SubType::THEME_PARK_OR_ZOO,
                            ])),
                            default => collect(),
                        };

                        return $filteredTypes->mapWithKeys(fn ($type) => [
                            $type->value => $type->getLabel(),
                        ])->toArray();
                    })
                    ->hidden(fn (Get $get) => ! in_array($get('mooban_type'), [
                        MoobanType::PUBLIC->value,
                        MoobanType::RESIDENCE->value,
                        MoobanType::PMOC->value,
                    ]))
                    ->searchable()
                    ->reactive()
                    ->required(),
            ]);
    }

    private static function siteLocationsFieldset(): Fieldset
    {
        return Fieldset::make(__('residence.site_locations'))
            ->columnSpanFull()
            ->schema([
                Select::make('province_id')
                    ->label(__('app.province'))
                    ->options(fn (): array => ThailandProvince::query()
                        ->orderBy('name_in_english', 'asc')
                        ->get()
                        ->pluck('name_in_english', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->required(),
                Select::make('district_id')
                    ->label(__('app.district'))
                    ->options(function (Get $get): array {
                        $provinceId = (int) ($get('province_id') ?? 0);

                        if (! $provinceId) {
                            return [];
                        }

                        return ThailandDistrict::query()
                            ->where('province_id', $provinceId)
                            ->orderBy('name_in_english', 'asc')
                            ->get()
                            ->pluck('name_in_english', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->required(),
                Select::make('subdistrict_id')
                    ->label(__('app.subdistrict'))
                    ->options(function (Get $get): array {
                        $districtId = (int) ($get('district_id') ?? 0);

                        if (! $districtId) {
                            return [];
                        }

                        return ThailandSubDistrict::query()
                            ->where('district_id', $districtId)
                            ->orderBy('name_in_english', 'asc')
                            ->get()
                            ->pluck('name_in_english', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('full_address')
                    ->autosize()
                    ->required(),
                TextInput::make('google_location_link')
                    ->required(),
                TextInput::make('latitude')
                    ->label(__('residence.latitude'))
                    ->translateLabel()
                    ->numeric()
                    ->required(),
                TextInput::make('longitude')
                    ->label(__('residence.longitude'))
                    ->translateLabel()
                    ->numeric()
                    ->required(),
                Select::make('main_road')
                    ->label(__('residence.main_road'))
                    ->options(function (Get $get) {
                        return ThailandLocationService::getMainRoadsByDistricts(
                            array_map('intval', array_filter((array) $get('district_id')))
                        );
                    })
                    ->searchable()
                    ->preload()
                    ->live(),
            ]);
    }

    private static function siteInfoFieldset(): Fieldset
    {
        return Fieldset::make(__('residence.site_info'))
            ->columnSpanFull()
            ->schema([
                Select::make('completion_year')
                    ->label(__('residence.completion_year'))
                    ->options(array_combine(range(date('Y'), 1969), range(date('Y'), 1969)))
                    ->searchable()
                    ->required(),
                Select::make('developer_id')
                    ->label(__('app.developer'))
                    ->options(function ($state) {
                        return CdpCompany::query()
                            ->with('businessEntity')
                            ->where(function (Builder $query) use ($state): void {
                                $query
                                    ->whereHas('businessEntity', function (Builder $query): void {
                                        $query->where('business_category_id', CompanyTypeEnum::DEVELOPER->value);
                                    })
                                    ->whereNull('deleted_at');
                    
                                if (! empty($state)) {
                                    $query->orWhere('id', (int) $state);
                                }
                            })
                            ->get()
                            ->sortBy(fn ($developerCompany) => $developerCompany->businessEntity?->name)
                            ->mapWithKeys(function ($developerCompany) {
                                return [
                                    $developerCompany->id => "{$developerCompany->businessEntity?->name} ({$developerCompany->businessEntity?->name_th})",
                                ];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                SpatieMediaLibraryFileUpload::make('cover_image')
                    ->label(__('app.cover_image'))
                    ->required()
                    ->collection('cover_image')
                    ->disk('cos')
                    ->image()
                    ->openable(true),
                SpatieMediaLibraryFileUpload::make('logo_image')
                    ->label(__('app.logo'))
                    ->required()
                    ->collection('logo_image')
                    ->disk('cos')
                    ->image()
                    ->openable(true),
                SpatieMediaLibraryFileUpload::make('google_map_image')
                    ->label(__('residence.google_map_image'))
                    // ->required()
                    ->collection('google_map_image')
                    ->disk('cos')
                    ->image()
                    ->openable(true),
                // googlemappicture
                SpatieMediaLibraryFileUpload::make('guard_house_image')
                    ->label(__('residence.guard_house_image'))
                    // ->required()
                    ->collection('guard_house_image')
                    ->disk('cos')
                    ->image()
                    ->openable(true),
                Select::make('guard_house_lane_type')
                    ->label(__('residence.guard_house_lane_type'))
                    ->required()
                    ->options(EntryLaneType::options())
                    ->searchable(),
                TextInput::make('guard_house_entry_number')
                    ->label(__('residence.number_of_guard_house_entries'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Select::make('internet_provider_id')
                    ->label(__('residence.internet_provider'))
                    ->multiple()
                    ->options(
                        collect(InternetProviderCompany::cases())->mapWithKeys(fn ($case) => [
                            $case->value => Str::headline(str_replace('_', ' ', $case->name)),
                        ])->toArray()
                    )
                    ->searchable(),
                Select::make('entrance_barrier_type')
                    ->label(__('residence.entrance_barrier_type'))
                    ->options(
                        collect(EntranceBarrierType::cases())->mapWithKeys(fn ($case) => [
                            $case->value => Str::headline(str_replace('_', ' ', $case->name)),
                        ])->toArray()
                    )
                    ->searchable(),
                Toggle::make('has_cctv')
                    ->label(__('residence.has_cctv'))
                    ->default(false)
                    ->live(), // For instant UI update of the dependent field

                TextInput::make('cctv_count')
                    ->label(__('residence.cctv_presence'))
                    ->numeric()
                    ->minValue(1) // ✅ Prevent 0
                    ->step(1)
                    ->hidden(fn (Get $get) => ! $get('has_cctv'))
                    ->required(fn (Get $get) => $get('has_cctv'))
                    ->dehydrated(), // ✅ Always send to the backend for processing (even if hidden)
                Toggle::make('has_roof'),
                Radio::make('receptionist')
                    ->visible(fn (Component $livewire): bool => $livewire instanceof CreateResidence)
                    ->label(__('residence.has_receptionist?'))
                    ->boolean()
                    ->columns(7)
                    ->required(),
            ]);
    }

    private static function subscriptionsFieldset(): Fieldset
    {
        return Fieldset::make(__('app.subscriptions'))
            ->columnSpanFull()
            ->schema([
                DatePicker::make('subscription_start_date')
                    ->label(__('app.subscription_start_date'))
                    ->format('Y-m-d')
                    ->required(),
                DatePicker::make('subscription_end_date')
                    ->label(__('app.subscription_end_date'))
                    ->format('Y-m-d')
                    ->required(),
            ]);
    }

    private static function activationStatusFieldset(): Fieldset
    {
        return Fieldset::make(__('residence.activation_status'))
            ->columnSpanFull()
            ->schema([
                Select::make('residence_activation_status_id')
                    ->label(__('app.status'))
                    ->options(fn (): array => ResidenceOptionsSupport::activationStatusOptions())
                    ->preload()
                    ->required(),
            ]);
    }
}
