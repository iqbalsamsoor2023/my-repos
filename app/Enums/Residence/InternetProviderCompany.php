<?php

namespace App\Enums\Residence;

use Illuminate\Support\Str;

enum InternetProviderCompany: int
{
    case NT_TELECOM = 1;
    case TRUE_ONLINE = 2;
    case AIS_FIBRE = 3;

    /**
     * Get provider name from enum case.
     */
    public function label(): string
    {
        return match ($this) {
            self::NT_TELECOM => 'NT Telecom',
            self::TRUE_ONLINE => 'True Online',
            self::AIS_FIBRE => 'AIS 3BB',
        };
    }

    /**
     * Get all cases as an associative array for Filament Select.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => Str::headline(str_replace('_', ' ', $case->name)),
        ])->toArray();
    }
}
