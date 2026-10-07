<?php

namespace App\Enums\FacilityAndAmenity;

enum AmenityTypeEnum: int
{
    case INDOOR = 1;
    case OUTDOOR = 2;

    public function label(): string
    {
        return match ($this) {
            self::INDOOR => 'Indoor',
            self::OUTDOOR => 'Outdoor',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
