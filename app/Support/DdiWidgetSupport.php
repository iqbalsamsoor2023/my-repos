<?php

namespace App\Support;

class DdiWidgetSupport
{
    /**
     * Normalize a filter ID payload into a deterministic sorted integer list.
     */
    public static function normalizeIds(mixed $values): array
    {
        if (! is_array($values)) {
            $values = $values !== null && $values !== '' ? [$values] : [];
        }

        $normalized = array_values(array_unique(array_map(
            'intval',
            array_filter($values, static fn ($value) => $value !== null && $value !== '')
        )));

        sort($normalized);

        return $normalized;
    }

    /**
     * Build a deterministic cache key for DDI widgets.
     */
    public static function cacheKey(string $slug, array $filters, int $version = 1): string
    {
        $normalizedFilters = self::normalizeForHash($filters);
        $encoded = json_encode($normalizedFilters);

        if ($encoded === false) {
            $encoded = serialize($normalizedFilters);
        }

        return sprintf('ddi_%s_v%d_%s', $slug, $version, md5($encoded));
    }

    private static function normalizeForHash(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = array_map(
            static fn (mixed $item): mixed => self::normalizeForHash($item),
            $value
        );

        if (array_is_list($normalized)) {
            if (self::listHasOnlyScalars($normalized)) {
                sort($normalized);

                return $normalized;
            }

            usort(
                $normalized,
                static fn (mixed $a, mixed $b): int => strcmp((string) json_encode($a), (string) json_encode($b))
            );

            return $normalized;
        }

        ksort($normalized);

        return $normalized;
    }

    private static function listHasOnlyScalars(array $values): bool
    {
        foreach ($values as $value) {
            if (! is_scalar($value) && $value !== null) {
                return false;
            }
        }

        return true;
    }
}
