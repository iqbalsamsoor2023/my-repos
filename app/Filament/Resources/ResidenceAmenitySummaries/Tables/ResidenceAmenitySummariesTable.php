<?php

namespace App\Filament\Resources\ResidenceAmenitySummaries\Tables;

use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Enums\Residence\MoobanType;
use App\Models\Residence;
use App\Models\ResidenceActivationStatus;
use App\Services\CustomerSuccessZoneService;
use App\Services\ThailandLocationService;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResidenceAmenitySummariesTable
{
    public static function configure(Table $table): Table
    {
        // Get top 15 amenities
        $topAmenities = DB::table('facilities_and_amenities as fa')
            ->select('fa.id', 'fa.name', DB::raw('COUNT(DISTINCT ra.residence_id) as residences_count'))
            ->join('residence_amenity as ra', function ($join) {
                $join->on('fa.id', '=', 'ra.facility_and_amenity_id')
                    ->whereNull('ra.deleted_at')
                    ->where('ra.is_active', 1);
            })
            ->join('residences as r', function ($join) {
                $join->on('ra.residence_id', '=', 'r.id')
                    ->whereNull('r.deleted_at');
            })
            ->where('fa.type', FacilityAmenityTypeEnum::AMENITY->value)
            ->groupBy('fa.id', 'fa.name')
            ->orderByDesc('residences_count')
            ->limit(15)
            ->get();

        // Build amenity columns dynamically
        $amenityColumns = $topAmenities->map(function ($amenity) {
            $columnKey = Str::snake($amenity->name);

            return TextColumn::make($columnKey)
                ->label($amenity->name)
                ->badge()
                ->getStateUsing(function (Residence $record) use ($amenity) {
                    $pivot = $record->residenceAmenities->firstWhere('facility_and_amenity_id', $amenity->id);

                    if (! $pivot) {
                        return 'Not set';
                    }

                    return $pivot->is_active === 1 ? 'Active' : 'Inactive';
                })
                ->color(fn (string $state) => match ($state) {
                    'Active' => 'success',
                    'Inactive' => 'danger',
                    'Not set' => 'gray',
                    default => 'gray',
                });
        });

        return $table
            ->columns(array_merge([
                TextColumn::make('residence_activation_status_id')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => ResidenceActivationStatus::pluck('status', 'id')[$state] ?? '-')
                    ->color(fn ($state) => match ($state) {
                        1 => 'gray',
                        2 => 'red',
                        3 => 'pink',
                        4 => 'blue',
                        5 => 'green',
                        6 => 'orange',
                        default => 'black',
                    })
                    ->toggleable(),
                TextColumn::make('subdistrict.district.province.name_in_english')
                    ->label(__('app.province'))
                    ->description(fn (Residence $record): string => $record->subdistrict->district->province->name_in_thai ?? '-')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subdistrict.district.name_in_english')
                    ->label(__('app.district'))
                    ->description(fn (Residence $record): string => $record->subdistrict->district->name_in_thai ?? '-')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subdistrict.name_in_english')
                    ->description(fn (Residence $record): string => $record->subdistrict->name_in_thai ?? '-')
                    ->label(__('app.subdistrict'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('residence.mooban_name'))
                    ->description(fn (Residence $record): string => $record->name_th ?? '-')
                    ->copyable()
                    ->searchable(
                        query: fn ($query, $search) => $query->where('name', 'like', "%{$search}%")->orWhere('name_th', 'like', "%{$search}%")
                    )
                    ->toggleable(),
                TextColumn::make('propertyManagementUser.name')
                    ->label(__('residence.mooban_id'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
            ], $amenityColumns->toArray()))
            ->defaultSort('updated_at', 'desc')
            ->filters([
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
                        Fieldset::make('Province, District, Subdistrict')
                            ->columns(3)
                            ->schema([
                                Select::make('customer_success_zone_ids')
                                    ->label(__('app.cs_zone'))
                                    ->options(fn (): array => CustomerSuccessZoneService::getOptions())
                                    ->searchable()
                                    ->native(false)
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set): void {
                                        $set('province', []);
                                        $set('district', []);
                                        $set('subdistrict', []);
                                    })
                                    ->multiple(),
                                Select::make('province')
                                    ->label(__('app.province'))
                                    ->options(fn (Get $get): array => CustomerSuccessZoneService::getProvinceOptions(
                                        (array) ($get('customer_success_zone_ids') ?? [])
                                    ))
                                    ->searchable()
                                    ->native(false)
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set): void {
                                        $set('district', []);
                                        $set('subdistrict', []);
                                    })
                                    ->multiple(),

                                Select::make('district')
                                    ->label(__('app.district'))
                                    ->options(function (Get $get): array {
                                        $provinceIds = (array) $get('province');
                                        $customerSuccessZoneIds = (array) ($get('customer_success_zone_ids') ?? []);

                                        return CustomerSuccessZoneService::getDistrictOptions($provinceIds, $customerSuccessZoneIds);
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->preload()
                                    ->live()
                                    ->disabled(fn (Get $get): bool => empty((array) $get('province')))
                                    ->afterStateUpdated(fn (callable $set) => $set('subdistrict', []))
                                    ->multiple(),

                                Select::make('subdistrict')
                                    ->label(__('app.subdistrict'))
                                    ->options(function (Get $get): array {
                                        $districtIds = (array) $get('district');

                                        if (empty($districtIds)) {
                                            return [];
                                        }

                                        return ThailandLocationService::getSubdistrictsByDistricts($districtIds);
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->preload()
                                    ->live()
                                    ->disabled(fn (Get $get): bool => empty((array) $get('district')))
                                    ->multiple(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $customerSuccessZoneIds = array_map('intval', array_filter((array) ($data['customer_success_zone_ids'] ?? [])));

                        return $query
                            ->when(
                                ! empty($customerSuccessZoneIds),
                                fn (Builder $query): Builder => CustomerSuccessZoneService::constrainResidenceQueryByZones($query, $customerSuccessZoneIds)
                            )
                            ->when(! empty($data['province']), fn ($q) => $q->whereHas('subdistrict.district.province', fn ($q) => $q->whereIn('id', (array) $data['province'])))
                            ->when(! empty($data['district']), fn ($q) => $q->whereHas('subdistrict.district', fn ($q) => $q->whereIn('id', (array) $data['district'])))
                            ->when(! empty($data['subdistrict']), fn ($q) => $q->whereHas('subdistrict', fn ($q) => $q->whereIn('id', (array) $data['subdistrict'])));
                    }),
                SelectFilter::make('amenity_id')
                    ->label(__('app.amenity'))
                    ->options(
                        fn () => DB::table('facilities_and_amenities as fa')
                            ->select('fa.id', 'fa.name', DB::raw('COUNT(ra.residence_id) AS usage_count'))
                            ->leftJoin('residence_amenity as ra', function ($join) {
                                $join->on('fa.id', '=', 'ra.facility_and_amenity_id')
                                    ->whereNull('ra.deleted_at');
                            })
                            ->where('fa.type', FacilityAmenityTypeEnum::AMENITY->value)
                            ->whereNull('fa.deleted_at')
                            ->groupBy('fa.id', 'fa.name')
                            ->orderByDesc('usage_count')
                            ->limit(30)
                            ->pluck('fa.name', 'fa.id')
                            ->toArray()
                    )
                    ->searchable()
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            ! empty($data['value']),
                            fn (Builder $query) => $query->whereHas('residenceAmenities', function ($q) use ($data) {
                                $q->where('facility_and_amenity_id', $data['value'])
                                    ->where('is_active', true);
                            })
                        );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([])
            ->toolbarActions([]);
    }
}
