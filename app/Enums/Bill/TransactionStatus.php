<?php

namespace App\Enums\Bill;

enum TransactionStatus: int
{
    case PENDING = 1;
    case ACCEPTED = 2;
    case REJECTED = 3;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('billing.pending'),
            self::ACCEPTED => __('billing.accepted'),
            self::REJECTED => __('billing.rejected')
        };
    }
}
