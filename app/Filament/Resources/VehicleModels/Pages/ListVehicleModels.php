<?php

namespace App\Filament\Resources\VehicleModels\Pages;

use App\Filament\Resources\VehicleModels\VehicleModelResource;
use App\Models\VehicleModel;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVehicleModels extends ListRecords
{
    protected static string $resource = VehicleModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return VehicleModel::query()
            ->with(['vehicles:id,vehicle_model_id,fuel_type', 'vehicleBrand', 'vehicleBrand.country'])
            ->select(['id', 'vehicle_brand_id', 'name', 'type', 'body_type', 'created_at', 'updated_at']);
    }
}
