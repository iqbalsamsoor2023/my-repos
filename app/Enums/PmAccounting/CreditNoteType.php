<?php

namespace App\Enums\PmAccounting;

use Filament\Support\Contracts\HasLabel;

enum CreditNoteType: int implements HasLabel
{
    case PARTIAL = 1;
    case FULL = 2;

    public static function asSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->getLabel()])
            ->toArray();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PARTIAL => 'yellow',
            self::FULL => 'green',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PARTIAL => 'Partial',
            self::FULL => 'Full',
            default => null,
        };
    }
}
