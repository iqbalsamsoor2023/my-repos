<?php

namespace App\Enums\Unit;

use Filament\Support\Contracts\HasLabel;

enum StatusType: int implements HasLabel
{
    case Occupied = 1;
    case Abandoned = 2;
    case Not_yet_sold = 3;
    case OccupiedTenant = 4;
    case NPA_NPL = 5;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Occupied => __('unit.occupied_by_owner'),
            self::Abandoned => __('unit.abandoned'),
            self::Not_yet_sold => __('unit.not_yet_sold'),
            self::OccupiedTenant => __('unit.occupied_by_tenant'),
            self::NPA_NPL => __('unit.npa_npl'),
            default => __('unit.unknown')
        };
    }

    /**
     * Get an array of status options for dropdowns.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($type) => [
            $type->value => $type->getLabel(),
        ])->toArray();
    }

    /**
     * Get the default value for the status.
     */
    public static function getDefaultValue(): int
    {
        return self::Occupied->value;
    }
}
