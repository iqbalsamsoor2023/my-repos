<?php

namespace App\Filament\Resources\SaleManagements\Widgets;

use App\Enums\Unit\HouseType;
use App\Enums\User\RoleType;
use App\Models\Unit;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class UnitByHouseTypeStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null; // disable polling for unnecessary queries

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
        ];

        $saleByHouseTypeColors = [];

        $houseTypeEnumList = HouseType::cases();
        foreach ($houseTypeEnumList as $key => $houseTypeEnum) {
            $saleByHouseTypeColors[$houseTypeEnum->value] = $colorMap[$key] ?? $colorMap[(int) ($key % count($colorMap))];
        }

        $mappedHouseTypeList = collect($houseTypeEnumList)->mapWithKeys(function (HouseType $houseType) use ($saleByHouseTypeColors) {
            return [
                $houseType->value => [
                    'label' => $houseType->getLabel(),
                    'color' => $saleByHouseTypeColors[$houseType->value] ?? 'default',
                ],
            ];
        })->toArray();

        // add the default color for house type that is not in the list
        $mappedHouseTypeList[null] = [
            'label' => __('unit.unknown_house_type'),
            'color' => $defaultColor,
        ];

        // prepare the query
        $unitByHouseTypeQuery = Unit::query()
            ->groupBy(['house_type'])
            ->selectRaw('house_type, COUNT(*) as count');

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $unitByHouseTypeQuery->where('residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $unitByHouseTypeQuery->where('unit_id', $unitIdFilter);
        }

        // end applying table filters

        // get the residence id list by user role
        $user = auth()->user();

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $unitByHouseTypeQuery->where('residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::SALES_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
            if (count($residenceIdList) > 0) {
                $unitByHouseTypeQuery->whereIn('residence_id', $residenceIdList);
            }
        }

        // get the data from the query

        $unitByHouseTypeData = $unitByHouseTypeQuery->get();

        $totalCount = $unitByHouseTypeData->sum('count');

        // prepare the data for the chart

        return collect($mappedHouseTypeList)
            ->map(function ($value, $key) use ($unitByHouseTypeData, $totalCount) {
                // search directly for null since housetype enum doesnt support null
                if ((int) $key === 0) {
                    $unitByHouseTypeCount = $unitByHouseTypeData->whereNull('house_type')->first()?->count ?? 0;
                } else {
                    $unitByHouseTypeCount = $unitByHouseTypeData->where('house_type', HouseType::from((int) $key))->first()?->count ?? 0;
                }

                if ($unitByHouseTypeCount > 0) {
                    $percentage = $totalCount > 0 ? round(($unitByHouseTypeCount / $totalCount) * 100, 2) : 0;

                    return Stat::make(
                        new HtmlString("<span style=\"color:{$value['color']};font-weight:bold;\">{$value['label']}</span>"),
                        $unitByHouseTypeCount
                    )
                        ->value(new HtmlString("
                        <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                            <span style=\"color:{$value['color']};font-weight:bold;\">{$unitByHouseTypeCount}</span>
                            <span style=\"text-align: right;font-size: 0.75em;\">({$percentage}%)</span>
                        </div>
                    "))
                        ->description(__('sale.from_count_total', ['count' => $totalCount]));
                }
            })
            ->filter()
            ->values()
            ->toArray();
    }
}
