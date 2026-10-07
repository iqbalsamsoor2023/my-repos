<?php

namespace App\Enums\FacilityAndAmenity;

enum FacilityAmenityTypeEnum: string
{
    case AMENITY = 'Amenity';
    case FACILITY = 'Facility';

    public function label(): string
    {
        return match ($this) {
            self::AMENITY => 'Amenity',
            self::FACILITY => 'Facility',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
