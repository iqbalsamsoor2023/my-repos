<?php

namespace App\Enums\Vehicle;

enum MotorcycleBodyType: int
{
    case UNDERBONE = 1;
    case SCOOTER = 2;
    case SPORT = 3;
    case TOURING = 4;
    case OFF_ROAD = 5;
    case CRUISER = 6;
    case STANDARD = 7;

    public function label(): string
    {
        return match ($this) {
            self::UNDERBONE => 'Underbone',
            self::SCOOTER => 'Scooter',
            self::SPORT => 'Sport',
            self::TOURING => 'Touring',
            self::OFF_ROAD => 'Off-Road',
            self::CRUISER => 'Cruiser',
            self::STANDARD => 'Standard',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray();
    }
}
