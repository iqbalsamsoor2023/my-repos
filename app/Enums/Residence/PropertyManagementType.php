<?php

namespace App\Enums\Residence;

use App\Support\WidgetColorPalette;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PropertyManagementType: int implements HasColor, HasLabel
{
    case PERSONAL_PROPERTY_MANAGEMENT = 1;
    case COMPANY_PROPERTY_MANAGEMENT = 2;
    case DEVELOPER_PROPERTY_MANAGEMENT = 3;
    case ABANDONED = 4;
    case NO_INFO = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::PERSONAL_PROPERTY_MANAGEMENT => 'Personal Property Management',
            self::COMPANY_PROPERTY_MANAGEMENT => 'Company Property Management',
            self::DEVELOPER_PROPERTY_MANAGEMENT => 'Developer Property Management',
            self::ABANDONED => 'Abandoned',
            self::NO_INFO => 'No Info',
        };
    }

    public function getShortLabel(): string
    {
        return match ($this) {
            self::PERSONAL_PROPERTY_MANAGEMENT => 'PPM',
            self::COMPANY_PROPERTY_MANAGEMENT => 'CPM',
            self::DEVELOPER_PROPERTY_MANAGEMENT => 'DPM',
            self::ABANDONED => 'Abandoned',
            self::NO_INFO => 'No Info',
        };
    }

    public function getHex(): string
    {
        return match ($this) {
            self::PERSONAL_PROPERTY_MANAGEMENT => '#F97316',
            self::COMPANY_PROPERTY_MANAGEMENT => '#3B82F6',
            self::DEVELOPER_PROPERTY_MANAGEMENT => '#A855F7',
            self::ABANDONED => '#EF4444',
            self::NO_INFO => '#64748B',
        };
    }

    public function getColor(): string|array
    {
        return WidgetColorPalette::statColorFromHex($this->getHex());
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
