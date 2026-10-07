<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class VehicleAgeCategorySupport
{
    /**
     * @var array<string, string>
     */
    private const FILTER_OPTIONS = [
        '1-5' => '1-5 years',
        '6-10' => '6-10 years',
        '11-15' => '11-15 years',
        '16-20' => '16-20 years',
        '21-25' => '21-25 years',
        '26-30' => '26-30 years',
        '31-35' => '31-35 years',
        '36-40' => '36-40 years',
        '41-45' => '41-45 years',
        '46-50' => '46-50 years',
        '50+' => '50+ years',
        'not_set' => 'Not Yet Set',
    ];

    /**
     * @var array<string, array<int, int>|null>
     */
    private const AGE_BUCKET_RANGES = [
        '1-5 years' => [1, 5],
        '6-10 years' => [6, 10],
        '11-15 years' => [11, 15],
        '16-20 years' => [16, 20],
        '21-25 years' => [21, 25],
        '26-30 years' => [26, 30],
        '31-35 years' => [31, 35],
        '36-40 years' => [36, 40],
        '41-45 years' => [41, 45],
        '46-50 years' => [46, 50],
        '50+ years' => [51, PHP_INT_MAX],
        'Not Yet Set' => null,
    ];

    public static function filterOptions(): array
    {
        return self::FILTER_OPTIONS;
    }

    public static function emptyBuckets(): array
    {
        return array_fill_keys(array_keys(self::AGE_BUCKET_RANGES), 0);
    }

    public static function applyCategoryFilters(
        EloquentBuilder|QueryBuilder $query,
        array $categories,
        string $modelYearColumn = 'model_year'
    ): void {
        if (empty($categories)) {
            return;
        }

        $normalizedCategories = array_values(array_filter(
            array_map(static fn (mixed $category): string => (string) $category, $categories),
            static fn (string $category): bool => $category !== ''
        ));

        if (empty($normalizedCategories)) {
            return;
        }

        $query->where(function ($builder) use ($normalizedCategories, $modelYearColumn): void {
            foreach ($normalizedCategories as $category) {
                $builder->orWhere(function ($subQuery) use ($category, $modelYearColumn): void {
                    static::applySingleCategoryFilter($subQuery, $category, $modelYearColumn);
                });
            }
        });
    }

    public static function bucketModelYearsByAge(array $modelYears, ?int $currentYear = null): array
    {
        $currentYear ??= now()->year;
        $counts = self::emptyBuckets();

        foreach ($modelYears as $year => $total) {
            if ($year === 'null' || $year === null) {
                $counts['Not Yet Set'] += (int) $total;

                continue;
            }

            $age = $currentYear - (int) $year;

            foreach (self::AGE_BUCKET_RANGES as $label => $range) {
                if ($range === null) {
                    continue;
                }

                [$min, $max] = $range;

                if ($age >= $min && $age <= $max) {
                    $counts[$label] += (int) $total;

                    break;
                }
            }
        }

        return $counts;
    }

    private static function applySingleCategoryFilter(
        EloquentBuilder|QueryBuilder $query,
        string $category,
        string $modelYearColumn
    ): void {
        if ($category === 'not_set') {
            $query->whereNull($modelYearColumn);

            return;
        }

        if ($category === '50+') {
            $query->where($modelYearColumn, '<=', now()->subYears(50)->year);

            return;
        }

        if (! str_contains($category, '-')) {
            return;
        }

        [$min, $max] = explode('-', $category);
        $minimumYears = (int) $min;
        $maximumYears = (int) $max;

        $query->whereBetween($modelYearColumn, [
            now()->subYears($maximumYears)->year,
            now()->subYears($minimumYears)->year,
        ]);
    }
}
