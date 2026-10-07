<?php

namespace App\Enums\Visitor;

enum VehicleType: int
{
    case CAR = 1;
    case TRUCK = 2;
    case MOTORBIKE = 3;
    case VAN = 4;
    case TAXI = 5;
    case PICKUP = 6;

    public function getLabel(): string
    {
        return match ($this) {
            self::CAR => __('vehicle.car'),
            self::TRUCK => __('vehicle.truck'),
            self::MOTORBIKE => __('vehicle.motorbike'),
            self::VAN => __('vehicle.van'),
            self::TAXI => __('vehicle.taxi'),
            self::PICKUP => __('vehicle.pickup'),
        };
    }
}
