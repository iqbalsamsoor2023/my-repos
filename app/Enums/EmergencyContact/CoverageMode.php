<?php

namespace App\Enums\EmergencyContact;

use Illuminate\Support\Str;

enum CoverageMode: int
{
    case NATIONWIDE = 1;
    case PROVINCE = 2;

    /**
     * Get all cases as an associative array for Filament Select.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => __('app.'.Str::lower($case->name)),
        ])->toArray();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::NATIONWIDE => __('app.nationwide'),
            self::PROVINCE => __('app.province'),
        };
    }
}
