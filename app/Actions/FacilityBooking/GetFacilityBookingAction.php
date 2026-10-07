<?php

namespace App\Actions\FacilityBooking;

use App\Models\AmenityBooking;
use App\Models\ResidenceAmenity;

class GetFacilityBookingAction
{
    public function execute($request)
    {
        $amenityBookings = AmenityBooking::with([
            'user',
            'unit',
            'amenityBookable',
        ])->where('amenity_bookable_type', ResidenceAmenity::class);

        if (isset($request->facility_id)) {
            $amenityBookings = $amenityBookings->where('amenity_bookable_id', $request->facility_id);
        }

        if (isset($request->user_id)) {
            $amenityBookings = $amenityBookings->where('user_id', $request->user_id);
        }

        if (isset($request->unit_id)) {
            $amenityBookings = $amenityBookings->where('unit_id', $request->unit_id);
        }

        if (isset($request->residence_id)) {
            $amenityBookings = $amenityBookings->whereHas('unit', function ($query) use ($request) {
                return $query->where('residence_id', $request->residence_id);
            });
        }

        return $amenityBookings->orderBy('id', 'DESC')->paginate(20);
    }
}
