<?php

namespace App\Services;

use App\Actions\VehicleBrand\GetVehicleBrandAction;
use Illuminate\Http\Request;

class BrandService
{
    public function index(Request $request)
    {
        $brandAction = new GetVehicleBrandAction;

        return $brandAction->execute($request);
    }
}
