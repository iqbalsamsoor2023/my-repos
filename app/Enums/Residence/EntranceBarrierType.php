<?php

namespace App\Enums\Residence;

use Illuminate\Support\Str;

enum EntranceBarrierType: int
{
    case GATE_FENCE = 1;
    case LPR = 2;
    case RFID = 3;
    case MANUAL_BARRIER = 4;
    case NO_BARRIER = 5;

    public function label(): string
    {
        return match ($this) {
            self::GATE_FENCE => 'Gate / Fence',
            self::LPR => 'LPR',
            self::RFID => 'RFID',
            self::MANUAL_BARRIER => 'Manual Barrier',
            self::NO_BARRIER => 'No Barrier',
        };
    }

    /**
     * Get all cases as an associative array for Filament Select.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => Str::headline(str_replace('_', ' ', $case->name)),
        ])->toArray();
    }
}
