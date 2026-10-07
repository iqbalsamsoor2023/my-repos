<?php

namespace App\Enums\Residence;

use App\Support\WidgetColorPalette;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ActivationStatusType: int implements HasColor, HasLabel
{
    case INACTIVE_DEMO = 1;
    case INACTIVE_CANCELLED = 2;
    case ACTIVE_GT_ONLY = 3;
    case ACTIVE_GP_ONLY = 4;
    case ACTIVE_ALL = 5;
    case ACTIVE_DEMO = 6;
    case ACTIVE_MYMOOBAN_LITE = 7;

    /** Mirrors `residence_activation_statuses.status` verbatim. */
    public function getLabel(): string
    {
        return match ($this) {
            self::INACTIVE_DEMO => 'Inactive - Demo',
            self::INACTIVE_CANCELLED => 'Inactive - Cancelled Service',
            self::ACTIVE_GT_ONLY => 'Active - GT Only',
            self::ACTIVE_GP_ONLY => 'Active - GP Only',
            self::ACTIVE_ALL => 'Active - All Action',
            self::ACTIVE_DEMO => 'Active - For Demo Only',
            self::ACTIVE_MYMOOBAN_LITE => 'Active - MyMooBan Lite',
        };
    }

    public function getHex(): string
    {
        return match ($this) {
            self::INACTIVE_DEMO => '#64748B',
            self::INACTIVE_CANCELLED => '#EF4444',
            self::ACTIVE_GT_ONLY => '#EC4899',
            self::ACTIVE_GP_ONLY => '#3B82F6',
            self::ACTIVE_ALL => '#10B981',
            self::ACTIVE_DEMO => '#F97316',
            self::ACTIVE_MYMOOBAN_LITE => '#8B5CF6',
        };
    }

    public function getColor(): string|array
    {
        return WidgetColorPalette::statColorFromHex($this->getHex());
    }
}
