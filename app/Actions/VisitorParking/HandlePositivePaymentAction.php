<?php

namespace App\Actions\VisitorParking;

use App\Enums\Parking\DiscountType;

class HandlePositivePaymentAction
{
    public static function execute($charteredAmountToPay, $durationBalanceToCalculateInMinutes, $calculation, $parking, $discountValue)
    {
        $durationBalanceToCalculateInHours = $durationBalanceToCalculateInMinutes / 60;
        $calculateBalanceToPay = $durationBalanceToCalculateInHours * $calculation->rate_per_hour;

        if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::PRICE->value) {
            $calculateBalanceToPay = $calculateBalanceToPay - (int) $discountValue;
        }

        $calculateBalanceToPay = $calculateBalanceToPay + $charteredAmountToPay;

        return $calculateBalanceToPay;
    }
}
