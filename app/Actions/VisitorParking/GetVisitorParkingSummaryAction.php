<?php

namespace App\Actions\VisitorParking;

use App\Actions\Calculation\GetCalculationAction;
use App\Enums\Parking\DiscountType;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class GetVisitorParkingSummaryAction
{
    public function execute(Request $request, VisitorLog $visitorLog)
    {
        // Get Parking Model
        $getVisitorParkingByResidenceIdAction = new GetVisitorParkingByResidenceIdAction;
        $parking = $getVisitorParkingByResidenceIdAction->execute($request->residence_id);

        // Get Calculation Model
        $getCalculationAction = new GetCalculationAction;

        $discountValue = $request->discount_value;
        $isPenalty = $request->is_penalty;
        $contentLanguage = $request->content_language;

        $request->merge([
            'parking_id' => $parking->id,
            'vehicle_type' => $visitorLog->vehicle_type,
            'is_stamp' => isset($request->is_stamp) ? $request->is_stamp : 0,
        ]);

        $calculation = $getCalculationAction->execute($request);

        if (empty($calculation)) {
            throw new ModelNotFoundException(__('api-response.error.calculation_not_found'));
        }

        $chartered_price = 0; // KIV

        // Get Visitor Arrival DateTime
        $visitorArrived = new Carbon($visitorLog->arrival_time);

        // Get Visitor Departure DateTime
        if (is_null($visitorLog->leave_time)) {
            $visitorDepart = Carbon::now();
        } else {
            $visitorDepart = new Carbon($visitorLog->leave_time);
        }

        $durationDiffinMinutes = $visitorDepart->diffInMinutes($visitorArrived, Carbon::DIFF_ABSOLUTE); // 1038 minutes
        $durationInOut = $visitorDepart->diff($visitorArrived, Carbon::DIFF_ABSOLUTE);
        $calculationHours = ($durationInOut->days * 24) + $durationInOut->h; // 17h
        $calculationMinutes = $durationInOut->i; // 18m
        $calculationSeconds = $durationInOut->s; // 56s
        $calculationValue = $calculationMinutes == 0 ? $calculationSeconds : $calculationMinutes; // 18h

        if ($calculation) {
            $charteredDurationCarbon = new Carbon($calculation->chartered_duration);
            $charteredDurationInMinutes = ($charteredDurationCarbon->hour * 60) + $charteredDurationCarbon->minute;

            // Chartered
            $amountToPay = 0;
            $charteredAmountToPay = 0;
            $durationBalanceToCalculateInMinutes = 0;
            $numberOfIterations = 0;

            if ($durationDiffinMinutes >= $charteredDurationInMinutes) {
                $chartered24HoursInMinutes = 1440;
                $durationDiffinHours = $calculationValue > 0 ? $calculationHours + 1 : $calculationHours;

                $durationBalanceToCalculateInMinutes = $durationDiffinHours * 60; // 4500

                $durationBeforeCalculation = $durationBalanceToCalculateInMinutes;
                // deduct voucher first
                if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::TIME->value) {
                    $durationBalanceToCalculateInMinutes = CalculateDiscountByTimeAction::execute($discountValue, $durationBalanceToCalculateInMinutes); // 3900
                }

                while ($durationBalanceToCalculateInMinutes >= $charteredDurationInMinutes) {
                    $durationBalanceToCalculateInMinutes -= $chartered24HoursInMinutes;
                    $numberOfIterations++;
                }
                $charteredAmountToPay = $numberOfIterations * $calculation->chartered_price;
                $durationBalanceToCalculateInMinutes = abs($durationBalanceToCalculateInMinutes) < 0 ? 0 : $durationBalanceToCalculateInMinutes;
                $amountToPay = $charteredAmountToPay;
            } elseif ($calculationHours == 0 && $calculationMinutes >= 0 && $calculationSeconds >= 0) {
                // if free duration more than total duration = free
                $calculateFreeParking = GetVisitorParkingSummaryAction::calculateFreeParking($calculation, $durationDiffinMinutes, $calculationSeconds);
                $amountToPay = data_get($calculateFreeParking, 'amountToPay');
                $durationBalanceToCalculateInMinutes = data_get($calculateFreeParking, 'durationBalanceToCalculateInMinutes');

                $durationBeforeCalculation = $durationBalanceToCalculateInMinutes;
                // deduct voucher after
                if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::TIME->value) {
                    $durationBalanceToCalculateInMinutes = CalculateDiscountByTimeAction::execute($discountValue, $durationBalanceToCalculateInMinutes);
                }
            } else {
                // if free duration less than total duration, then deduct first only round up
                $calculateFreeParking = GetVisitorParkingSummaryAction::calculateFreeParking($calculation, $durationDiffinMinutes, $calculationSeconds);
                $amountToPay = data_get($calculateFreeParking, 'amountToPay');
                $durationBalanceToCalculateInMinutes = data_get($calculateFreeParking, 'durationBalanceToCalculateInMinutes');

                $durationBeforeCalculation = $durationBalanceToCalculateInMinutes;
                // deduct voucher after
                if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::TIME->value) {
                    $durationBalanceToCalculateInMinutes = CalculateDiscountByTimeAction::execute($discountValue, $durationBalanceToCalculateInMinutes);
                }
            }
        }

        $h = floor($durationDiffinMinutes / 60);
        $timeDifference = $visitorDepart->diff($visitorArrived, Carbon::DIFF_ABSOLUTE);
        $hours = ($timeDifference->days * 24) + $timeDifference->h;
        $form = $hours * 60 + $timeDifference->i;
        $hours = floor($form / 60);
        $minutes = $form % 60;

        if ($isPenalty == 1) {
            $amount_paid = $request->amount_paid ?? 0;
            $balance = $amount_paid - $calculation->penalty;
            $change = max(0, $balance);

            if ($contentLanguage == 'th') {
                $response['duration'] = ($hours < 0 || $minutes < 0)
                    ? '0H'
                    : $hours.' ชั่วโมง '.$minutes.' นาที '.$durationInOut->s.' วินาที';
            } else {
                $response['duration'] = ($hours < 0 || $minutes < 0)
                    ? '0H'
                    : $hours.' hours '.$minutes.' minute '.$durationInOut->s.' seconds';
            }

            $response['amount_to_pay'] = ($calculation->penalty).' THB';
            $response['balance'] = $balance.' THB';
            $response['change'] = $change.' THB';
            $response['penalty'] = $calculation->penalty.' THB';
        } else {
            if ($durationBalanceToCalculateInMinutes >= 0) {
                $finalAmountToPay = HandlePositivePaymentAction::execute(
                    $amountToPay, // from chartered or 0 value
                    $durationBalanceToCalculateInMinutes,
                    $calculation,
                    $parking,
                    $discountValue
                );

                $amount_paid = $request->amount_paid ?? 0;
                $balance = $amount_paid - $finalAmountToPay;
                $change = max(0, $balance);

                if ($contentLanguage == 'th') {
                    $response['duration'] = ($hours < 0 || $minutes < 0)
                        ? '0H'
                        : $hours.' ชั่วโมง '.$minutes.' นาที '.$durationInOut->s.' วินาที';
                } else {
                    $response['duration'] = ($hours < 0 || $minutes < 0)
                        ? '0H'
                        : $hours.' hours '.$minutes.' minute '.$durationInOut->s.' seconds';
                }

                $response['amount_to_pay'] = ($finalAmountToPay).' THB';
                $response['balance'] = $balance.' THB';
                $response['change'] = $change.' THB';
            } elseif ($durationBalanceToCalculateInMinutes <= 0 && $charteredAmountToPay == 0) {
                $finalAmountToPay = 0;
                $amount_paid = $request->amount_paid ?? 0;
                $response['duration'] = '0H';
                $response['amount_to_pay'] = 0 .' THB';
                $response['balance'] = 0 .' THB';
                $response['change'] = 0 .' THB';
            } elseif ($charteredAmountToPay > 0) {
                $amount_paid = $request->amount_paid ?? 0;
                $balance = $amount_paid - $charteredAmountToPay;
                $change = max(0, $balance);

                if ($contentLanguage == 'th') {
                    $response['duration'] = ($hours < 0 || $minutes < 0)
                        ? '0H'
                        : $hours.' ชั่วโมง '.$minutes.' นาที '.$durationInOut->s.' วินาที';
                } else {
                    $response['duration'] = ($hours < 0 || $minutes < 0)
                        ? '0H'
                        : $hours.' hours '.$minutes.' minute '.$durationInOut->s.' seconds';
                }
                $response['amount_to_pay'] = ($charteredAmountToPay).' THB';
                $response['balance'] = $balance.' THB';
                $response['change'] = $change.' THB';
            }
        }

        // For Response
        $response['stamp'] = $request->is_stamp == 1 ? 'Yes' : 'No';
        $response['amount_paid'] = ($amount_paid).' THB';
        $response['durationOld'] = (int) $h;
        $response['rate_per_hour'] = $calculation->rate_per_hour;
        $response['is_stamp_value'] = array_sum([
            (int) $calculation->rate_per_hour,
            (int) $calculation->free_parking_minutes,
            (int) $calculation->chartered_duration,
            (int) $calculation->chartered_price,
        ]) == 0 ? false : true;
        $response['is_chartered_value'] = array_sum([
            (int) $calculation->chartered_duration,
            (int) $calculation->chartered_price,
        ]) == 0 ? false : true;

        $amountToPay = $finalAmountToPay ?? $charteredAmountToPay;

        if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::TIME->value) {
            $response['amount_before_discount'] = abs($durationBeforeCalculation) / 60 .($contentLanguage == 'th' ? ' ชั่วโมง' : ' Hours');
        } elseif ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::PRICE->value) {
            $durationBalanceToCalculateInHours = $durationBalanceToCalculateInMinutes / 60;
            $response['amount_before_discount'] = ($durationBalanceToCalculateInHours * $calculation->rate_per_hour) + $charteredAmountToPay.' THB';
        } else {
            $response['amount_before_discount'] = $amountToPay.' THB';
        }

        $response['chartered_parking_price'] = isset($calculation) && $calculation->chartered_duration !== '00:00:00' ? $calculation->chartered_price : 0;

        // convert chartered parking to minutes to display the correct formula
        $charteredDuration = new Carbon($calculation->chartered_duration);
        if ($charteredDuration->hour !== 0 || $charteredDuration->minute !== 0) {
            if ($charteredDuration->hour !== 0) {
                $charteredHour = $charteredDuration->hour;
                $charteredParkingMinutes = $charteredHour * 60;
                $response['chartered_parking'] = $charteredHour;
            } else {
                $charteredParkingMinutes = $charteredDuration->minute;
                $response['chartered_parking'] = $charteredParkingMinutes;
            }
        } else {
            // if chartered duration is 00:00:00, set to 0 minutes
            $charteredParkingMinutes = $charteredDuration->second > 0 ? 60 / $charteredDuration->second : 0;
            $response['chartered_parking'] = $charteredParkingMinutes;
        }

        $response['chartered_parking_in_minutes'] = $charteredParkingMinutes ?? 0;

        $freeParkingCarbon = Carbon::parse($calculation->free_parking_minutes);
        $response['free_parking'] = $contentLanguage == 'th' ? '(**จอดฟรี(*ถ้าไม่โดนราคาเหมาจ่าย)): ' : '(**for non-chartered only): ';

        if ($calculation->free_parking_minutes == '00:00:00') {
            $time = '0 '.($contentLanguage == 'th' ? 'วินาที' : 'seconds');
        } else {
            $time = $freeParkingCarbon->hour.' '.($contentLanguage == 'th' ? 'ชั่วโมง' : 'hours').' '.$freeParkingCarbon->minute.' '.($contentLanguage == 'th' ? 'นาที' : 'minutes');
        }

        $response['free_parking'] .= $time;

        if (is_null($parking->discount_type)) {
            $response['discountType'] = 'N/A';
            $response['discountValue'] = 'N/A';
        } else {
            $response['discountType'] = $parking->discount_type;

            $value = isset($discountValue) ? $discountValue : $request->discountValue;

            $response['discountValue'] = empty($value)
                ? ($parking->discount_type == 1
                    ? '0 THB'
                    : ($contentLanguage == 'th' ? '0 ชั่วโมง' : '0 hour'))
                : ($parking->discount_type == 1
                    ? $value.' THB'
                    : $value.($contentLanguage == 'th' ? ' ชั่วโมง' : ' hour'));
        }

        $response['type'] = ($parking->type == 1) ? 'Free' : (($parking->type == 2) ? 'Paid' : 'N/A');

        $response['formula'] = [
            'duration_to_pay' => 'Out Parking - In Parking = Total Parking Hours = '.$response['duration'],
            'parking_total' => 'Total Parking Fees = **Chartered Rate  + (Balance parking hours after chartered session x Rate Per Hour) OR (Total Parking Hours - Free Parking) x Rate Per Hour: '.$amountToPay.' THB',
            'terms' => '**'.' Subject to Terms & Conditions',
        ];

        $response['formulaTh'] = [
            'duration_to_pay' => 'เวลาสิ้นสุดการจอดรถ - เวลาเริ่มจอดรถ - เวลาจอดทั้งหมด** = เวลาจอดทั้งหมด = '.$response['duration'],
            'parking_total' => '(เวลาจอดทั้งหมด - เวลาเหมาจ่ายที่จอดเกิน**) x ราคาต่อชั่วโมง + ราคาเหมาจ่าย** - ส่วนลด** = ค่าจอดรถทั้งหมด = '.$amountToPay.' บาท',
            'terms' => '**'.' รายละเอียดทั้งหมดเป็นไปตามข้อกำหนดและเงื่อนไขของบริษัท',
        ];

        return $response;
    }

    private static function calculateFreeParking($calculation, $durationDiffinMinutes, $calculationSeconds)
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
