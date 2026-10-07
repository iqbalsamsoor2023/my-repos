<?php

namespace App\Enums\Parcel;

enum PickupType: int
{
    case RECIPIENT = 1;
    case ON_BEHALF = 2;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::RECIPIENT => __('parcel.recipient'),
            self::ON_BEHALF => __('parcel.on_behalf'),
        };
    }
}