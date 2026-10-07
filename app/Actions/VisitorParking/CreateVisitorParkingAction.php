<?php

namespace App\Actions\VisitorParking;

use App\Exceptions\GeneralException;
use App\Http\Requests\VisitorParking\StoreVisitorParkingRequest;
use App\Models\Calculation;
use App\Models\VisitorParking;
use Illuminate\Http\JsonResponse;

class CreateVisitorParkingAction
{
    public function execute(StoreVisitorParkingRequest $request, Calculation $calculation, $finalAmountToPay)
    {
        $request->merge([
            'amount_to_pay' => $finalAmountToPay,
            'calculation_id' => $calculation->id,
            'calculation_records' => $calculation,
        ]);

        $visitorParking = $this->createVisitorParking($request);
        $this->uploadVoucherImage($request, $visitorParking);

        return $visitorParking;
    }

    private function createVisitorParking(StoreVisitorParkingRequest $request)
    {
        $visitor_parking = VisitorParking::create($request->only([
            'visitor_log_id',
            'discount_value',
            'amount_to_pay',
            'amount_paid',
            'is_penalty',
            'is_stamp',
            'calculation_id',
            'calculation_records',
        ]));

        if ($visitor_parking == false) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating visitor parking');
        }

        return $visitor_parking;
    }

    private function uploadVoucherImage(StoreVisitorParkingRequest $request, VisitorParking $visitorParking)
    {
        if ($request->hasFile('voucher_image')) {
            $visitorParking->addMediaFromRequest('voucher_image')->withCustomProperties(['type' => 'voucher_image'])->toMediaCollection('voucher_image');
        }
    }
}
