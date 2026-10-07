<?php

namespace App\Actions\Amenity;

use App\Models\Amenity;
use Illuminate\Http\Request;

class GetAmenityAction
{
    public function execute(Request $request)
    {
        $amenity = Amenity::with('residence', 'otherAmenity');

        if (isset($request->residence_id)) {
            $amenity = $amenity->where('residence_id', $request->residence_id);
        }

        if (isset($request->amenity_name)) {
            $amenity = $amenity->where('amenity_name', $request->amenity_name);
        }

        if (isset($request->private_amenity_name)) {
            $amenity = $amenity->where('amenity_name', '!=', $request->private_amenity_name);
        }

        return $amenity->orderBy('id', 'DESC')->paginate(20);
    }
}
