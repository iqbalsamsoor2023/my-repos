<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Enums\Company\InsuranceTypeEnum;
use App\Enums\Residence\LocationTagType;
use App\Enums\Residence\MoobanType;
use App\Enums\Vehicle\CarBodyType;
use App\Enums\Vehicle\FuelType;
use App\Enums\Vehicle\MotorcycleBodyType;
use App\Enums\Vehicle\VehicleType;
use App\Exports\VehicleExport;
use App\Filament\Resources\Units\RelationManagers\VehiclesRelationManager;
use App\Models\ErpLocationTag;
use App\Models\ResidenceActivationStatus;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Policies\VehiclePolicy;
use App\Services\FilamentExport\FilamentExportBulkAction;
use App\Services\FilamentExport\FilamentTableExportSupport;
use App\Services\ThailandLocationService;
use App\Support\VehicleAgeCategorySupport;
use App\Support\VehicleInsuranceSupport;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->copyable()
                    ->toggleable()
                    ->description(fn (Vehicle $record): string => $record?->unit?->residence?->name_th ?? '-')
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VehiclesRelationManager),
                TextColumn::make('insurance_company_name')
                    ->label(__('company.insurance_company'))
                    ->copyable()
                    ->toggleable()
                    ->getStateUsing(fn (Vehicle $record): string => VehicleInsuranceSupport::displayName($record->insuranceCompany))
                    ->description(fn (Vehicle $record): ?string => VehicleInsuranceSupport::secondaryName($record->insuranceCompany))
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VehiclesRelationManager),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->copyable()
                    ->toggleable()
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VehiclesRelationManager),
                TextColumn::make('resident')
                    ->label(__('app.resident'))
                    ->getStateUsing(fn (Vehicle $record) => new HtmlString(
                        $record->user
                            ? __('app.name').': '.$record->user->name.'<br/>'.
                            __('app.email').': '.$record->user->email.'<br/>'.
                            __('app.phone_number').': '.$record->user->phone_no
                            : 'N/A'
                    ))
                    ->toggleable(),
                TextColumn::make('vehicleModel.type')
                    ->label(__('app.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state == VehicleType::CAR->value
                        ? __('vehicle.'.strtolower(VehicleType::CAR->name))
                        : __('vehicle.'.strtolower(VehicleType::MOTORCYCLE->name)))
                    ->toggleable(),
                ViewColumn::make(__('vehicle.vehicle_details'))
                    ->view('tables.columns.vehicle.vehicle-detail')
                    ->toggleable(),
                TextColumn::make('province.name_in_english')
                    ->label(__('app.province'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('vehicle_age')
                    ->label(__('vehicle.vehicle_age'))
                    ->toggleable(),
                IconColumn::make('is_access_card')
                    ->label(__('vehicle.is_access_card'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_car_sticker')
                    ->label(__('vehicle.is_car_sticker'))
                    ->boolean()
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Vehicle $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Vehicle $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('residence_filters')
                    ->visible(fn () => VehiclePolicy::isPropertyManager(Auth::user()))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('app.residence_mooban'))
                            ->columns(3)
                            ->schema([
                                Select::make('name')
                                    ->label(__('app.residence_mooban'))
                                    ->options(list_residences())
                                    ->searchable()
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['name'] ?? null,
                                fn (Builder $query) => $query->whereHas(
                                    'unit.residence',
                                    fn ($q) => $q->where('id', $data['name'])
                                )
                            );
                    }),

                Filter::make('mooban_filters')
                    ->visible(fn () => ! VehiclePolicy::isPropertyManager(Auth::user()))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Mooban Type & Sub Type')
                            ->columns(3)
                            ->schema([
                                Select::make('mooban_type')
                                    ->label(__('residence.mooban_type'))
                                    ->options(MoobanType::class)
                                    ->preload()
                                    ->multiple()
                                    ->live(),
                                Select::make('sub_type')
                                    ->label(__('residence.house_type'))
                                    ->options(function (Get $get) {
                                        $moobanTypes = (array) $get('mooban_type');

                                        if (empty($moobanTypes)) {
                                            return [];
                                        }

                                        return collect($moobanTypes)
                                            ->flatMap(function ($moobanType) {
                                                $enum = $moobanType instanceof MoobanType
                                                    ? $moobanType
                                                    : MoobanType::tryFrom($moobanType);

                                                return $enum?->allowedSubTypes() ?? [];
                                            })
                                            ->unique()
                                            ->mapWithKeys(fn ($subType) => [
                                                $subType->value => $subType->getLabel(),
                                            ])
                                            ->toArray();
                                    })
                                    ->multiple(),
                                Select::make('residence_id')
                                    ->label(__('app.residence_mooban'))
                                    ->options(list_residences())
                                    ->searchable(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['mooban_type']),
                                fn ($q) => $q->whereHas(
                                    'unit.residence',
                                    fn ($r) => $r->whereIn('mooban_type', (array) $data['mooban_type'])
                                )
                            )
                            ->when(
                                ! empty($data['sub_type']),
                                fn ($q) => $q->whereHas(
                                    'unit',
                                    fn ($u) => $u->whereIn('sub_type', (array) $data['sub_type'])
                                )
                            )
                            ->when(
                                ! empty($data['residence_id']),
                                fn ($q) => $q->whereHas(
                                    'unit.residence',
                                    fn ($r) => $r->where('id', $data['residence_id'])
                                )
                            );
                    }),

                Filter::make('province_filters')
                    ->visible(fn () => VehiclePolicy::isGlobalAdmin(Auth::user()))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Province, District, Subdistrict & Main Road')
                            ->columns(3)
                            ->schema([
                                Select::make('province')
                                    ->label(__('app.province'))
                                    ->options(fn () => ThailandLocationService::getProvinces())
                                    ->searchable()
                                    ->live()
                                    ->multiple(),
                                Select::make('district')
                                    ->label(__('app.district'))
                                    ->options(function (Get $get) {
                                        $provinceIds = (array) $get('province');

                                        if (empty($provinceIds)) {
                                            return [];
                                        }

                                        return ThailandLocationService::getDistrictsByProvinces($provinceIds);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->multiple(),
                                Select::make('subdistrict')
                                    ->label(__('app.subdistrict'))
                                    ->options(function (Get $get) {
                                        $districtIds = (array) $get('district');

                                        if (empty($districtIds)) {
                                            return [];
                                        }

                                        return ThailandLocationService::getSubdistrictsByDistricts($districtIds);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->multiple(),
                                Select::make('main_road')
                                    ->label(__('residence.main_road'))
                                    ->options(function (Get $get) {
                                        return ThailandLocationService::getMainRoadsByDistricts(
                                            array_map('intval', array_filter((array) $get('district')))
                                        );
                                    })
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['subdistrict'])) {
                            $query->whereHas(
                                'unit.residence',
                                fn ($r) => $r->whereIn('subdistrict_id', (array) $data['subdistrict'])
                            );
                        } elseif (! empty($data['district'])) {
                            $subdistrictIds = ThailandLocationService::getSubdistrictsByDistricts((array) $data['district']);
                            $query->whereHas(
                                'unit.residence',
                                fn ($r) => $r->whereIn('subdistrict_id', array_keys($subdistrictIds))
                            );
                        } elseif (! empty($data['province'])) {
                            $districtIds = array_keys(ThailandLocationService::getDistrictsByProvinces((array) $data['province']));
                            $subdistrictIds = ThailandLocationService::getSubdistrictsByDistricts($districtIds);
                            $query->whereHas(
                                'unit.residence',
                                fn ($r) => $r->whereIn('subdistrict_id', array_keys($subdistrictIds))
                            );
                        }

                        if (! empty($data['main_road'])) {
                            $query->whereHas(
                                'unit.residence',
                                fn ($r) => $r->where('main_road', 'LIKE', '%'.$data['main_road'].'%')
                            );
                        }

                        return $query;
                    }),

                Filter::make('status_filters')
                    ->label(__('app.status'))
                    ->visible(fn () => Auth::user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false)
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Activation Status')
                            ->columns(2)
                            ->schema([
                                Select::make('residence_activation_status_id')
                                    ->label(__('app.status'))
                                    ->options(fn () => ResidenceActivationStatus::pluck('status', 'id')->toArray())
                                    ->multiple(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['residence_activation_status_id']),
                                fn (Builder $query) => $query->whereHas('unit.residence', function ($q) use ($data) {
                                    $q->whereIn('residence_activation_status_id', $data['residence_activation_status_id']);
                                })
                            );
                    }),
                Filter::make('date_filters')
                    ->label(__('app.dates_filtering'))
                    ->visible(fn () => Auth::user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false)
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Created & Updated Filter')
                            ->columns(3)
                            ->schema([
                                DatePicker::make('created_from')
                                    ->label(__('app.created_from')),
                                DatePicker::make('created_until')
                                    ->label(__('app.created_until')),
                                DatePicker::make('updated_from')
                                    ->label(__('app.last_updated_from')),
                                DatePicker::make('updated_until')
                                    ->label(__('app.last_updated_until')),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['created_from']),
                                fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                ! empty($data['created_until']),
                                fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_until'])
                            )
                            ->when(
                                ! empty($data['updated_from']),
                                fn (Builder $q) => $q->whereDate('updated_at', '>=', $data['updated_from'])
                            )
                            ->when(
                                ! empty($data['updated_until']),
                                fn (Builder $q) => $q->whereDate('updated_at', '<=', $data['updated_until'])
                            );
                    }),
                Filter::make('vehicle_filters')
                    ->label(__('vehicle.vehicle_information_filters'))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('vehicle.vehicle_information_filters'))
                            ->columns(3)
                            ->schema([
                                Select::make('vehicle_type')
                                    ->label(__('vehicle.vehicle_type'))
                                    ->options([
                                        VehicleType::CAR->value => __('vehicle.car'),
                                        VehicleType::MOTORCYCLE->value => __('vehicle.motorcycle'),
                                    ])
                                    ->native(false)
                                    ->searchable()
                                    ->multiple()
                                    ->live(),

                                Select::make('car_brand')
                                    ->label(__('vehicle.car_brand'))
                                    ->options(function () {
                                        return VehicleBrand::whereHas('vehicleModels', function ($q) {
                                            $q->where('type', VehicleType::CAR->value);
                                        })->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->multiple()
                                    ->visible(
                                        fn (Get $get) => empty($get('vehicle_type')) ||
                                            in_array(VehicleType::CAR->value, (array) $get('vehicle_type'))
                                    ),

                                Select::make('motorcycle_brand')
                                    ->label(__('vehicle.motorcycle_brand'))
                                    ->options(function () {
                                        return VehicleBrand::whereHas('vehicleModels', function ($q) {
                                            $q->where('type', VehicleType::MOTORCYCLE->value);
                                        })->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->multiple()
                                    ->visible(
                                        fn (Get $get) => empty($get('vehicle_type')) ||
                                            in_array(VehicleType::MOTORCYCLE->value, (array) $get('vehicle_type'))
                                    ),

                                Select::make('fuel_type')
                                    ->label(__('vehicle.fuel_type'))
                                    ->options(FuelType::casesToOptions())
                                    ->native(false)
                                    ->searchable()
                                    ->multiple(),

                                Select::make('body_type')
                                    ->label(__('vehicle.body_type'))
                                    ->options(
                                        fn (callable $get) => $get('vehicle_type') === VehicleType::MOTORCYCLE->value
                                            ? MotorcycleBodyType::options()
                                            : CarBodyType::options()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->multiple(),

                                Select::make('insurance_company')
                                    ->label(__('vehicle.insurance_company'))
                                    ->relationship(
                                        name: 'insuranceCompany',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('type', InsuranceTypeEnum::VEHICLE_INSURANCE->value)
                                    )
                                    ->preload()
                                    ->searchable()
                                    ->multiple(),

                                Select::make('age_category')
                                    ->label(__('vehicle.vehicle_age_category'))
                                    ->options(VehicleAgeCategorySupport::filterOptions())
                                    ->multiple(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                ! empty($data['vehicle_type']),
                                fn ($q) => $q->whereHas('vehicleModel', fn ($q2) => $q2->whereIn('type', (array) $data['vehicle_type']))
                            )
                            ->when(
                                ! empty($data['car_brand']),
                                fn ($q) => $q->whereHas('vehicleModel.vehicleBrand', function ($q2) use ($data) {
                                    $q2->whereIn('vehicle_brands.id', (array) $data['car_brand'])
                                        ->whereHas('vehicleModels', fn ($q3) => $q3->where('type', VehicleType::CAR->value));
                                })
                            )
                            ->when(
                                ! empty($data['motorcycle_brand']),
                                fn ($q) => $q->whereHas('vehicleModel.vehicleBrand', function ($q2) use ($data) {
                                    $q2->whereIn('vehicle_brands.id', (array) $data['motorcycle_brand'])
                                        ->whereHas('vehicleModels', fn ($q3) => $q3->where('type', VehicleType::MOTORCYCLE->value));
                                })
                            )
                            ->when(
                                ! empty($data['fuel_type']),
                                fn ($q) => $q->whereIn('fuel_type', (array) $data['fuel_type'])
                            )
                            ->when(
                                ! empty($data['body_type']),
                                fn ($q) => $q->whereIn('body_type', (array) $data['body_type'])
                            )
                            ->when(
                                ! empty($data['insurance_company']),
                                fn ($q) => $q->whereIn('insurance_company_id', (array) $data['insurance_company'])
                            )
                            ->when(! empty($data['age_category']), function ($q) use ($data) {
                                VehicleAgeCategorySupport::applyCategoryFilters($q, (array) $data['age_category']);
                            });
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Vehicle-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (array $data, Component $livewire) {
                        $columns = FilamentTableExportSupport::visibleColumnNames($livewire);
                        $fileName = FilamentTableExportSupport::excelFileName($data['file_name'] ?? null, 'Vehicle-Report');
                        $selectedVehicleIds = FilamentTableExportSupport::selectedRecordIds($livewire);

                        $vehicles = Vehicle::query()
                            ->whereIn('id', $selectedVehicleIds, 'and', false)
                            ->latest('id')
                            ->get();

                        FilamentTableExportSupport::recordExportAudit(Auth::user(), 'exported vehicle records');

                        return Excel::download(new VehicleExport($vehicles, $columns), $fileName);
                    }),
                DeleteBulkAction::make()
                    ->hidden(Auth::user()?->hasRole('Admin') ?? false),
            ]);
    }
}
