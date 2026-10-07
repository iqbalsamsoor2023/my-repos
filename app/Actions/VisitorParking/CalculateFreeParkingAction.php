<?php

namespace App\Actions\VisitorParking;

class CalculateFreeParkingAction
{
    public function execute($calculation, $durationDiffinMinutes)
    {
        $freeParkingDuration = $calculation->free_parking_minutes;
        $freeParkingHours = explode(':', $freeParkingDuration);
        $freeParkingMinutes = intval($freeParkingHours[0]) * 60 + intval($freeParkingHours[1]);

        if ($durationDiffinMinutes < $freeParkingMinutes) {
            $durationBalanceToCalculateInMinutes = 0; // this consider as free parking
            $amountToPay = 0;
        } else {
            $durationBalanceToCalculateInMinutes = $durationDiffinMinutes - $freeParkingMinutes;
            if ($durationBalanceToCalculateInMinutes > 0 && $durationBalanceToCalculateInMinutes <= 60) {
                // if duration less than 1 minute, consider as 1 hour for duration in minutes
                $hour = 1;
                $durationBalanceToCalculateInMinutes = $hour * 60;
                $amountToPay = 0;
            } else {
                // if duration is more than 1 hour, calculate
                $hours = intdiv($durationBalanceToCalculateInMinutes, 60);
                $minutes = $durationBalanceToCalculateInMinutes % 60;

                if ($minutes > 0) {
                    $durationBalanceToCalculateInMinutes = $hours + 1;
                    $durationBalanceToCalculateInMinutes = $durationBalanceToCalculateInMinutes * 60;
                    $amountToPay = 0;
                } else {
                    // if duration minute is 0 and have balance in seconds
                    $durationBalanceToCalculateInMinutes = $hours + 1;
                    $durationBalanceToCalculateInMinutes = $durationBalanceToCalculateInMinutes * 60;
                    $amountToPay = 0;
                }
            }
        }

        return [
            'amountToPay' => $amountToPay,
            'durationBalanceToCalculateInMinutes' => $durationBalanceToCalculateInMinutes,
        ];
    }
}
