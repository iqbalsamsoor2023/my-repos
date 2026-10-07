<?php

namespace App\Services;

use App\Actions\AmenityBooking\CreateAmenityBookingAction;
use App\Actions\AmenityBooking\GetAmenityBookingAction;
use App\Actions\AmenityBooking\GetAmenityBookingByIdAction;
use App\Actions\AmenityBooking\GetAmenityBookingByRefNoAction;
use App\Http\Requests\AmenityBooking\StoreAmenityBookingRequest;
use Illuminate\Http\Request;

class AmenityBookingService
{
    public function index(Request $request)
    {
        $getAmenityBookingAction = new GetAmenityBookingAction;
        $amenityBookings = $getAmenityBookingAction->execute($request);

        return $amenityBookings;
    }

    public function create(StoreAmenityBookingRequest $request)
    {
        $createAmenityBookingAction = new CreateAmenityBookingAction;
        $amenityBooking = $createAmenityBookingAction->execute($request);

        return $amenityBooking;
    }

    public function show(int $id)
    {
        $getAmenityBookingByIdAction = new GetAmenityBookingByIdAction;
        $amenityBooking = $getAmenityBookingByIdAction->execute($id);

        return $amenityBooking;
    }

    public function findByRefNumber(string $ref_no)
    {
        $getAmenityBookingByRefNoAction = new GetAmenityBookingByRefNoAction;
        $amenityBooking = $getAmenityBookingByRefNoAction->execute($ref_no);

        return $amenityBooking;
    }
}
