<?php

namespace App\Filament\Resources\TenancyManagements\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TenancyManagements\TenancyManagementResource;
use App\Filament\Resources\TenancyManagements\Widgets\TenancyByHouseTypeStats;
use App\Filament\Resources\TenancyManagements\Widgets\TotalUnitWithTenancyValue;
use App\Filament\Resources\TenancyManagements\Widgets\UnitByHouseTypeStats;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListTenancyManagements extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TenancyManagementResource::class;

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
            TotalUnitWithTenancyValue::class,
            TenancyByHouseTypeStats::class,
        ];
    }
}
