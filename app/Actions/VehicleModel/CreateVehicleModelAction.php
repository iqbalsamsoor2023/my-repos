<?php

namespace App\Actions\VehicleModel;

use App\Exceptions\GeneralException;
use App\Models\VehicleModel;
use Illuminate\Http\JsonResponse;

class CreateVehicleModelAction
{
    public function execute($request)
    {
        $vehicle_model = VehicleModel::create($request->only([
            'vehicle_brand_id',
            'name',
            'type',
        ]));

        if (! $vehicle_model) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating vehicle model');
        }

        return $vehicle_model;
    }
}
