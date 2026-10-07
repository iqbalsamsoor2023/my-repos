<?php

namespace App\Actions\AmenityBooking;

use App\Models\AmenityBooking;

class GetAmenityBookingByRefNoAction
{
    public function execute(string $ref_no): AmenityBooking
    {
        return AmenityBooking::with(['user', 'unit', 'amenityBookable'])->where('ref_no', $ref_no)->firstOrFail();
    }
}
