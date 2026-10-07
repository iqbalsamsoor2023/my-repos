<?php

namespace App\Actions\FacilityBooking;

use App\Enums\FacilityAndAmenity\AmenityBookingStatusEnum;
use App\Http\Requests\FacilityBooking\StoreFacilityBookingRequest;
use Illuminate\Support\Facades\DB;

class CheckBookingAvailabilityAction
{
    public function execute(StoreFacilityBookingRequest $request)
    {
        $from = $request->start_at;
        $to = $request->end_at;

        $query = DB::table('amenity_bookings');
        $query->where(function ($query) use ($from, $to) {
            $query->where(function ($query) use ($from, $to) {
                $query->where(DB::raw('start_at'), '>=', DB::raw("'".$from."'"));
                $query->where(DB::raw('start_at'), '<', DB::raw("'".$to."'"));
            });

            $query->orWhere(function ($query) use ($from, $to) {
                $query->where(DB::raw('end_at'), '>', DB::raw("'".$from."'"));
                $query->where(DB::raw('end_at'), '<', DB::raw("'".$to."'"));
            });

            $query->orWhere(function ($query) use ($from) {
                $query->where(DB::raw("'".$from."'"), '>=', DB::raw('start_at'));
                $query->where(DB::raw("'".$from."'"), '<', DB::raw('end_at'));
            });
        });

        $query->where('status', '!=', AmenityBookingStatusEnum::REJECT->value);
        $query->where('amenity_bookable_id', '=', $request->facility_id);
        $query->where('amenity_bookable_type', 'App\Models\ResidenceAmenity');

        if ($request->unit_id) {
            $query->where('unit_id', $request->unit_id);
        }

        return $query->count();
    }
}
