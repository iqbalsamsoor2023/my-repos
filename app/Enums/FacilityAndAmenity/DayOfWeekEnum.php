<?php

namespace App\Enums\FacilityAndAmenity;

use Carbon\Carbon;

enum DayOfWeekEnum: int
{
    case MONDAY = Carbon::MONDAY;
    case TUESDAY = Carbon::TUESDAY;
    case WEDNESDAY = Carbon::WEDNESDAY;
    case THURSDAY = Carbon::THURSDAY;
    case FRIDAY = Carbon::FRIDAY;
    case SATURDAY = Carbon::SATURDAY;
    case SUNDAY = Carbon::SUNDAY;

    public function label(): string
    {
        return match ($this) {
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
            self::SUNDAY => 'Sunday',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public static function fromLabel(string $label): ?self
    {
        return collect(self::cases())
            ->first(fn (self $case) => strtolower($case->label()) === strtolower($label)) ?: null;
    }
}
