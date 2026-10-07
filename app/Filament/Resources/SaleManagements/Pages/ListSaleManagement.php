<?php

namespace App\Filament\Resources\SaleManagements\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SaleManagements\Widgets\TotalUnitWithSaleValue;
use App\Filament\Resources\SaleManagements\SaleManagementResource;
use App\Filament\Resources\SaleManagements\Widgets\ConstructionProgressByUnitDoughNutChart;
use App\Filament\Resources\SaleManagements\Widgets\ConstructionProgressByUnitStats;
use App\Filament\Resources\SaleManagements\Widgets\RemainingUnitWithSaleValue;
use App\Filament\Resources\SaleManagements\Widgets\SellingStatusByUnitDoughnutChart;
use App\Filament\Resources\SaleManagements\Widgets\SellingStatusByUnitStats;
use App\Filament\Resources\SaleManagements\Widgets\SoldOutUnitWithSaleValue;
use App\Filament\Resources\SaleManagements\Widgets\UnitByHouseTypeStats;
use App\Filament\Resources\SaleManagements\Widgets\UnitBySellingStatusTrendChart;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListSaleManagement extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = SaleManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UnitByHouseTypeStats::class,
            TotalUnitWithSaleValue::class,
            SoldOutUnitWithSaleValue::class,
            RemainingUnitWithSaleValue::class,
            ConstructionProgressByUnitDoughNutChart::class,
            ConstructionProgressByUnitStats::class,
            SellingStatusByUnitDoughnutChart::class,
            SellingStatusByUnitStats::class,
            UnitBySellingStatusTrendChart::class,
        ];
    }
}
