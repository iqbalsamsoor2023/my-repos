<?php

namespace App\Actions\VisitorParking;

class CalculateDiscountByTimeAction
{
    public static function execute($discountValue, $durationBeforeDiscountinMinutes)
    {
        $discount_value = $discountValue * 60;

        if ($durationBeforeDiscountinMinutes > 0) {
            $durationToPay = $durationBeforeDiscountinMinutes - $discount_value;
        } else {
            $durationToPay = 0; // if negative, set duration as 0
        }

        return $durationToPay;
    }
}
