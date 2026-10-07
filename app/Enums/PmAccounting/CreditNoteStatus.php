<?php

namespace App\Enums\PmAccounting;

use Filament\Support\Contracts\HasLabel;

enum CreditNoteStatus: int implements HasLabel
{
    case PENDING = 1;
    case CLAIMED = 2;
    case CANCELLED = 3;
    case PAID = 4;
    case OVERPAID = 5;

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
            self::CLAIMED => 'green',
            self::CANCELLED => 'red',
            self::PAID => 'blue',
            self::OVERPAID => 'purple',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CLAIMED => 'Claimed',
            self::CANCELLED => 'Cancelled',
            self::PAID => 'Paid',
            self::OVERPAID => 'Overpaid',
            default => null,
        };
    }
}
