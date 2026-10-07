<?php

namespace App\Filament\Resources\VehicleBrands\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VehicleBrands\VehicleBrandResource;
use Filament\Resources\Pages\ListRecords;

class ListVehicleBrands extends ListRecords
{
    protected static string $resource = VehicleBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_vehicle_brand')),
        ];
    }
}
