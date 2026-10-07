<?php

namespace App\Actions\Parking;

use App\Models\Parking;
use Illuminate\Http\Request;

class GetParkingAction
{
    public function execute(Request $request)
    {
        $parking = Parking::with('residence');

        if (isset($request->residence_id)) {
            $parking = $parking->where('residence_id', $request->residence_id);
        }

        return $parking->orderBy('id', 'DESC')->paginate(25);
    }
}
