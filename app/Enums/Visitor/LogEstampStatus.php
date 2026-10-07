<?php

namespace App\Enums\Visitor;

enum LogEstampStatus: int
{
    // Remove deprecated
    case PENDING = 1;
    case ESTAMP = 2;
    case PARTIAL_ESTAMP = 3;
    case NOT_MY_VISITOR = 4;
    case CANCEL_BY_SG = 5;
    case BLACKLISTED = 6;
    case STAMP_BY_PM = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ESTAMP => 'Estamp',
            self::PARTIAL_ESTAMP => 'Partial Estamp',
            self::NOT_MY_VISITOR => 'Not My Visitor',
            self::CANCEL_BY_SG => 'Cancelled by SG',
            self::BLACKLISTED => 'Blacklisted',
            self::STAMP_BY_PM => 'Stamped by PM',
        };
    }
}
