<?php

namespace App\Enums\PmAccounting;

use Filament\Support\Contracts\HasLabel;

enum BillingStatus: int implements HasLabel
{
    case PENDING = 1;
    case ISSUED_INVOICE = 2;
    case PAID = 3;
    case PARTIALLY_PAID = 4;
    case OVERDUE = 5;
    case CANCELLED = 6;
    case VOID = 7;

    public static function asSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->getLabel()])
            ->toArray();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::ISSUED_INVOICE => 'aqua',
            self::PAID => 'green',
            self::PARTIALLY_PAID => 'aquamarine',
            self::OVERDUE => 'orange',
            self::CANCELLED => 'red',
            self::VOID => 'darkred',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ISSUED_INVOICE => 'Issued Invoice',
            self::PAID => 'Paid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::VOID => 'Void',
            default => null,
        };
    }
}
