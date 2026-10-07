<?php

namespace App\Services;

use App\Actions\Facility\GetFacilityAction;
use Illuminate\Http\Request;

class FacilityService
{
    public function index(Request $request)
    {
        $getFacilityAction = new GetFacilityAction;
        $facility = $getFacilityAction->execute($request);

        return $facility;
    }
}
