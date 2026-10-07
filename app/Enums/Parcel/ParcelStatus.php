<?php

namespace App\Enums\Parcel;

enum ParcelStatus: int
{
    case PENDING_PICK_UP = 0;
    case PICKED_UP = 1;
    case NOT_MY_PARCEL = 2;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING_PICK_UP => __('parcel.pending_pickup'),
            self::PICKED_UP => __('parcel.picked_up'),
            self::NOT_MY_PARCEL => __('parcel.not_my_parcel'),
        };
    }
}
