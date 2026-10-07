<?php

namespace App\Enums\Unit;

enum PropertyTypeOld: int
{
    // wont use this
    case POOL_VILLA = 1;
    case SINGLE_HOME = 2;
    case TWIN_HOME = 3;
    case TOWN_HOME = 4;
    case CONDO_HIGH_RISE = 5;
    case CONDO_LOW_RISE = 6;
    case SERVICE_APARTMENT = 7;

    public function label(): string
    {
        return match ($this) {
            self::POOL_VILLA => 'Pool Villa',
            self::SINGLE_HOME => 'Single Home',
            self::TWIN_HOME => 'Twin Home',
            self::TOWN_HOME => 'Town Home',
            self::CONDO_HIGH_RISE => 'Condo High Rise',
            self::CONDO_LOW_RISE => 'Condo Low Rise',
            self::SERVICE_APARTMENT => 'Service Apartment',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
