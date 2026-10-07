<?php

namespace App\Actions\VisitorParking;

use App\Enums\Parking\DiscountType;
use App\Models\Calculation;
use App\Models\Parking;

class CalculatePaymentAction
{
    public static function execute($charteredAmountToPay, $durationBalanceToCalculateInMinutes, Calculation $calculation, Parking $parking, int $discountValue)
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
