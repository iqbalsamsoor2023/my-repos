<?php

namespace App\Enums\EmergencyContact;

enum DepartmentType: string
{
    case HOSPITAL = 'Hospital';
    case POLICE = 'Police';
    case FOUNDATION = 'Foundation';
    case FIRE_STATION = 'Fire Station';
    case OTHERS = 'Others';

    public static function fromTypeId(int $typeId): string
    {
        return match ($typeId) {
            1 => self::HOSPITAL->value,
            2 => self::POLICE->value,
            3 => self::FOUNDATION->value,
            4 => self::FIRE_STATION->value,
            default => self::OTHERS->value,
        };
    }
}
