<?php

namespace App\Enums\Company;

enum InsuranceTypeEnum: int
{
    case LIFE_INSURANCE = 1;
    case VEHICLE_INSURANCE = 2;
    case HOUSE_INSURANCE = 3;

    public function label(): string
    {
        return match ($this) {
            self::LIFE_INSURANCE => 'Life Insurance',
            self::VEHICLE_INSURANCE => 'Vehicle Insurance',
            self::HOUSE_INSURANCE => 'House Insurance',
        };
    }
}
