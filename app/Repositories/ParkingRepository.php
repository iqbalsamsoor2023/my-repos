<?php

namespace App\Repositories;

use App\Actions\Parking\GetParkingAction;
use Illuminate\Http\Request;

class ParkingRepository
{
    public function index(Request $request)
    {
        $getParkingAction = new GetParkingAction;
        $parking = $getParkingAction->execute($request);

        return $parking;
    }
}
