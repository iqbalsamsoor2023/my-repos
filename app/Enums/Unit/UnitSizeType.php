<?php

namespace App\Enums\Unit;

use Filament\Support\Contracts\HasLabel;

enum UnitSizeType: int implements HasLabel
{
    case SQUARE_METER = 1;
    case SQUARE_WA = 2;
    case CHARTERED = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SQUARE_METER => __('unit.charge_unit.sqm'),
            self::SQUARE_WA => __('unit.charge_unit.sqw'),
            self::CHARTERED => __('unit.charge_unit.charter'),
            default => null,
        };
    }
}
