<?php

namespace App\Enums\Bill;

use Filament\Support\Contracts\HasLabel;

enum BillStatus: int implements HasLabel
{
    case UNPAID = 1;
    case PAID = 2;
    case PARTIALLY_PAID = 3;
    case PENDING = 4;
    case FAILED = 5;
    case CANCEL = 6;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID => __('billing.unpaid'),
            self::PAID => __('billing.paid'),
            self::PARTIALLY_PAID => __('billing.partially_paid'),
            self::PENDING => __('billing.pending'),
            self::FAILED => __('billing.failed'),
            self::CANCEL => __('billing.cancel'),
        };
    }
}
