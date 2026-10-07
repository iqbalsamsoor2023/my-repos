<?php

namespace App\Enums\Visitor;

enum VisitingArrangementStatus: int
{
    case MY_VISITOR = 1; // estamp
    case NOT_MY_VISITOR = 2;
    case CANCEL_BY_SG = 3;
    // case BLACKLISTED = 4; Deprecated
    case STAMP_BY_PM = 5;

    public static function getLabel(int $value): string
    {
        return match ($value) {
            self::MY_VISITOR->value => 'MyVisitor/Estamp',
            self::NOT_MY_VISITOR->value => 'Not My Visitor',
            self::CANCEL_BY_SG->value => 'Cancelled by SG',
            // self::BLACKLISTED->value => 'Blacklisted',
            self::STAMP_BY_PM->value => 'Stamped by PM',
            default => 'Unknown',
        };
    }
}
