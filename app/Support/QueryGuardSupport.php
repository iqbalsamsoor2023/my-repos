<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class QueryGuardSupport
{
    public static function noRowsClause(): string
    {
        return '1 = 0';
    }

    /**
     * Apply a deterministic guard that forces the query to return zero rows.
     *
     * @template TBuilder of EloquentBuilder|QueryBuilder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public static function denyAll(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $query->whereRaw(static::noRowsClause());
    }

    /**
     * Apply `whereIn` for non-empty ids; otherwise force empty result set.
     *
     * @template TBuilder of EloquentBuilder|QueryBuilder
     *
     * @param  TBuilder  $query
     * @param  array<int|string|null>  $ids
     * @return TBuilder
     */
    public static function whereInOrDenyAll(
        EloquentBuilder|QueryBuilder $query,
        string $column,
        array $ids
    ): EloquentBuilder|QueryBuilder {
        $normalizedIds = static::normalizeNonEmptyValues($ids);

        if (empty($normalizedIds)) {
            return static::denyAll($query);
        }

        return $query->whereIn($column, $normalizedIds, 'and', false);
    }

    /**
     * Apply `whereHas(...whereIn...)` for non-empty ids; otherwise force empty result set.
     *
     * @param  array<int|string|null>  $ids
     */
    public static function whereHasInOrDenyAll(
        EloquentBuilder $query,
        string $relation,
        string $column,
        array $ids
    ): EloquentBuilder {
        $normalizedIds = static::normalizeNonEmptyValues($ids);

        if (empty($normalizedIds)) {
            return static::denyAll($query);
        }

        return $query->whereHas($relation, static function (EloquentBuilder $subQuery) use ($column, $normalizedIds) {
            $subQuery->whereIn($column, $normalizedIds, 'and', false);
        });
    }

    /**
     * @param  array<int|string|null>  $values
     * @return array<int|string>
     */
    private static function normalizeNonEmptyValues(array $values): array
    {
        return array_values(array_unique(array_filter(
            $values,
            static fn ($value): bool => $value !== null && $value !== ''
        ), SORT_REGULAR));
    }
}
