<?php

namespace App\Actions\VisitorParking;

use App\Enums\Parking\DiscountType;
use Carbon\Carbon;

class CalculateTotalVisitorParkingAmountAction
{
    public function execute($request, $visitor_log, $parking, $calculation)
    {
        $parking_duration_in_minutes = self::parkingDurationInMinutes($visitor_log); // TOTAL PARKING DURATION BEFORE ANY DEDUCTION
        $parking_duration_in_minutes = self::deductFreeParkingTime($parking_duration_in_minutes, $calculation); // DEDUCT FREE PARKING FROM PARKING DURATION IF ANY
        $parking_duration_in_minutes = self::deductCharteredDuration($parking_duration_in_minutes, $calculation); // DEDUCT CHARTERED DURATION FROM PARKING DURATION IF QUALIFIED
        $parking_duration_in_minutes = self::deductTimeDiscount($parking_duration_in_minutes, $request, $parking); // DEDUCT TIME DISCOUNT FROM PARKING DURATION IF ANY
        $total_duration_parking_in_hours = self::convertTotalParkingDurationMinutesToHours($parking_duration_in_minutes);
        $amount_to_pay = self::totalAmountToPayAfterDeductAll($total_duration_parking_in_hours, $parking_duration_in_minutes, $calculation, $parking, $request);  // DEDUCT PRICE DISCOUNT IF ANY

        return $amount_to_pay;
    }

    private function parkingDurationInMinutes($visitor_log)
    {
        $arrival_time = new Carbon($visitor_log->arrival_time);
        $depart_time = is_null($visitor_log->leave_time) ? Carbon::now() : new Carbon($visitor_log->leave_time);

        $parking_duration_in_minutes = $depart_time->diffInMinutes($arrival_time); // total parking duration

        return $parking_duration_in_minutes;
    }

    private function deductFreeParkingTime($parking_duration_in_minutes, $calculation)
    {
        $free_parking_in_minutes = ((new Carbon($calculation['free_parking_minutes']))->hour) * 60 + (new Carbon($calculation['free_parking_minutes']))->minute; // 01:12:00 (60(carbon->hour) + 12(carbon->minute))
        $parking_duration_in_minutes = $parking_duration_in_minutes - $free_parking_in_minutes; // minus free parking minutes if any

        return $parking_duration_in_minutes;
    }

    private function deductCharteredDuration($parking_duration_in_minutes, $calculation)
    {
        $chartered_duration_in_minutes = ((new Carbon($calculation['chartered_duration']))->hour) * 60 + (new Carbon($calculation['free_parking_minutes']))->minute; // 01:12:00 (60(carbon->hour) + 12(carbon->minute))
        $is_qualified_for_chartered_duration = $parking_duration_in_minutes >= $chartered_duration_in_minutes ? true : false;
        if ($is_qualified_for_chartered_duration) {
            $parking_duration_in_minutes = $parking_duration_in_minutes - $chartered_duration_in_minutes;
        }
    }

    private function deductTimeDiscount($parking_duration_in_minutes, $request, $parking)
    {
        $has_time_discount = ($parking->is_discount_coupon == 1) && ($parking->discount_type == DiscountType::TIME->value);

        if ($has_time_discount) {
            $time_discount_in_minutes = $request->discount_value * 60;
            if ($time_discount_in_minutes > 0) {
                $parking_duration_in_minutes = $parking_duration_in_minutes - $time_discount_in_minutes;
            }
        }

        return $parking_duration_in_minutes;
    }

    private function convertTotalParkingDurationMinutesToHours($parking_duration_in_minutes)
    {
        $total_duration_parking_in_hours = floor($parking_duration_in_minutes / 60); // Get the number of whole hours

        // exceed 1 seconds will considered as 1 hours
        // $seconds = ($depart_time)->diff($arrival_time)->format('%S');
        // if($seconds >= 1) {
        // $total_duration_parking_in_hours = $total_duration_parking_in_hours + 1;

        return $total_duration_parking_in_hours;
    }

    private function totalAmountToPayAfterDeductAll($total_duration_parking_in_hours, $parking_duration_in_minutes, $calculation, $parking, $request)
    {
        $chartered_duration_in_minutes = ((new Carbon($calculation['chartered_duration']))->hour) * 60 + (new Carbon($calculation['free_parking_minutes']))->minute; // 01:12:00 (60(carbon->hour) + 12(carbon->minute))
        $is_qualified_for_chartered_duration = $parking_duration_in_minutes >= $chartered_duration_in_minutes ? true : false;

        $amount_to_pay = $total_duration_parking_in_hours * $calculation['rate_per_hour'];
        if ($is_qualified_for_chartered_duration) {
            $amount_to_pay = $amount_to_pay + $calculation->chartered_price;
        }

        // deduct price discount
        if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::PRICE->value) {
            $amount_to_pay = $amount_to_pay - $request->discount_value;
        }

        return $amount_to_pay;
    }
}
