<?php

namespace App\Services;

use App\Actions\VisitorParking\CreateVisitorParkingAction;
use App\Actions\VisitorParking\GetVisitorParkingInvoiceAction;
use App\Enums\Parking\DiscountType;
use App\Enums\Residence\Features;
use App\Enums\Vehicle\VehicleType;
use App\Enums\Visitor\VehicleType as VisitorVehicleType;
use App\Exceptions\GeneralException;
use App\Http\Requests\VisitorParking\GetVisitorParkingCalculationRequest;
use App\Http\Requests\VisitorParking\StoreVisitorParkingRequest;
use App\Models\Calculation;
use App\Models\Parking;
use App\Models\ResidenceFeature;
use App\Models\VisitingArrangement;
use App\Models\VisitorLog;
use App\Models\VisitorParking;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\JsonResponse;

class VisitorParkingService
{
    public function create(StoreVisitorParkingRequest $request)
    {
        $visitorParking = VisitorParking::where('visitor_log_id', $request->visitor_log_id)->first();

        if ($visitorParking) {
            $visitorLog = $visitorParking->visitorLog;

            if ($visitorLog && is_null($visitorLog->leave_time)) {
                // Delete existing record if visitor hasn't left
                $visitorParking->forceDelete();
            } else {
                // Visitor already paid and completed
                throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, __('This visitor has paid the parking.'));
            }
        }

        // if (VisitorParking::where('visitor_log_id', $request->visitor_log_id)->exists()) {
        //     throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, __('This visitor has paid the parking.'));
        // }

        $visitorLog = VisitorLog::findOrFail($request->visitor_log_id);
        $visitingArrangement = VisitingArrangement::where('visitor_log_id', $request->visitor_log_id)->firstOrFail();

        $parking = Parking::where('residence_id', $visitingArrangement->residence_id)->first();

        if (! $parking) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Parking rate not yet set.');
        }

        // handle incoming request from router or sgoc2
        // using vehicletype enum by checking values from visitorvehicletype enum
        // because calculation setup dashboard used vehicletype enum and stored calculations using it
        $vehicleType = $request->vehicle_type ?? $visitorLog->vehicle_type;
        if ($vehicleType) {
            if (in_array($vehicleType, [VisitorVehicleType::CAR->value, 2, 4, 5, 6])) {
                $vehicleType = VehicleType::CAR->value;
            } else {
                $vehicleType = VehicleType::MOTORCYCLE->value;
            }
        }

        $calculation = Calculation::with('parking')
            ->where('parking_id', $parking->id)
            ->where('vehicle_type', (int) $vehicleType)
            ->where('is_stamp', (int) $request->is_stamp)
            ->first();

        if (! $calculation) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Parking calculation is not configured.');
        }

        if ($request->is_penalty == 1) {
            $finalAmountToPay = $calculation->penalty;
        } else {
            $visitorArrived = new Carbon($visitorLog->arrival_time);
            $visitorDepart = is_null($visitorLog->leave_time) ? Carbon::now() : new Carbon($visitorLog->leave_time);
            $durationDiffinMinutes = $visitorDepart->diffInMinutes($visitorArrived, Carbon::DIFF_ABSOLUTE);
            $durationInOut = $visitorDepart->diff($visitorArrived, Carbon::DIFF_ABSOLUTE);
            $calculationHours = ($durationInOut->days * 24) + $durationInOut->h;
            $calculationMinutes = $durationInOut->i;
            $calculationSeconds = $durationInOut->s;

            $calculationValue = $calculationMinutes == 0 ? $calculationSeconds : $calculationMinutes;
            if (isset($calculation)) {
                $charteredDurationCarbon = new Carbon($calculation->chartered_duration);
                $charteredDurationInMinutes = ($charteredDurationCarbon->hour * 60) + $charteredDurationCarbon->minute;
                // 3.1 Step - Chartered
                $amountToPay = 0;
                $durationBalanceToCalculateInMinutes = 0;
                $numberOfIterations = 0;

                if ($durationDiffinMinutes >= $charteredDurationInMinutes) {
                    $chartered24HoursInMinutes = 1440;
                    $durationDiffinHours = $calculationValue > 0 ? $calculationHours + 1 : 0;
                    $durationBalanceToCalculateInMinutes = $durationDiffinHours * 60;

                    // deduct voucher first
                    if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::TIME->value) {
                        $discountValue = $request->discount_value * 60;
                        if ($durationBalanceToCalculateInMinutes > 0) {
                            $durationBalanceToCalculateInMinutes = $durationBalanceToCalculateInMinutes - $discountValue;
                        } else {
                            $durationBalanceToCalculateInMinutes = 0; // if negative, set duration as 0
                        }
                    }

                    while ($durationBalanceToCalculateInMinutes >= $charteredDurationInMinutes) {
                        $durationBalanceToCalculateInMinutes -= $chartered24HoursInMinutes;
                        $numberOfIterations++;
                    }

                    $amountToPay = $numberOfIterations * $calculation->chartered_price;
                    $durationBalanceToCalculateInMinutes = $durationBalanceToCalculateInMinutes < 0 ? 0 : $durationBalanceToCalculateInMinutes;
                } elseif ($calculationHours == 0 && $calculationMinutes >= 0 && $calculationSeconds >= 0) {
                    // if free duration more than total duration = free
                    $calculateFreeParking = VisitorParkingService::calculateFreeParking($calculation, $durationDiffinMinutes, $calculationSeconds);
                    $amountToPay = data_get($calculateFreeParking, 'amountToPay');
                    $durationBalanceToCalculateInMinutes = data_get($calculateFreeParking, 'durationBalanceToCalculateInMinutes');
                } else {
                    // if free duration less than total duration, then deduct first only round up
                    $calculateFreeParking = VisitorParkingService::calculateFreeParking($calculation, $durationDiffinMinutes, $calculationSeconds);
                    $amountToPay = data_get($calculateFreeParking, 'amountToPay');
                    $durationBalanceToCalculateInMinutes = data_get($calculateFreeParking, 'durationBalanceToCalculateInMinutes');
                }
            }

            $h = floor($durationDiffinMinutes / 60);
            $timeDifference = $visitorDepart->diff($visitorArrived, Carbon::DIFF_ABSOLUTE);
            $hours = ($timeDifference->days * 24) + $timeDifference->h;
            $form = $hours * 60 + $timeDifference->i;
            $hours = floor($form / 60);
            $minutes = $form % 60;

            if ($durationBalanceToCalculateInMinutes >= 0) {
                $durationBalanceToCalculateInHours = $durationBalanceToCalculateInMinutes / 60;
                $calculateBalanceToPay = $durationBalanceToCalculateInHours * $calculation->rate_per_hour;
                if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::PRICE->value) {
                    $calculateBalanceToPay = $calculateBalanceToPay - (int) $request->discount_value;
                }
                $finalAmountToPay = $calculateBalanceToPay + $amountToPay;
            } elseif ($durationBalanceToCalculateInMinutes <= 0) {
                $finalAmountToPay = 0;
            }
        }
        $createVisitorParkingAction = new CreateVisitorParkingAction;
        $createVisitorParkingAction = $createVisitorParkingAction->execute($request, $calculation, $finalAmountToPay);

        return $createVisitorParkingAction;
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

    public function calculator($request)
    {
        $visitor_log = VisitorLog::find($request->visitor_log_id);
        // $vehicle_type = $visitor_log->vehicle_type == 3 ? VehicleType::MOTORCYCLE->value : VehicleType::CAR->value;
        if (! in_array($visitor_log->vehicle_type, [VisitorVehicleType::CAR, VisitorVehicleType::MOTORBIKE], true)) {
            $vehicle_type = VisitorVehicleType::CAR;
        }
        $parking = Parking::where('residence_id', $request->residence_id)->first();
        $arrival_time = new Carbon($visitor_log->arrival_time);
        $leave_time = new Carbon($visitor_log->leave_time);

        $calculation = Calculation::where('is_stamp', $request->is_stamp ?? 0)->where('parking_id', $parking->id)->where('vehicle_type', $vehicle_type)->first();
        if (is_null($calculation) == true) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'parking calculation not yet set');
        }

        if ($request->is_penalty == 1) {
            $amount_to_pay = $calculation->penalty;
        } else {
            $parking_duration_in_minutes = ($arrival_time)->diffInMinutes($leave_time, Carbon::DIFF_ABSOLUTE); // total parking duration

            // FREE PARKING
            $free_parking_in_minutes = ((new Carbon($calculation->free_parking_minutes))->hour) * 60 + (new Carbon($calculation->free_parking_minutes))->minute; // 01:12:00 (60(carbon->hour) + 12(carbon->minute))
            $parking_duration_in_minutes = $parking_duration_in_minutes - $free_parking_in_minutes; // minus free parking minutes if any

            // CHARTERED DURATION
            $chartered_duration_in_minutes = ((new Carbon($calculation->chartered_duration))->hour) * 60 + (new Carbon($calculation->free_parking_minutes))->minute; // 01:12:00 (60(carbon->hour) + 12(carbon->minute))
            $is_qualified_for_chartered_duration = $parking_duration_in_minutes >= $chartered_duration_in_minutes ? true : false;
            if ($is_qualified_for_chartered_duration) {
                $parking_duration_in_minutes = $parking_duration_in_minutes - $chartered_duration_in_minutes;
            }

            // TIME DISCOUNT
            $has_time_discount = ($parking->is_discount_coupon == 1) && ($parking->discount_type == DiscountType::TIME->value);

            // If discount by time, minus before calculate
            if ($has_time_discount) {
                $time_discount_in_minutes = $request->discount_value * 60;
                if ($time_discount_in_minutes > 0) {
                    $parking_duration_in_minutes = $parking_duration_in_minutes - $time_discount_in_minutes;
                }
            }

            // convert parking duration minutes to hour
            $total_duration_parking_in_hours = floor($parking_duration_in_minutes / 60); // Get the number of whole hours

            // exceed 1 seconds will considered as 1 hours
            $seconds = ($leave_time)->diff($arrival_time, Carbon::DIFF_ABSOLUTE)->format('%S');
            if ($seconds >= 1) {
                $total_duration_parking_in_hours = $total_duration_parking_in_hours + 1;
            }

            $amount_to_pay = $total_duration_parking_in_hours * $calculation['rate_per_hour'];
            if ($is_qualified_for_chartered_duration) {
                $amount_to_pay = $amount_to_pay + $calculation->chartered_price;
            }

            // PRICE DISCOUNT
            if ($parking->is_discount_coupon == 1 && $parking->discount_type == DiscountType::PRICE->value) {
                $amount_to_pay = $amount_to_pay - $request->discount_value;
            }
        }

        if ($request->is_submit) {
            $createVisitorParkingAction = new CreateVisitorParkingAction;
            $createVisitorParkingAction = $createVisitorParkingAction->execute($request, $calculation, $amount_to_pay);

            $change = $request->amount_paid - $amount_to_pay;
            $change = $change < 0 ? '0.00' : $change;
        }

        $date1 = new DateTime($arrival_time);
        $date2 = new DateTime($leave_time);
        $interval = $date1->diff($date2, Carbon::DIFF_ABSOLUTE);

        // Extract the components (days, hours, minutes, and seconds)
        $days = ($interval->days) * 24;
        $hours = $interval->h + $days;
        $minutes = $interval->i;
        $seconds = $interval->s;

        // key to return and display in front end
        /*
            parking_type : if free, FE will display free parking, else will pop out parking fee info (for visitor summary)
            is_stamp : if is_stamp = true, fe will hide the estamp button (for visitor summary)
            is_parking_fee_paid : if paid, fe will prompt print receipt button, else will prompt parking dialog.
        **/
        $discount_value = [
            'total_duration_parking_hours' => $hours,
            'total_duration_parking_minutes' => $minutes,
            'total_duration_parking_seconds' => $seconds,
            'total_parking_fee' => $amount_to_pay,
            'discount_type' => $parking->discount_type,
            'arrival_time' => $arrival_time,
            'leave_time' => $leave_time,
            'rate_per_hour' => $calculation->rate_per_hour,
            'free_parking' => $calculation->free_parking_minutes,
            'chartered_duration' => $calculation->chartered_duration,
            'chartered_price' => $calculation->chartered_price,
            'change' => $change ?? '0.00',
            'formula_total_parking_hour' => $request->header('Accept-Language') == 'th' ? 'เวลาสิ้นสุดการจอดรถ - เวลาเริ่มจอดรถ - เวลาจอดทั้งหมด** = เวลาจอดทั้งหมด = '.$hours.' ชั่วโมง '.$minutes.' นาที' : 'Out Parking - In Parking - Free Parking** = Total Parking Hours = '.$hours.'hour(s) '.$minutes.'minute(s)',
            'formula_total_parking_fees' => $request->header('Accept-Language') == 'th' ? '(เวลาจอดทั้งหมด - เวลาเหมาจ่ายที่จอดเกิน**) x ราคาต่อชั่วโมง + ราคาเหมาจ่าย** - ส่วนลด** = ค่าจอดรถทั้งหมด = '.$amount_to_pay.' บาท' : '(Total Parking Hours - Chartered Hours**) x Rate Per Hour + Chartered Price** - Discount** = Total Parking Fees = '.$amount_to_pay.' THB',
            'formula_chartered_parking' => $request->header('Accept-Language') == 'th' ? 'คิดราคาเหมาจ่ายเมื่อจอดเกิน '.substr($calculation->chartered_duration, 0, 2).': '.$calculation->chartered_price.' บาท' : 'Chartered Parking for '.substr($calculation->chartered_duration, 0, 2).' hours: '.$calculation->chartered_price.' THB',
            'parking_type' => $parking->type, // free or paid
            'is_stamp' => isset($createVisitorParkingAction) ? $createVisitorParkingAction->is_stamp : false,
            'is_parking_fee_paid' => isset($createVisitorParkingAction) ? true : false,
        ];

        return $discount_value;
    }

    public function calculationSummary(GetVisitorParkingCalculationRequest $request)
    {
        $visitorLog = VisitorLog::with('visitorParking')->find($request->visitor_log_id);

        $response = null;

        if ($visitorLog) {
            if (isset($visitorLog->visitorParking) && $visitorLog->visitorParking !== null) {
                $request->merge([
                    'amount_paid' => $visitorLog->visitorParking->amount_paid,
                    'is_stamp' => $visitorLog->visitorParking->is_stamp,
                    'is_penalty' => $visitorLog->visitorParking->is_penalty,
                    'discount_value' => $visitorLog->visitorParking->discount_value ?? $request->discount_value,
                ]);
            }

            $visitingArrangement = VisitingArrangement::where('visitor_log_id', $request->visitor_log_id)->first();

            // check if residence is already turned on the parking fees basic feature. (actually, should be done before creating the visitor log)
            ResidenceFeature::where('residence_id', $visitingArrangement->residence_id)->where('feature_id', Features::PARKING_FEE_BASIC->value)->active()->firstOrFail();

            $request->merge([
                'content_language' => $request->header('Accept-Language') ?? 'en',
                'residence_id' => $visitingArrangement->residence_id,
                'discount_value' => $request->discount_value ?? 0,
                'is_penalty' => $request->is_penalty ?? 0,
                'amount_paid' => $request->amount_paid,
                'is_stamp' => $request->is_stamp ?? 0,
            ]);

            $getVisitorParkingInvoiceAction = new GetVisitorParkingInvoiceAction;
            $response = $getVisitorParkingInvoiceAction->execute($request, $visitorLog);
        }

        return $response;
    }
}