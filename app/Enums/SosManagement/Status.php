<?php

namespace App\Enums\SosManagement;

enum Status: int
{
    case PENDING = 1;
    case IN_PROGRESS = 2;
    case CANCELLED = 3;
    case COMPLETED = 4;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('app.pending'),
            self::IN_PROGRESS => __('app.in_progress'),
            self::CANCELLED => __('app.cancelled'),
            self::COMPLETED => __('app.completed')
        };
    }
}
