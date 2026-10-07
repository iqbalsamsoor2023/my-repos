<?php

namespace App\Enums\Maintenance;

enum MaintenanceStatus: int
{
    case PENDING = 0;
    case IN_PROGRESS = 1;
    case COMPLETE = 2;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('maintenance.pending'),
            self::IN_PROGRESS => __('maintenance.in_progress'),
            self::COMPLETE => __('maintenance.completed'),
        };
    }
}
