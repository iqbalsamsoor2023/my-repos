<?php

namespace App\Enums\Unit;

use Filament\Support\Contracts\HasLabel;

enum PaymentFrequency: string implements HasLabel
{
    case MONTHLY = 'M';
    case BI_MONTHLY = 'B';
    case QUARTERLY = 'Q';
    case HALF_YEARLY = 'H';
    case YEARLY = 'Y';

    public function getLabel(): string
    {
        return match ($this) {
            self::MONTHLY => __('unit.payment_frequency.monthly'),
            self::BI_MONTHLY => __('unit.payment_frequency.bi_monthly'),
            self::QUARTERLY => __('unit.payment_frequency.quarterly'),
            self::HALF_YEARLY => __('unit.payment_frequency.half_yearly'),
            self::YEARLY => __('unit.payment_frequency.yearly'),
        };
    }
}
