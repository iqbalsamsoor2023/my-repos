<?php

namespace App\Filament\Resources\SaleManagements\Widgets;

use App\Enums\SalesManagement\ConstructionProgressEnum;
use App\Enums\User\RoleType;
use App\Models\SaleAdvertisement;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class ConstructionProgressByUnitDoughNutChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null; // disable polling for unnecessary queries

    public function getHeading(): string
    {
        return __('sale.construction_progress_by_unit');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $defaultColor = '#374151';

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
        $constructionProgressByUnitResult = $constructionProgressByUnitQuery->get();

        // prepare the data for the chart
        $constructionProgressByUnitChartData = [];

        foreach ($constructionProgressByUnitResult as $constructionProgressByUnit) {
            $data = [];
            $data['count'] = $constructionProgressByUnit->count;
            $data['construction_progress'] = ConstructionProgressEnum::tryFrom($constructionProgressByUnit->construction_progress) ? ConstructionProgressEnum::from($constructionProgressByUnit->construction_progress)->getLabel() : __('sale.construction_progress.unknown_construction_progress');
            $data['color'] = ConstructionProgressEnum::tryFrom($constructionProgressByUnit->construction_progress) ? ConstructionProgressEnum::from($constructionProgressByUnit->construction_progress)->getColor() : $defaultColor;
            $constructionProgressByUnitChartData[] = $data;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Construction Progress By Unit',
                    'data' => array_column($constructionProgressByUnitChartData, 'count'),
                    'backgroundColor' => array_column($constructionProgressByUnitChartData, 'color'),
                ],
            ],

            'labels' => array_column($constructionProgressByUnitChartData, 'construction_progress'),
        ];
    }
}