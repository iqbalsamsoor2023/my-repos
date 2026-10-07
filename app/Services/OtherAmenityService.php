<?php

namespace App\Services;

use App\Actions\OtherAmenity\GetOtherAmenityAction;
use Illuminate\Http\Request;

class OtherAmenityService
{
    public function index(Request $request)
    {
        $getOtherAmenityAction = new GetOtherAmenityAction;
        $otherAmenity = $getOtherAmenityAction->execute($request);

        return $otherAmenity;
    }
}
