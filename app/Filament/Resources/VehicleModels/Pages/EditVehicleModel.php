<?php

namespace App\Filament\Resources\VehicleModels\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\VehicleModels\VehicleModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleModel extends EditRecord
{
    protected static string $resource = VehicleModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
