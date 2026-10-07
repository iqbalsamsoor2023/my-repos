<?php

namespace App\Enums\PmAccounting;

use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: int implements HasLabel
{
    case PENDING = 1;
    case PAID = 2;
    case PARTIALLY_PAID = 3;
    case OVERDUE = 4;
    case CANCELLED = 5;
    case VOID = 6;

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
            self::PENDING => __('billing.pending'),
            self::PAID => __('billing.paid'),
            self::PARTIALLY_PAID => __('billing.partially_paid'),
            self::OVERDUE => __('billing.overdue'),
            self::CANCELLED => __('billing.cancelled'),
            self::VOID => __('billing.void'),
            default => null,
        };
    }
}
