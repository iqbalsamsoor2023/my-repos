<?php

namespace App\Enums\Residence;

use Illuminate\Support\Str;

enum EntryLaneType: int
{
    case SINGLE_LANE = 1;
    case DUAL_LANE = 2;
    case SAME_LANE = 3;

    /**
     * Get all cases as an associative array for Filament Select.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => Str::headline(str_replace('_', ' ', $case->name)),
        ])->toArray();
    }

    public function getLabel(): string
    {
        return Str::headline(str_replace('_', ' ', $this->name));
    }
}
