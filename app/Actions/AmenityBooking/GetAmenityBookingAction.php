<?php

namespace App\Actions\AmenityBooking;

use App\Models\AmenityBooking;

class GetAmenityBookingAction
{
    public function execute($request)
    {
        $amenityBookings = AmenityBooking::with([
            'user',
            'unit',
            'amenityBookable',
        ]);

        if (isset($request->user_id)) {
            $amenityBookings = $amenityBookings->where('user_id', $request->user_id);
        }

        if (isset($request->unit_id)) {
            $amenityBookings = $amenityBookings->where('unit_id', $request->unit_id);
        }

        if (isset($request->ref_no)) {
            $amenityBookings = $amenityBookings->where('ref_no', $request->ref_no);
        }

        if (isset($request->booking_date)) {
            $amenityBookings = $amenityBookings->whereDate('start_at', $request->booking_date);
        }

        return $amenityBookings->orderBy('id', 'DESC')->paginate(10);
    }
}
