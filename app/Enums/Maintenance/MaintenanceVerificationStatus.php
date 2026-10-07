<?php

namespace App\Enums\Maintenance;

enum MaintenanceVerificationStatus: int
{
    case NOT_VERIFIED = 0;
    case VERIFIED = 1;

    public function label(): string
    {
        return match ($this) {
            self::NOT_VERIFIED => 'Not Verified',
            self::VERIFIED => 'Verified',
        };
    }
}
