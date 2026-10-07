<?php

namespace App\Helpers;

use App\Models\AmenityBooking;

class AmenityBookingRefNoGenerator
{
    public static function generate(): string
    {
        $ref_no = strtoupper(str()->random(12));

        // Check for reference number uniqueness
        $hasSameRefNo = AmenityBooking::where('ref_no', $ref_no)->count();
        if ($hasSameRefNo > 0) {
            $ref_no = static::generate();
        }

        return $ref_no;
    }
}
