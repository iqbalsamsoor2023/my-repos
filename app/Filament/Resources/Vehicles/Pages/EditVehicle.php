<?php

namespace App\Filament\Resources\Vehicles\Pages;

use App\Filament\Resources\Vehicles\VehicleResource;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Filament\Resources\Pages\EditRecord;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_vehicle');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['residence_id'] = $this->record->unit->residence->id;
        $data['unit_id'] = $this->record->unit_id;
        $data['type'] = $this->record->vehicleModel->type ?? null;
        $data['vehicle_brand_id'] = $this->record->vehicleModel->vehicle_brand_id ?? null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['vehicle_brand_id'] == 'Other') {
            $vehicleBrand = VehicleBrand::where('name', 'LIKE', '%'.$data['vehicle_brand'].'%')->first();

            if (! $vehicleBrand) {
                $vehicleBrand = VehicleBrand::create([
                    'name' => $data['vehicle_brand'],
                ]);
            }

            $data['vehicle_brand_id'] = $vehicleBrand->id;
        } else {
            $vehicleBrand = VehicleBrand::whereId($data['vehicle_brand_id'])->first();
        }

        if ($data['vehicle_model_id'] == 'Other') {
            $vehicle_model = VehicleModel::where('name', $data['vehicle_model'])->first();

            if (! $vehicle_model) {
                $vehicle_model = VehicleModel::create([
                    'vehicle_brand_id' => $vehicleBrand->id,
                    'name' => $data['vehicle_model'],
                    'type' => $data['type'],
                ]);
            }

            $data['vehicle_model_id'] = $vehicle_model->id;
        }

        return $data;
    }
}
