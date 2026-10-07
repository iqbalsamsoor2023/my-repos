<?php

namespace App\Repositories;

use App\Actions\Facility\GetFacilityTimeslotAction;
use Illuminate\Http\Request;

class FacilityTimeslotRepository
{
    public function index(Request $request)
    {
        $getFacilityTimeslotAction = new GetFacilityTimeslotAction;
        $facilityTimeslot = $getFacilityTimeslotAction->execute($request);

        return $facilityTimeslot;
    }
}
