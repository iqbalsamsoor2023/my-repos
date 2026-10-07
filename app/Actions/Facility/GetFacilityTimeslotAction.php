<?php

namespace App\Actions\Facility;

use App\Models\ResidenceAmenity;
use App\Models\AmenityTimeslot;

class GetFacilityTimeslotAction
{
    public function execute($request)
    {
        $query = AmenityTimeslot::with('amenityTimeslotable');

        $query->where('amenity_timeslotable_type', ResidenceAmenity::class);

        if ($request->filled('facility_id')) {
            $query->where('amenity_timeslotable_id', $request->facility_id);
        }

        if ($request->filled('day')) {
            $query->where('day', $request->day);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return $query->orderByDesc('id')->paginate(20);
    }
}
