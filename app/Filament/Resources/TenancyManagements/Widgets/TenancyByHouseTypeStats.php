<?php

namespace App\Filament\Resources\TenancyManagements\Widgets;

use App\Enums\Unit\HouseType;
use App\Enums\User\RoleType;
use App\Models\RentAdvertisement;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class TenancyByHouseTypeStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getCards(): array
    {
        $defaultColor = '#374151';
        $colorMap = [
            '#EF4444',
            '#3B82F6',
            '#F59E0B',
            '#10B981',
            '#9333EA',
            '#EC4899',
            '#60A5FA',
            '#374151',
        ];

        $resaleByHouseTypeColors = [];

        $houseTypeEnumList = HouseType::cases();

        foreach ($houseTypeEnumList as $key => $houseTypeEnum) {
            $resaleByHouseTypeColors[$houseTypeEnum->value] = $colorMap[$key] ?? $colorMap[(int) ($key % count($colorMap))];
        }

        $resaleByHouseTypeColors[null] = $defaultColor;

        $mappedHouseTypeList = collect($houseTypeEnumList)->mapWithKeys(function (HouseType $houseType) use ($resaleByHouseTypeColors) {
            return [
                $houseType->value => [
                    'label' => $houseType->getLabel(),
                    'color' => $resaleByHouseTypeColors[$houseType->value] ?? 'default',
                ],
            ];
        })->toArray();

        // add the default color for house type that is not in the list
        $mappedHouseTypeList[null] = [
            'label' => __('unit.unknown_house_type'),
            'color' => $resaleByHouseTypeColors[null] ?? $defaultColor,
        ];

        // prepare the query
        $tenancyByHouseTypeQuery = RentAdvertisement::availableForRent()->join('units', 'unit_id', '=', 'units.id')
            ->whereNull('units.deleted_at')
            ->groupBy(['house_type'])
            ->selectRaw('house_type, COUNT(*) as count');

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $tenancyByHouseTypeQuery->where('rent_advertisements.residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $tenancyByHouseTypeQuery->where('unit_id', $unitIdFilter);
        }

        $hasCustomRule = $this->tableFilters['has_custom_rule']['value'] ?? null;
        if ($hasCustomRule) {
            $tenancyByHouseTypeQuery->where('has_custom_rule', $hasCustomRule);
        }

        $isRentalUpfront = $this->tableFilters['is_rental_upfront']['value'] ?? null;
        if ($isRentalUpfront) {
            $tenancyByHouseTypeQuery->where('is_rental_upfront', $isRentalUpfront);
        }

        $rentPriceFrom = $this->tableFilters['rent_price_from']['value'] ?? null;
        if ($rentPriceFrom) {
            $tenancyByHouseTypeQuery->where('rent_price', '>=', $rentPriceFrom);
        }

        $rentPriceTo = $this->tableFilters['rent_price_to']['value'] ?? null;
        if ($rentPriceTo) {
            $tenancyByHouseTypeQuery->where('rent_price', '<=', $rentPriceTo);
        }

        $depositFrom = $this->tableFilters['deposit_from']['value'] ?? null;
        if ($depositFrom) {
            $tenancyByHouseTypeQuery->where('deposit', '>=', $depositFrom);
        }

        $depositTo = $this->tableFilters['deposit_to']['value'] ?? null;
        if ($depositTo) {
            $tenancyByHouseTypeQuery->where('deposit', '<=', $depositTo);
        }

        // end applying table filters

        // get the residence id list by user role
        $user = auth()->user();

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $tenancyByHouseTypeQuery->where('rent_advertisements.residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::RESALES_AND_TENANCY_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_rtm($user->id);
            if (count($residenceIdList) > 0) {
                $tenancyByHouseTypeQuery->whereIn('rent_advertisements.residence_id', $residenceIdList);
            }
        }

        // get the data from the query

        $tenancyByHouseType = $tenancyByHouseTypeQuery->get();

        $totalCount = $tenancyByHouseType->sum('count');

        // prepare the data for the chart

        $statsData = collect($mappedHouseTypeList)
            ->map(function ($value, $key) use ($tenancyByHouseType, $totalCount) {
                $tenancyByHouseTypeCount = $tenancyByHouseType->where('house_type', $key)->first()?->count ?? 0;

                if ($tenancyByHouseTypeCount > 0) {
                    $percentage = $totalCount > 0 ? round(($tenancyByHouseTypeCount / $totalCount) * 100, 2) : 0;
                    $color = $value['color'];

                    return Stat::make(
                        new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$value['label']}</span>"),
                        $tenancyByHouseTypeCount
                    )
                        ->value(new HtmlString("
                        <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                            <span style=\"color:{$color};font-weight:bold;\">{$tenancyByHouseTypeCount}</span>
                            <span style=\"text-align: right;font-size: 0.75em;\">({$percentage}%)</span>
                        </div>
                    "))
                        ->description(__('resales-and-tenancies.from_count_total', ['count' => $tenancyByHouseTypeCount]));
                }
            })
            ->filter()
            ->values();

        return $statsData->toArray();
    }
}