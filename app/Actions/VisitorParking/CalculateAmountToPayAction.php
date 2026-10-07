<?php

namespace App\Actions\VisitorParking;

class CalculateAmountToPayAction
{
    public function execute($roundingUpDuration, $calculation)
    {
        if ($roundingUpDuration <= 0) {
            $amountToPay = $calculation['rate_per_hour'];
        } else {
            $amountToPay = ($roundingUpDuration * $calculation['rate_per_hour']);
        }

        return $amountToPay;
    }
}
