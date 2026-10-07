<?php

namespace App\Actions\VisitorParking;

use App\Enums\Parking\DiscountType;
use App\Models\Parking;

class GetDiscountByTimeAction
{
    public function execute($charteredDuration, $discount_value, Parking $parking)
    {
        // If discount by time, minus before calculate
        if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::TIME->value) {
            $discount_value = $discount_value * 60;

            if ($charteredDuration > 0) {
                $charteredDuration = $charteredDuration - $discount_value;
            } else {
                // If negative, set duration as 0
                $charteredDuration = 0;
            }
        }

        return $charteredDuration;
    }
}
