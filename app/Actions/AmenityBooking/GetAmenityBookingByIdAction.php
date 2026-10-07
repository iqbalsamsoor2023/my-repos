<?php

namespace App\Actions\AmenityBooking;

use App\Models\AmenityBooking;

class GetAmenityBookingByIdAction
{
    public function execute(int $id): AmenityBooking
    {
        return AmenityBooking::with(['user', 'unit', 'amenityBookable'])->findOrFail($id);
    }
}
