<?php

namespace App\Enums\Company;

enum CompanyTypeEnum: int
{
    case SECURITY_GUARD = 1;
    case DEVELOPER = 2;
    case PROPERTY_MANAGEMENT = 3;

    public function label(): string
    {
        return match ($this) {
            self::SECURITY_GUARD => 'Security Guard',
            self::DEVELOPER => 'Developer',
            self::PROPERTY_MANAGEMENT => 'Property Management',
        };
    }
}
