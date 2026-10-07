<?php

namespace App\Filament\Resources\ResaleManagements\Widgets;

use App\Enums\ResalesManagement\ResalesManagementStatus;
use App\Enums\SalesAndTenancies\BankLoanStatusEnum;
use App\Enums\Unit\HouseType;
use App\Enums\User\RoleType;
use App\Models\ResaleAdvertisement;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class ResaleByHouseTypeStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 3; // This sets the widget to display cards in 3 columns
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
            'color' => $defaultColor,
        ];

        // prepare the query
        $resaleByHouseTypeQuery = ResaleAdvertisement::join('units', 'unit_id', '=', 'units.id')
            ->whereNull('units.deleted_at')
            ->groupBy(['house_type'])
            ->selectRaw('house_type, COUNT(*) as count');

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $resaleByHouseTypeQuery->where('resale_advertisements.residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $resaleByHouseTypeQuery->where('unit_id', $unitIdFilter);
        }

        $ownershipDocumentFilter = $this->tableFilters['have_ownership_documents']['value'] ?? null;
        if ($ownershipDocumentFilter) {
            $resaleByHouseTypeQuery->where('have_ownership_documents', $ownershipDocumentFilter);
        }

        $bankLoanFilter = $this->tableFilters['bank_loan_status']['value'] ?? null;
        if ($bankLoanFilter) {
            $resaleByHouseTypeQuery->where('bank_loan_status', BankLoanStatusEnum::from($bankLoanFilter)->value);
        }

        $resalePriceFromFilter = $this->tableFilters['resale_price_from']['value'] ?? null;
        if ($resalePriceFromFilter) {
            $resaleByHouseTypeQuery->where('resale_price', '>=', $resalePriceFromFilter);
        }

        $resalePriceToFilter = $this->tableFilters['resale_price_to']['value'] ?? null;
        if ($resalePriceToFilter) {
            $resaleByHouseTypeQuery->where('resale_price', '<=', $resalePriceToFilter);
        }

        $resaleStatusFilter = $this->tableFilters['status']['value'] ?? null;
        if ($resaleStatusFilter) {
            $resaleByHouseTypeQuery->where('resale_advertisements.status', ResalesManagementStatus::from($resaleStatusFilter)->value);
        }

        $isActiveFilter = $this->tableFilters['is_active']['value'] ?? null;
        if ($isActiveFilter !== null) {
            $resaleByHouseTypeQuery->where('is_active', $isActiveFilter);
        }
        // end applying table filters

        // get the residence id list by user role
        $user = auth()->user();

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $resaleByHouseTypeQuery->where('units.residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::RESALES_AND_TENANCY_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_rtm($user->id);
            if (count($residenceIdList) > 0) {
                $resaleByHouseTypeQuery->whereIn('units.residence_id', $residenceIdList);
            }
        }

        // get the data from the query
        $resaleByHouseTypeResults = $resaleByHouseTypeQuery->get();

        $totalCount = $resaleByHouseTypeResults->sum('count');

        // prepare the data for the chart
        $statsData = collect($mappedHouseTypeList)
            ->map(function ($value, $key) use ($resaleByHouseTypeResults, $totalCount) {
                $resaleStatusByHouseTypeCount = $resaleByHouseTypeResults->where('house_type', $key)->first()?->count ?? 0;

                if ($resaleStatusByHouseTypeCount > 0) {
                    $percentage = $totalCount > 0 ? round(($resaleStatusByHouseTypeCount / $totalCount) * 100, 2) : 0;
                    $color = $value['color'];

                    return Stat::make(
                        new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$value['label']}</span>"),
                        $resaleStatusByHouseTypeCount
                    )
                        ->value(new HtmlString("
                        <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                            <span style=\"color:{$color};font-weight:bold;\">{$resaleStatusByHouseTypeCount}</span>
                            <span style=\"text-align: right;font-size: 0.75em;\">({$percentage}%)</span>
                        </div>
                    "))
                        ->description(__('resales-and-tenancies.from_count_total', ['count' => $totalCount]));
                }
            })
            ->filter()
            ->values();

        return $statsData->toArray();
    }
}