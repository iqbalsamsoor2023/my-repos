<?php

namespace App\Enums\Vehicle;

enum FuelType: int
{
    case GASOLINE = 1;
    case DIESEL = 2;
    case PHEV = 3;
    case EV = 4;

    public function label(): string
    {
        return match ($this) {
            self::GASOLINE => 'Gasoline',
            self::DIESEL => 'Diesel',
            self::PHEV => 'PHEV (Hybrid)',
            self::EV => 'EV',
        };
    }

    public static function casesToOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
