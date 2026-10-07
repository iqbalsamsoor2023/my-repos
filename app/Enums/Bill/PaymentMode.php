<?php

namespace App\Enums\Bill;

enum PaymentMode: int
{
    case CASH = 1;
    case ONLINE_TT = 2;
    case QR_CODE = 3;
    case CASH_DEPOSIT = 4;
    case MASTER_CARD = 5;
    case VISA_CARD = 6;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CASH => __('billing.cash'),
            self::ONLINE_TT => 'Online TT',
            self::QR_CODE => 'QR Code',
            self::CASH_DEPOSIT => __('billing.cash_deposit'),
            self::MASTER_CARD => 'Mastercard',
            self::VISA_CARD => 'Visa',
        };
    }
}
