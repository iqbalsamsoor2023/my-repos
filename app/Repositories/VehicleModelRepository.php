<?php

namespace App\Repositories;

use App\Actions\VehicleModel\GetVehicleModelAction;
use Illuminate\Http\Request;

class VehicleModelRepository
{
    public function index(Request $request)
    {
        $getVehicleModelAction = new GetVehicleModelAction;

        return $getVehicleModelAction->execute($request);
    }
}
