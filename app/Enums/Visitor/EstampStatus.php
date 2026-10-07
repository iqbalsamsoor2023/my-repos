<?php

namespace App\Enums\Visitor;

enum EstampStatus: string
{
    case PENDING = 'PENDING';
    case ESTAMP = 'ESTAMP';
    case NOT_MY_VISITOR = 'NOT_MY_VISITOR';
    case CANCEL_BY_SG = 'CANCEL_BY_SG';
    case STAMP_BY_PM = 'STAMP_BY_PM';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('visitor.estamp_status_pending'),
            self::ESTAMP => __('visitor.estamp_status_stamped'),
            self::NOT_MY_VISITOR => __('visitor.estamp_status_not_my_visitor'),
            self::CANCEL_BY_SG => __('visitor.estamp_status_cancel_by_sg'),
            self::STAMP_BY_PM => __('visitor.estamp_status_stamped_by_pm'),
            default => __('visitor.estamp_status_pending'),
        };
    }

    public function getValue(): int
    {
        return match ($this) {
            self::PENDING => 0,
            self::ESTAMP => 1,
            self::NOT_MY_VISITOR => 2,
            self::CANCEL_BY_SG => 3,
            self::STAMP_BY_PM => 4,
        };
    }
}
