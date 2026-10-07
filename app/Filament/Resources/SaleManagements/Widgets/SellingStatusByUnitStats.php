<?php

namespace App\Filament\Resources\SaleManagements\Widgets;

use App\Enums\SalesManagement\SellingStatusEnum;
use App\Enums\User\RoleType;
use App\Models\SaleAdvertisement;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class SellingStatusByUnitStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null; // disable polling for unnecessary queries

    protected function getCards(): array
    {
        $user = auth()->user();

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

        $variationColors = [];

        $variationEnumList = SellingStatusEnum::cases();
        foreach ($variationEnumList as $key => $variationEnum) {
            $variationColors[$variationEnum->value] = $colorMap[$key] ?? $defaultColor;
        }

        $mappedVariationList = collect($variationEnumList)->mapWithKeys(function (SellingStatusEnum $variation) use ($variationColors) {
            return [
                $variation->value => [
                    'label' => $variation->getLabel(),
                    'color' => $variationColors[$variation->value] ?? 'default',
                ],
            ];
        })->toArray();

        // Add null variation to handle records with no variation specified
        $mappedVariationList[null] = [
            'label' => __('sale.selling_status_values.unknown_selling_status'),
            'color' => '#6B7280', // Gray color for unknown/null values
        ];

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

        $queryResult = $query->get();

        $totalCount = $queryResult->sum('count');
        $chartData = [];

        foreach ($queryResult as $result) {
            $data = [];
            $data['count'] = $result->count;
            $data['selling_status'] = SellingStatusEnum::from($result->selling_status)->getLabel();
            $chartData[] = $data;
        }

        // prepare the data for the chart
        return collect($mappedVariationList)
            ->map(function ($value, $key) use ($queryResult, $totalCount) {

                $individualCount = $queryResult->where('selling_status', $key)->first()?->count ?? 0;

                $percentage = $totalCount > 0 ? round(($individualCount / $totalCount) * 100, 2) : 0;
                $color = $value['color'];

                return Stat::make(
                    new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$value['label']}</span>"),
                    $individualCount
                )
                    ->value(new HtmlString("
                    <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                        <span style=\"color:{$color};font-weight:bold;\">{$individualCount}</span>
                        <span style=\"text-align: right;font-size: 0.75em;\">({$percentage}%)</span>
                    </div>
                "))
                    ->description(__('sale.from_count_total', ['count' => $totalCount]));
            })
            ->values()
            ->toArray();
    }
}