<?php

namespace App\Enums\DigitalTool;

use Filament\Support\Colors\Color;

enum DtaDigitalToolStatusEnum: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('status.active'),
            self::SUSPENDED => __('status.suspended'),
            self::EXPIRED => __('status.expired'),
            self::CANCELLED => __('status.cancelled'),
        };
    }

    public function color()
    {
        return match ($this) {
            self::ACTIVE => Color::Emerald,
            self::SUSPENDED => Color::Amber,
            self::EXPIRED => Color::Red,
            self::CANCELLED => Color::Zinc,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
