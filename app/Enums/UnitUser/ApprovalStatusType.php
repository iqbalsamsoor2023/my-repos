<?php

namespace App\Enums\UnitUser;

enum ApprovalStatusType: int
{
    case APPROVED = 1;
    case REJECTED = 2;

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => __('app.approved'),
            self::REJECTED => __('app.rejected'),
        };
    }
}
