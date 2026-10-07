<?php

namespace App\Actions\VisitorParking;

use App\Models\VisitorLog;
use Illuminate\Http\Request;

class GetVisitorParkingInvoiceAction
{
    public function execute(Request $request, VisitorLog $visitorLog)
    {
        $getVisitorParkingSummaryAction = new GetVisitorParkingSummaryAction;
        $visitorPakingSummary = $getVisitorParkingSummaryAction->execute($request, $visitorLog);

        $is_paid = isset($visitorLog->visitorParking->amount_paid) ? true : false;

        $amountPaidOld = ($visitorPakingSummary['amount_paid'] === null || $visitorPakingSummary['amount_paid'] === '0 THB') ? null : (int) $visitorPakingSummary['amount_paid'];
        $totalFeeOld = ($visitorPakingSummary['amount_to_pay'] === '0 THB') ? null : (int) $visitorPakingSummary['amount_to_pay'];

        if ($visitorPakingSummary['chartered_parking_in_minutes'] == 0) {
            $charteredParkingEn = 'No Chartered Duration';
            $charteredParkingTh = 'ไม่มีระยะเวลาจอดเหมา';
        } else {
            if ($visitorPakingSummary['chartered_parking_in_minutes'] >= 60) {
                // If the chartered duration is 1 hour or more, display in hours
                if ($request->content_language == 'th') {
                    $charteredParkingEn = 'คิดราคาเหมาจ่ายเมื่อจอดเกิน '.$visitorPakingSummary['chartered_parking'].' ชั่วโมง (**by 24 ชั่วโมง): '.$visitorPakingSummary['chartered_parking_price'].' บาท';
                } else {
                    $charteredParkingEn = 'Chartered Parking for '.$visitorPakingSummary['chartered_parking'].' hours (**by 24 Hours): '.$visitorPakingSummary['chartered_parking_price'].' THB';
                }
                $charteredParkingTh = 'คิดราคาเหมาจ่ายเมื่อจอดเกิน '.$visitorPakingSummary['chartered_parking'].' ชั่วโมง (**by 24 ชั่วโมง): '.$visitorPakingSummary['chartered_parking_price'].' บาท';
            } else {
                // If the chartered duration is less than 1 hour, display in minutes
                if ($request->content_language == 'th') {
                    $charteredParkingEn = 'คิดราคาเหมาจ่ายเมื่อจอดเกิน '.$visitorPakingSummary['chartered_parking'].' นาที (**by 24 ชั่วโมง): '.$visitorPakingSummary['chartered_parking_price'].' บาท';
                } else {
                    $charteredParkingEn = 'Chartered Parking for '.$visitorPakingSummary['chartered_parking'].' minutes (**by 24 Hours): '.$visitorPakingSummary['chartered_parking_price'].' THB';
                }
                $charteredParkingTh = 'คิดราคาเหมาจ่ายเมื่อจอดเกิน '.$visitorPakingSummary['chartered_parking'].' นาที (**by 24 ชั่วโมง): '.$visitorPakingSummary['chartered_parking_price'].' บาท';
            }
        }

        return [
            'duration' => $visitorPakingSummary['duration'],
            'type' => $visitorPakingSummary['type'],
            'amount_to_pay' => $visitorPakingSummary['amount_to_pay'],
            'amount_before_discount' => $visitorPakingSummary['amount_before_discount'],
            'penalty' => $visitorPakingSummary['penalty'] ?? 0 .' THB',
            'is_stamp' => $visitorPakingSummary['stamp'] ?? 'No',
            'is_stamp_value' => $visitorPakingSummary['is_stamp_value'],
            'is_chartered_value' => $visitorPakingSummary['is_chartered_value'],
            'discount_type' => $visitorPakingSummary['discountType'],
            'discount_received' => $visitorPakingSummary['discountValue'],
            'amount_paid' => $visitorPakingSummary['amount_paid'] ?? 0 .' THB',
            'change' => $visitorPakingSummary['change'] ?? 0 .' THB',
            'balance' => $visitorPakingSummary['balance'] ?? 0 .' THB',
            'formula_information' => [
                'arrive_at' => $visitorLog->arrival_time,
                'depart_at' => $visitorLog->leave_time,
                'rate_per_hour' => $visitorPakingSummary['rate_per_hour'] ?? 0 .' THB',
                'free_parking' => $visitorPakingSummary['free_parking'],
                'chartered_parking' => $charteredParkingEn,
                'discount_value' => $request->discount_value.' THB',
                'formula' => $visitorPakingSummary['formula'],
            ],
            'formula_information_th' => [
                'arrive_at' => $visitorLog->arrival_time,
                'depart_at' => $visitorLog->leave_time,
                'rate_per_hour' => $visitorPakingSummary['rate_per_hour'] ?? 0 .' บาท',
                'free_parking' => $visitorPakingSummary['free_parking'],
                'chartered_parking' => $charteredParkingTh,
                'discount_value' => $request->discount_value.' บาท',
                'formula' => $visitorPakingSummary['formulaTh'],
            ],
            // support old app
            'rate_per_hour' => $visitorPakingSummary['rate_per_hour'],
            'total_fee' => $totalFeeOld,
            'parking_hour' => $visitorPakingSummary['durationOld'],
            'amount' => $amountPaidOld,
            'is_paid' => $is_paid,
        ];
    }
}
