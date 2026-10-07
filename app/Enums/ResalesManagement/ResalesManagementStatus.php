<?php

namespace App\Enums\ResalesManagement;

use Filament\Support\Contracts\HasLabel;

enum ResalesManagementStatus: int implements HasLabel
{
    case FOR_RESALE = 1;
    case SOLD_OUT = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FOR_RESALE => __('resales-and-tenancies.for_resale'),
            self::SOLD_OUT => __('resales-and-tenancies.sold_out')
        };
    }
}
