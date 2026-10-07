<?php

namespace App\Enums\TenancyManagement;

use Filament\Support\Contracts\HasLabel;

enum TenancyManagementStatus: int implements HasLabel
{
    case FOR_RENT = 1;
    case RENTED_OUT = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FOR_RENT => __('resales-and-tenancies.for_rent'),
            self::RENTED_OUT => __('resales-and-tenancies.rented_out')
        };
    }
}
