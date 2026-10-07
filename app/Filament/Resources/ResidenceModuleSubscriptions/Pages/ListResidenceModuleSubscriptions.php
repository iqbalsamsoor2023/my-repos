<?php

namespace App\Filament\Resources\ResidenceModuleSubscriptions\Pages;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use App\Enums\Company\CompanyTypeEnum;
use App\Enums\Residence\LocationTagType;
use App\Enums\Residence\MoobanType;
use App\Filament\Resources\ResidenceModuleSubscriptions\ResidenceModuleSubscriptionResource;
use App\Helpers\CompanyHelper;
use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandProvince;
use App\Models\Erp\ThailandSubDistrict;
use App\Models\ErpLocationTag;
use App\Models\Residence;
use App\Models\ResidenceActivationStatus;
use App\Services\ThailandLocationService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListResidenceModuleSubscriptions extends ListRecords
{
    protected static string $resource = ResidenceModuleSubscriptionResource::class;

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        // Base query with necessary relationships
        $query = Residence::with([
            'residenceFeatures',
            'propertyManagementUser',
            'subdistrict.district',
        ]);

        return $query
            ->withCount([
                'units as distinct_user_count' => function ($subQuery) {
                    $subQuery->select(DB::raw('COUNT(DISTINCT users.id)'))
                        ->leftJoin('unit_user', 'unit_user.unit_id', '=', 'units.id')
                        ->leftJoin('users', 'users.id', '=', 'unit_user.user_id')
                        ->whereNull('units.deleted_at')
                        ->whereNull('unit_user.deleted_at')
                        ->whereNull('users.deleted_at');
                },
            ])
            ->selectSub(function ($sub) {
                return $sub->selectRaw('COALESCE(
                    COUNT(DISTINCT CASE WHEN unit_user.id IS NOT NULL THEN units.id END) * 100.0 /
                    NULLIF(COUNT(DISTINCT units.id), 0),
                    0
                )')
                    ->from('units')
                    ->leftJoin('unit_user', 'units.id', '=', 'unit_user.unit_id')
                    ->whereColumn('units.residence_id', 'residences.id')
                    ->whereNull('units.deleted_at')
                    ->whereNull('unit_user.deleted_at');
            }, 'sign_up_percentage');
    }

    public function getTableFilters(): array
    {
        return [
            Filter::make('mooban')
                ->columnSpan(3)
                ->schema([
                    Fieldset::make('Mooban Type & Sub Type')
                        ->columns(3)
                        ->schema([
                            Select::make('mooban_type')
                                ->options(collect(MoobanType::cases())->mapWithKeys(fn ($type) => [
                                    $type->value => $type->getLabel(),
                                ]))
                                ->preload()
                                ->multiple(),

                            Select::make('sub_type')
                                ->label(__('residence.house_type'))
                                ->options(function (Get $get) {
                                    $moobanTypes = $get('mooban_type');

                                    if (! is_array($moobanTypes) || empty($moobanTypes)) {
                                        return [];
                                    }

                                    $filteredSubTypes = collect($moobanTypes)
                                        ->map(fn ($moobanType) => MoobanType::tryFrom((int) $moobanType)) // Ensure it's cast to int
                                        ->filter() // Remove null values (invalid enums)
                                        ->flatMap(fn ($moobanType) => $moobanType->allowedSubTypes() ?? []) // Get allowed subtypes
                                        ->unique();

                                    return $filteredSubTypes->mapWithKeys(fn ($subType) => [
                                        $subType->value => $subType->getLabel(),
                                    ])->toArray();
                                })
                                ->multiple(),
                            Select::make('name')
                                ->label(__('app.residence_mooban'))
                                ->options(list_residences())
                                ->searchable(),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            ! empty($data['mooban_type']),
                            fn (Builder $query) => $query->whereIn('mooban_type', (array) $data['mooban_type'])
                        )
                        ->when(
                            ! empty($data['sub_type']),
                            fn (Builder $query) => $query->whereIn('sub_type', (array) $data['sub_type'])

                        )
                        ->when(
                            $data['name'],
                            fn (Builder $query): Builder => $query->whereId($data['name']),
                        );
                }),
            Filter::make('province_filters')
                ->columnSpan(3)
                ->schema([
                    Fieldset::make('Province, District, Subdistrict & Main Road')
                        ->columns(3)
                        ->schema([
                            Select::make('province')
                                ->label(__('app.province'))
                                ->options(fn () => ThailandProvince::get()->pluck('name_in_english', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->multiple(),

                            Select::make('district')
                                ->label(__('app.district'))
                                ->options(function (Get $get) {
                                    $provinceIds = (array) $get('province');

                                    if (empty($provinceIds)) {
                                        return [];
                                    }

                                    return ThailandDistrict::whereIn('province_id', $provinceIds)
                                        ->get()
                                        ->pluck('name_in_english', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->multiple(),

                            Select::make('subdistrict')
                                ->label(__('app.subdistrict'))
                                ->options(function (Get $get) {
                                    $districtIds = (array) $get('district'); // Ensure it's an array

                                    if (empty($districtIds)) {
                                        return [];
                                    }

                                    return ThailandSubDistrict::whereIn('district_id', $districtIds)
                                        ->get()
                                        ->pluck('name_in_english', 'id');
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
                                ->preload()
                                ->live(),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            ! empty($data['province']),
                            fn (Builder $query) => $query->whereHas('subdistrict.district.province', function ($q) use ($data) {
                                return $q->whereIn('id', (array) $data['province']);
                            })
                        )
                        ->when(
                            ! empty($data['district']),
                            fn (Builder $query) => $query->whereHas('subdistrict.district', function ($q) use ($data) {
                                return $q->whereIn('id', (array) $data['district']);
                            })
                        )
                        ->when(
                            ! empty($data['subdistrict']),
                            fn (Builder $query) => $query->whereHas('subdistrict', function ($q) use ($data) {
                                return $q->whereIn('id', (array) $data['subdistrict']);
                            })
                        )
                        ->when(
                            $data['main_road'] ?? null,
                            fn (Builder $query, $mainRoad): Builder => $query->where('main_road', 'LIKE', '%'.$mainRoad.'%')
                        );
                }),
            Filter::make('status_filters')
                ->label('Status')
                ->visible(fn () => Filament::auth()->user()->hasRole(['Super Admin', 'Admin']))
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
                            fn (Builder $query) => $query->whereIn('residence_activation_status_id', $data['residence_activation_status_id'])
                        );
                }),
            Filter::make('date_filters')
                ->label('Dates Filtering')
                ->visible(fn () => Filament::auth()->user()->hasRole(['Super Admin', 'Admin']))
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
                        ->when(! empty($data['created_from']), fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from'])
                        )
                        ->when(! empty($data['created_until']), fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_until'])
                        )
                        ->when(! empty($data['updated_from']), fn (Builder $q) => $q->whereDate('updated_at', '>=', $data['updated_from'])
                        )
                        ->when(! empty($data['updated_until']), fn (Builder $q) => $q->whereDate('updated_at', '<=', $data['updated_until'])
                        );
                }),
            Filter::make('companies')
            ->columnSpan(3)
            ->schema([
                Fieldset::make('Developer, Property Management & Security Guard')
                    ->columns(3)
                    ->schema([
                        Select::make('developer_id')
                            ->label(__('app.developer_company'))
                            ->searchable()
                            ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::DEVELOPER)),
                        Select::make('property_management_id')
                            ->label(__('app.property_management_company'))
                            ->searchable()
                            ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::PROPERTY_MANAGEMENT)),
                        Select::make('sgoc_company_id')
                            ->label(__('user.security_guard_company'))
                            ->searchable()
                            ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::SECURITY_GUARD)),
                    ]),
            ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['developer_id'] ?? null, fn (Builder $query, $value) => $query->where('developer_id', $value))
                            ->when($data['property_management_id'] ?? null, fn (Builder $query, $value) => $query->where('property_management_id', $value))
                            ->when($data['sgoc_company_id'] ?? null, fn (Builder $query, $value) => $query->where('sgoc_company_id', $value));
                    }),
        ];
    }
}
