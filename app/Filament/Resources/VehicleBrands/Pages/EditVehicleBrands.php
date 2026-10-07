<?php

namespace App\Filament\Resources\VehicleBrands\Pages;

use App\Filament\Resources\VehicleBrands\VehicleBrandResource;
use Filament\Resources\Pages\EditRecord;

class EditVehicleBrands extends EditRecord
{
    protected static string $resource = VehicleBrandResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_vehicle_brand');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
