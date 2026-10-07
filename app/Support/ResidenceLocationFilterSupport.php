<?php

namespace App\Support;

use App\Services\CustomerSuccessZoneService;
use App\Services\ThailandLocationService;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class ResidenceLocationFilterSupport
{
    public static function makeProvinceFilters(
        bool $disableDistrictUntilProvince = false,
        bool $disableSubdistrictUntilDistrict = false,
        string $mainRoadColumn = 'residences.main_road'
    ): Filter {
        return Filter::make('province_filters')
            ->columnSpan(3)
            ->schema([
                Fieldset::make(__('residence.filter_location_main_road'))
                    ->columns(3)
                    ->schema([
                        Select::make('customer_success_zone_ids')
                            ->label(__('app.cs_zone'))
                            ->options(fn (): array => CustomerSuccessZoneService::getOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('province', []);
                                $set('district', []);
                                $set('subdistrict', []);
                                $set('main_road', null);
                            })
                            ->multiple(),
                        Select::make('province')
                            ->label(__('app.province'))
                            ->options(fn (Get $get): array => CustomerSuccessZoneService::getProvinceOptions(
                                self::normalizeIds((array) ($get('customer_success_zone_ids') ?? []))
                            ))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('district', []);
                                $set('subdistrict', []);
                                $set('main_road', null);
                            })
                            ->multiple(),
                        Select::make('district')
                            ->label(__('app.district'))
                            ->options(function (Get $get): array {
                                return CustomerSuccessZoneService::getDistrictOptions(
                                    self::normalizeIds((array) $get('province')),
                                    self::normalizeIds((array) ($get('customer_success_zone_ids') ?? [])),
                                );
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->disabled(fn (Get $get): bool => $disableDistrictUntilProvince && empty((array) $get('province')))
                            ->afterStateUpdated(function (callable $set): void {
                                $set('subdistrict', []);
                                $set('main_road', null);
                            })
                            ->multiple(),
                        Select::make('subdistrict')
                            ->label(__('app.subdistrict'))
                            ->options(fn (Get $get): array => ThailandLocationService::getSubdistrictsByDistricts(
                                self::normalizeIds((array) $get('district'))
                            ))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->disabled(fn (Get $get): bool => $disableSubdistrictUntilDistrict && empty((array) $get('district')))
                            ->multiple(),
                        Select::make('main_road')
                            ->label(__('residence.main_road'))
                            ->options(fn (Get $get): array => ThailandLocationService::getMainRoadsByDistricts(
                                self::normalizeIds((array) $get('district'))
                            ))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live(),
                    ]),
            ])
            ->query(fn (Builder $query, array $data): Builder => self::applyLocationFilters(
                $query,
                $data,
                $mainRoadColumn,
            ));
    }

    public static function applyLocationFilters(
        Builder $query,
        array $data,
        string $mainRoadColumn = 'residences.main_road'
    ): Builder {
        $customerSuccessZoneIds = self::normalizeIds((array) ($data['customer_success_zone_ids'] ?? []));
        $provinceIds = self::normalizeIds((array) ($data['province'] ?? []));
        $districtIds = self::normalizeIds((array) ($data['district'] ?? []));
        $subdistrictIds = self::normalizeIds((array) ($data['subdistrict'] ?? []));
        $mainRoad = $data['main_road'] ?? null;

        return $query
            ->when(
                ! empty($customerSuccessZoneIds),
                fn (Builder $locationQuery): Builder => CustomerSuccessZoneService::constrainResidenceQueryByZones($locationQuery, $customerSuccessZoneIds)
            )
            ->when(
                ! empty($provinceIds),
                fn (Builder $locationQuery): Builder => $locationQuery->whereHas('subdistrict.district.province', fn (Builder $provinceQuery): Builder => $provinceQuery->whereIn('id', $provinceIds))
            )
            ->when(
                ! empty($districtIds),
                fn (Builder $locationQuery): Builder => $locationQuery->whereHas('subdistrict.district', fn (Builder $districtQuery): Builder => $districtQuery->whereIn('id', $districtIds))
            )
            ->when(
                ! empty($subdistrictIds),
                fn (Builder $locationQuery): Builder => $locationQuery->whereHas('subdistrict', fn (Builder $subdistrictQuery): Builder => $subdistrictQuery->whereIn('id', $subdistrictIds))
            )
            ->when(
                $mainRoad !== null && $mainRoad !== '',
                fn (Builder $locationQuery): Builder => $locationQuery->where($mainRoadColumn, 'LIKE', '%'.$mainRoad.'%')
            );
    }

    /**
     * @param  array<int|string|null>  $ids
     * @return array<int>
     */
    public static function normalizeIds(array $ids): array
    {
        $normalized = array_values(array_unique(array_map(
            'intval',
            array_filter($ids, static fn ($value): bool => $value !== null && $value !== '')
        )));

        sort($normalized);

        return $normalized;
    }
}
