<?php

namespace App\Listeners;

use App\Enums\FacilityAndAmenity\AmenityBookingStatusEnum;
use App\Events\AmenityBookingCreated;
use App\Helpers\AmenityBookingRefNoGenerator;

class GenerateAmenityBookingRefNo
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param AmenityBookingCreated $event
     * @return void
     */
    public function handle(AmenityBookingCreated $event)
    {
        $amenityBooking = $event->amenityBooking;
        $amenityBooking->ref_no = AmenityBookingRefNoGenerator::generate();
        $amenityBooking->status = AmenityBookingStatusEnum::BOOKED->value;
        $amenityBooking->save();
    }
}
