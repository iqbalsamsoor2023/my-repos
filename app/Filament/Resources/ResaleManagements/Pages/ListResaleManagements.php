<?php

namespace App\Filament\Resources\ResaleManagements\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ResaleManagements\ResaleManagementResource;
use App\Filament\Resources\ResaleManagements\Widgets\ResaleByHouseTypeStats;
use App\Filament\Resources\ResaleManagements\Widgets\TotalUnitWithResaleValue;
use App\Filament\Resources\ResaleManagements\Widgets\UnitByHouseTypeStats;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListResaleManagements extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ResaleManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('resales-and-tenancies.new_resale_management')),
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
            TotalUnitWithResaleValue::class,
            ResaleByHouseTypeStats::class,
        ];
    }
}
