<?php

namespace App\Actions\VisitorParking;

use Carbon\Carbon;

class GetCharteredPriceAction
{
    public function execute($amountToPay, $visitorDuration, $calculation)
    {
        $carbon = new Carbon($calculation['chartered_duration']);
        $minutes = $carbon->hour;
        $chartered_duration = ($minutes * 60) + $carbon->minute;

        if ($visitorDuration >= $chartered_duration) {
            $amountToPay = $amountToPay + $calculation['chartered_price'];
        } elseif ($calculation['chartered_price'] != 0.00) {
            $carbon = new Carbon($calculation['chartered_duration']);
            $minutes = $carbon->hour;
            $charteredDuration = ($minutes * 60) + $carbon->minute;

            $amountToPay = ($calculation['chartered_price']) * ((int) ($visitorDuration / $charteredDuration));
        }

        return $amountToPay;
    }
}
