<?php

namespace App\Actions\VehicleModel;

use App\Models\VehicleModel;
use Illuminate\Http\Request;

class GetVehicleModelAction
{
    public function execute(Request $request)
    {
        $vehicleModels = VehicleModel::query();

        if (isset($request->type)) {
            $vehicleModels = $vehicleModels->where('type', $request->type);
        }

        if (isset($request->brand_id)) {
            $vehicleModels = $vehicleModels->where('vehicle_brand_id', $request->brand_id);
        }

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $vehicleModels->get();
        }

        return $vehicleModels->paginate(100);
    }
}
