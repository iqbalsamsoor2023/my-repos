<?php

namespace App\Filament\Resources\SaleManagements\Widgets;

use App\Enums\SalesManagement\SellingStatusEnum;
use App\Enums\User\RoleType;
use App\Models\SaleAdvertisement;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class UnitBySellingStatusTrendChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '400px';

    public function getHeading(): string|Htmlable|null
    {
        return __('sale.unit_by_selling_status_trend');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $sellingStatusEnumList = SellingStatusEnum::cases();

        $sellingStatusList = collect($sellingStatusEnumList)->map(function (SellingStatusEnum $status) {
            return
                [
                    'id' => $status->value,
                    'name' => $status->getLabel(),
                ];
        });

        $sellingStatusQuery = SaleAdvertisement::join('units', 'sale_advertisements.unit_id', '=', 'units.id')
            ->whereNull('units.deleted_at');

        // get the residence id list by user role
        $user = auth()->user();

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $sellingStatusQuery->where('sale_advertisements.residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::SALES_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
            if (count($residenceIdList) > 0) {
                $sellingStatusQuery->whereIn('sale_advertisements.residence_id', $residenceIdList);
            }
        }

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $sellingStatusQuery->where('sale_advertisements.residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $sellingStatusQuery->where('unit_id', $unitIdFilter);
        }

        $sellingStatusFilter = $this->tableFilters['selling_status']['values'] ?? [];
        if ($sellingStatusFilter) {
            $sellingStatusQuery->whereIn('selling_status', $sellingStatusFilter);
        }

        $salePriceFromFilter = $this->tableFilters['sale_price_from']['sale_price_from'] ?? null;
        if ($salePriceFromFilter) {
            $sellingStatusQuery->where('sale_price', '>=', $salePriceFromFilter);
        }

        $salePriceToFilter = $this->tableFilters['sale_price_to']['sale_price_to'] ?? null;
        if ($salePriceToFilter) {
            $sellingStatusQuery->where('sale_price', '<=', $salePriceToFilter);
        }

        // end applying table filters
        $sellingStatusData = [];
        foreach ($sellingStatusList as $key => $status) {
            $query = $sellingStatusQuery->clone()
                ->where('selling_status', $status['id']);

            $sellingStatusData[$key] = [
                'label' => $status['name'],
                'data' => Trend::query($query)
                    ->dateColumn('sale_advertisements.created_at')
                    ->between(
                        start: Carbon::now()->startOfMonth(),
                        end: Carbon::now()->endOfMonth(),
                    )
                    ->perDay()
                    ->count()
                    ->map(fn (TrendValue $value) => $value->aggregate),
                'fill' => false,
                'backgroundColor' => $this->getRevenueColor($status['id'], 0.2),
                'borderColor' => $this->getRevenueColor($status['id']),
                'tension' => 0.1,
            ];
        }

        $totalData = Trend::query(SaleAdvertisement::query())
            ->dateColumn('created_at')
            ->between(
                start: Carbon::now()->startOfMonth(),
                end: Carbon::now()->endOfMonth(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => $sellingStatusData,
            'labels' => $totalData->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getRevenueColor(int $zoneId, float $opacity = 1): string
    {
        $colors = [
            1 => 'rgb(255, 99, 132)',  // Zone 1: Red
            2 => 'rgb(54, 162, 235)',  // Zone 2: Blue
            3 => 'rgb(255, 206, 86)',  // Zone 4: Yellow
            4 => 'rgb(75, 192, 192)',  // Zone 3: Teal
            5 => 'rgb(153, 102, 255)', // Zone 5: Purple
            6 => 'rgb(255, 159, 64)',  // Zone 6 EEC: Orange
            7 => 'rgb(130, 215, 75)',  // Zone 8 North: Green
            8 => 'rgb(20, 120, 233)',  // Zone 10 South: Dark Blue
            9 => 'rgb(240, 128, 128)', // Zone 9 North East: Light Red
            10 => 'rgb(100, 149, 237)', // Zone 7 Central: Cornflower Blue
        ];

        $baseColor = $colors[$zoneId] ?? 'rgb(128, 128, 128)'; // Default Gray

        return $opacity < 1 ? str_replace('rgb', 'rgba', $baseColor).", $opacity)" : $baseColor;
    }
}
