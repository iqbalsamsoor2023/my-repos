<?php

namespace App\Support;

use Illuminate\Support\Arr;

class PetDashboardSupport
{
    public static function normalizedFilters(array $filters): array
    {
        return Arr::sortRecursive($filters);
    }

    public static function flattenFilters(array $filters): array
    {
        return collect(Arr::dot($filters))
            ->filter(static fn (mixed $value): bool => filled($value))
            ->values()
            ->all();
    }
}
