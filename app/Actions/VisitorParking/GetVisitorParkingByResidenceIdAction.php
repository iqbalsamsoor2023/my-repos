<?php

namespace App\Actions\VisitorParking;

use App\Models\Parking;

class GetVisitorParkingByResidenceIdAction
{
    public function execute(int $residence_id)
    {
        $parking = Parking::select(
            'id',
            'residence_id',
            'type',
            'rate_mode',
            'is_discount_coupon',
            'discount_type'
        )->where('residence_id', $residence_id)->firstOrFail();

        return $parking;
    }
}
