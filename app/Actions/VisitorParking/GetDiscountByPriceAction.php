<?php

namespace App\Actions\VisitorParking;

use App\Enums\Parking\DiscountType;
use App\Models\Parking;

class GetDiscountByPriceAction
{
    public function execute($charteredDuration, $discount_value, Parking $parking)
    {
        // If discount by price, minus after calculate
        if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::PRICE->value) {
            $charteredDuration = $charteredDuration - $discount_value;
        }

        return $charteredDuration;
    }
}
