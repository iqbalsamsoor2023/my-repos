<?php

namespace App\Actions\OtherAmenity;

use App\Models\OtherAmenity;
use Illuminate\Http\Request;

class GetOtherAmenityAction
{
    public function execute(Request $request)
    {
        $amenity = OtherAmenity::with('residence', 'amenities');

        if (isset($request->residence_id)) {
            $amenity = $amenity->where('residence_id', $request->residence_id);
        }

        return $amenity->orderBy('id', 'DESC')->paginate(20);
    }
}
