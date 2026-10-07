<?php

namespace App\Enums\Vehicle;

enum VehicleType: int
{
    case CAR = 1;
    case MOTORCYCLE = 2;

    public function label(): string
    {
        return match ($this) {
            self::CAR => __('vehicle.car'),
            self::MOTORCYCLE => __('vehicle.motorcycle'),
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::CAR => 'Car',
            self::MOTORCYCLE => 'Bike',
        };
    }
}
