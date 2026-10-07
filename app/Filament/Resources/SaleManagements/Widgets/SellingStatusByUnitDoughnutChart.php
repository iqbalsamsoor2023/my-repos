<?php

namespace App\Filament\Resources\SaleManagements\Widgets;

use App\Enums\SalesManagement\SellingStatusEnum;
use App\Enums\User\RoleType;
use App\Models\SaleAdvertisement;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;

class SellingStatusByUnitDoughnutChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return __('sale.selling_status_by_unit');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $user = auth()->user();

        $query = SaleAdvertisement::select('selling_status', DB::raw('count(selling_status) as count'))
            ->join('units', 'sale_advertisements.unit_id', '=', 'units.id')
            ->whereNull('units.deleted_at')
            ->groupBy('selling_status');

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);

            if ($residence) {
                $query = $query->where('sale_advertisements.residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::SALES_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
            if (count($residenceIdList) > 0) {
                $query = $query->whereIn('sale_advertisements.residence_id', $residenceIdList);
            }
        }

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $query->where('sale_advertisements.residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $query->where('unit_id', $unitIdFilter);
        }

        $sellingStatusFilter = $this->tableFilters['selling_status']['values'] ?? [];
        if ($sellingStatusFilter) {
            $query->whereIn('selling_status', $sellingStatusFilter);
        }

        $salePriceFromFilter = $this->tableFilters['sale_price_from']['sale_price_from'] ?? null;
        if ($salePriceFromFilter) {
            $query->where('sale_price', '>=', $salePriceFromFilter);
        }

        $salePriceToFilter = $this->tableFilters['sale_price_to']['sale_price_to'] ?? null;
        if ($salePriceToFilter) {
            $query->where('sale_price', '<=', $salePriceToFilter);
        }

        $availableForSaleFilter = $this->tableFilters['available_for_sale']['isActive'] ?? null;
        if ($availableForSaleFilter) {
            $query->availableForSale();
        }
        // end applying table filters

        $sellingStatusByUnit = $query->get();
        $sellingStatusByUnitChartData = [];

        foreach ($sellingStatusByUnit as $sellingStatusByUnit) {
            $data = [];
            $data['count'] = $sellingStatusByUnit->count;
            $data['selling_status'] = SellingStatusEnum::from($sellingStatusByUnit->selling_status)->getLabel();
            $data['color'] = SellingStatusEnum::from($sellingStatusByUnit->selling_status)->getColor();
            $sellingStatusByUnitChartData[] = $data;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Selling Status By Unit',
                    'data' => array_column($sellingStatusByUnitChartData, 'count'),
                    'backgroundColor' => array_column($sellingStatusByUnitChartData, 'color'),
                ],
            ],

            'labels' => array_column($sellingStatusByUnitChartData, 'selling_status'),
        ];
    }
}
