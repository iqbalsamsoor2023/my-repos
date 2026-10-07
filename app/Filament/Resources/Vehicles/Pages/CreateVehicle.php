<?php

namespace App\Filament\Resources\Vehicles\Pages;

use App\Filament\Resources\Vehicles\VehicleResource;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    public function getTitle(): string
    {
        return __('menu.create_vehicle');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        if ($data['vehicle_brand_id'] == 'Other') {
            $brand = VehicleBrand::where('name', 'LIKE', '%'.$data['vehicle_brand'].'%')->first();

            if (! $brand) {
                $brand = VehicleBrand::create([
                    'name' => $data['vehicle_brand'],
                ]);
            }

            $data['vehicle_brand_id'] = $brand->id;
        } else {
            $brand = VehicleBrand::whereId($data['vehicle_brand_id'])->first();
        }

        if ($data['vehicle_model_id'] == 'Other') {
            $vehicle_model = VehicleModel::where('name', $data['vehicle_model'])->first();

            if (! $vehicle_model) {
                $vehicle_model = VehicleModel::create([
                    'vehicle_brand_id' => $brand->id,
                    'name' => $data['vehicle_model'],
                    'type' => $data['type'],
                ]);
            }

            $data['vehicle_model_id'] = $vehicle_model->id;
        }

        return static::getModel()::create($data);
    }
}
