<?php

namespace App\Repositories;

use App\Actions\Amenity\GetAmenityAction;
use Illuminate\Http\Request;

class AmenityRepository
{
    public function index(Request $request)
    {
        $getAmenityAction = new GetAmenityAction;
        $amenity = $getAmenityAction->execute($request);

        return $amenity;
    }
}
