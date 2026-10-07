<?php

namespace App\Enums\SalesManagement;

use Filament\Support\Contracts\HasLabel;

enum SellingStatusEnum: int implements HasLabel
{
    case QUOTATIONS = 1;
    case BOOKING = 2;
    case CONTRACT = 3;
    case LOAN_APPLICATION_STATUS = 4;
    case OWNERSHIP_TRANSFER = 5;
    case GET_PROMOTIONS = 6;
    case NOT_YET_SOLD = 7;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::QUOTATIONS => __('sale.selling_status_values.quotations'),
            self::BOOKING => __('sale.selling_status_values.booking'),
            self::CONTRACT => __('sale.selling_status_values.contract'),
            self::LOAN_APPLICATION_STATUS => __('sale.selling_status_values.loan_application'),
            self::OWNERSHIP_TRANSFER => __('sale.selling_status_values.ownership_transfer'),
            self::GET_PROMOTIONS => __('sale.selling_status_values.get_promotions'),
            self::NOT_YET_SOLD => __('sale.selling_status_values.not_yet_sold'),
            default => 'N/A',
        };
    }

    public function getColor(): ?string
    {
        $defaultColor = '#374151';
        $colorMap = [
            '#EF4444',
            '#3B82F6',
            '#F59E0B',
            '#10B981',
            '#9333EA',
            '#EC4899',
            '#60A5FA',
            '#374151',
        ];

        return match ($this) {
            self::QUOTATIONS => $colorMap[0],
            self::BOOKING => $colorMap[1],
            self::CONTRACT => $colorMap[2],
            self::LOAN_APPLICATION_STATUS => $colorMap[3],
            self::OWNERSHIP_TRANSFER => $colorMap[4],
            self::GET_PROMOTIONS => $colorMap[5],
            self::NOT_YET_SOLD => $colorMap[6],
            default => $defaultColor,
        };
    }
}
