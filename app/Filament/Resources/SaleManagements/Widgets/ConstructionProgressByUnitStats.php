<?php

namespace App\Filament\Resources\SaleManagements\Widgets;

use App\Enums\SalesManagement\ConstructionProgressEnum;
use App\Enums\User\RoleType;
use App\Models\SaleAdvertisement;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class ConstructionProgressByUnitStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

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
            '#374151',
        ];

        $constructionProgressEnumColors = [];

        $constructionProgressEnumList = ConstructionProgressEnum::cases();
        foreach ($constructionProgressEnumList as $key => $constructionProgressEnum) {
            $constructionProgressEnumColors[$constructionProgressEnum->value] = $colorMap[$key] ?? $defaultColor;
        }

        $mappedConstructionProgressList = collect($constructionProgressEnumList)->mapWithKeys(function (ConstructionProgressEnum $constructionProgress) use ($constructionProgressEnumColors) {
            return [
                $constructionProgress->value => [
                    'label' => $constructionProgress->getLabel(),
                    'color' => $constructionProgressEnumColors[$constructionProgress->value] ?? 'default',
                ],
            ];
        })->toArray();

        // Add null construction_progress to handle records with no construction progress specified
        $mappedConstructionProgressList[null] = [
            'label' => __('sale.construction_progress.unknown_construction_progress'),
            'color' => '#6B7280', // Gray color for unknown/null values
        ];

        // prepare the query
        $constructionProgressByUnitQuery = SaleAdvertisement::join('units', 'sale_advertisements.unit_id', '=', 'units.id')
            ->whereNull('units.deleted_at')
            ->groupBy(['construction_progress'])
            ->selectRaw('construction_progress, COUNT(*) as count');

        // get the residence id list by user role
        $user = auth()->user();

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $constructionProgressByUnitQuery->where('sale_advertisements.residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::SALES_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
            if (count($residenceIdList) > 0) {
                $constructionProgressByUnitQuery->whereIn('sale_advertisements.residence_id', $residenceIdList);
            }
        }

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $constructionProgressByUnitQuery->where('sale_advertisements.residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $constructionProgressByUnitQuery->where('unit_id', $unitIdFilter);
        }

        $sellingStatusFilter = $this->tableFilters['selling_status']['values'] ?? [];
        if ($sellingStatusFilter) {
            $constructionProgressByUnitQuery->whereIn('selling_status', $sellingStatusFilter);
        }

        $salePriceFromFilter = $this->tableFilters['sale_price_from']['sale_price_from'] ?? null;
        if ($salePriceFromFilter) {
            $constructionProgressByUnitQuery->where('sale_price', '>=', $salePriceFromFilter);
        }

        $salePriceToFilter = $this->tableFilters['sale_price_to']['sale_price_to'] ?? null;
        if ($salePriceToFilter) {
            $constructionProgressByUnitQuery->where('sale_price', '<=', $salePriceToFilter);
        }

        $availableForSaleFilter = $this->tableFilters['available_for_sale']['isActive'] ?? null;
        if ($availableForSaleFilter) {
            $constructionProgressByUnitQuery->availableForSale();
        }
        // end applying table filters

        // get the data from the query
        $constructionProgressByUnitResults = $constructionProgressByUnitQuery->get();

        $totalCount = $constructionProgressByUnitResults->sum('count');

        // prepare the data for the chart
        return collect($mappedConstructionProgressList)
            ->map(function ($value, $key) use ($constructionProgressByUnitResults, $totalCount) {
                $constructionProgressByUnit = $constructionProgressByUnitResults->where('construction_progress', $key)->first()?->count ?? 0;

                $percentage = $totalCount > 0 ? round(($constructionProgressByUnit / $totalCount) * 100, 2) : 0;
                $color = $value['color'];

                return Stat::make(
                    new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$value['label']}</span>"),
                    $constructionProgressByUnit
                )
                    ->value(new HtmlString("
                    <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                        <span style=\"color:{$color};font-weight:bold;\">{$constructionProgressByUnit}</span>
                        <span style=\"text-align: right;font-size: 0.75em;\">({$percentage}%)</span>
                    </div>
                "))
                    ->description(__('sale.from_count_total', ['count' => $totalCount]));
            })
            ->values()
            ->toArray();
    }
}