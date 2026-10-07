<?php

namespace App\Enums\FacilityBooking;

enum FacilityBookingStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case REJECT = 2;
    case COMPLETE = 3;
    case NO_SHOW = 4;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('app.pending'),
            self::APPROVED => __('app.approved'),
            self::REJECT => __('app.rejected'),
            self::COMPLETE => __('app.complete'),
            self::NO_SHOW => __('app.no_show'),
        };
    }
}
