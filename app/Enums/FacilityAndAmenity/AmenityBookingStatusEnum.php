<?php

namespace App\Enums\FacilityAndAmenity;

enum AmenityBookingStatusEnum: int
{
    case PENDING = 0;
    case BOOKED = 1;
    case REJECT = 2;
    case COMPLETE = 3;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('app.pending'),
            self::BOOKED => __('app.booked'),
            self::REJECT => __('app.rejected'),
            self::COMPLETE => __('app.completed'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
