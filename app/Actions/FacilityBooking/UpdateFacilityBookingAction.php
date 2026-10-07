<?php

namespace App\Actions\FacilityBooking;

use App\Exceptions\GeneralException;
use App\Http\Requests\FacilityBooking\UpdateFacilityBookingRequest;
use App\Models\AmenityBooking;
use Illuminate\Http\JsonResponse;

class UpdateFacilityBookingAction
{
    public function execute(UpdateFacilityBookingRequest $request, AmenityBooking $facility_booking)
    {
        $facility_booking->update([
            'status' => $request->status,
            'updated_by' => auth()->user()->id,
        ]);

        if (! $facility_booking) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating booking amenity');
        }

        return $facility_booking;
    }
}
