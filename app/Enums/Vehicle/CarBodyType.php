<?php

namespace App\Enums\Vehicle;

enum CarBodyType: int
{
    case SEDAN = 1;
    case SUV = 2;
    case MPV = 3;
    case PICKUP = 4;
    case VAN = 5;
    case PPV = 6;

    public function label(): string
    {
        return match ($this) {
            self::SEDAN => 'Sedan',
            self::SUV => 'SUV',
            self::MPV => 'MPV',
            self::PICKUP => 'Pickup',
            self::VAN => 'Van',
            self::PPV => 'PPV',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray();
    }
}
