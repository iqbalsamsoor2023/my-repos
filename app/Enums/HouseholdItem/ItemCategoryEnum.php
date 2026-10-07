<?php

namespace App\Enums\HouseholdItem;

use Filament\Support\Contracts\HasLabel;

enum ItemCategoryEnum: int implements HasLabel
{
    case HOME_APPLIANCE = 1;
    case FURNITURE = 2;
    case SPACE = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::HOME_APPLIANCE => 'Home Appliances',
            self::FURNITURE => 'Furniture',
            self::SPACE => 'Living Space'
        };
    }
}
