<?php

namespace App\Enums\SalesAndTenancies;

use Filament\Support\Contracts\HasLabel;

enum BankLoanStatusEnum: int implements HasLabel
{
    case FREE = 1;
    case UNDER = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FREE => __('resales-and-tenancies.free_from_bank_loan'),
            self::UNDER => __('resales-and-tenancies.under_bank_loan')
        };
    }
}
