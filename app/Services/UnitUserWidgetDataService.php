<?php

namespace App\Services;

use App\Models\WidgetAggregate;
use App\Policies\UnitUserPolicy;
use App\Support\QueryGuardSupport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UnitUserWidgetDataService
{
    private const FILTERED_CACHE_TTL_SECONDS = 60;

    private const CACHE_VERSION_KEY = 'uusv_widget_version';

    public static function getScopedDataForUser($user, array $filters = []): array
    {
        if (UnitUserPolicy::isGlobalAdmin($user)) {
            return static::getAllData($filters);
        }

        $residenceIds = static::resolveResidenceIdsForUser($user);

        return static::getByResidenceIds($residenceIds, $filters);
    }

    public static function getScopedCreationTrendForUser($user, array $filters = [], string $period = 'day'): array
    {
        if (UnitUserPolicy::isGlobalAdmin($user)) {
            return static::getCreationTrend($filters, $period);
        }

        $residenceIds = static::resolveResidenceIdsForUser($user);

        if (empty($residenceIds)) {
            return [];
        }

        $base = DB::table('unit_user_stats_view')
            ->whereIn('residence_id', $residenceIds);

        static::applyReadModelFilters($base, $filters);

        return static::computeCreationTrendFromReadModel($base, $period);
    }

    public static function getAllData(array $filters = []): array
    {
        $normalizedFilters = static::normalizedFilters($filters);
        $hasFilters = ! empty(static::flattenFilters($normalizedFilters));

        if (! $hasFilters) {
            $cached = WidgetAggregate::getCached('unit_user_global_stats', 300, 'unit_user');
            if ($cached) {
                return $cached;
            }

            $data = static::computeFromReadModel([]);
            WidgetAggregate::putCache('unit_user_global_stats', $data, 'unit_user');

            return $data;
        }

        $cacheKey = 'uusv_widget_'.static::cacheVersion().'_'.md5(json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, fn () => static::computeFromReadModel($normalizedFilters));
    }

    public static function getByResidenceIds(array $residenceIds, array $filters = []): array
    {
        if (empty($residenceIds)) {
            return static::empty();
        }

        sort($residenceIds);
        $normalizedFilters = static::normalizedFilters($filters);

        $cacheKey = 'uusv_pm_'.static::cacheVersion().'_'.md5(implode(',', $residenceIds).'|'.json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($residenceIds, $normalizedFilters) {
            $base = DB::table('unit_user_stats_view')
                ->whereIn('residence_id', $residenceIds);

            static::applyReadModelFilters($base, $normalizedFilters);

            return static::aggregateQuery($base);
        });
    }

    public static function computeFromReadModel(array $filters): array
    {
        $base = DB::table('unit_user_stats_view');
        static::applyReadModelFilters($base, $filters);

        return static::aggregateQuery($base);
    }

    public static function getCreationTrend(array $filters = [], string $period = 'day'): array
    {
        $base = DB::table('unit_user_stats_view');
        static::applyReadModelFilters($base, $filters);

        return static::computeCreationTrendFromReadModel($base, $period);
    }

    public static function applyReadModelFilters($query, array $filters): void
    {
        static::applySoftDeleteFilter($query, data_get($filters, 'trashed.value'));

        if (! empty($filters['mooban']['mooban_type'] ?? null)) {
            $query->whereIn('mooban_type', (array) $filters['mooban']['mooban_type']);
        }

        if (! empty($filters['mooban']['sub_type'] ?? null)) {
            $query->whereIn('sub_type', (array) $filters['mooban']['sub_type']);
        }

        if (! empty($filters['mooban']['residence_id'] ?? null)) {
            $query->where('residence_id', $filters['mooban']['residence_id']);
        }

        if (! empty($filters['resident_filters']['unit'] ?? null)) {
            $query->where('unit_number', 'LIKE', '%'.$filters['resident_filters']['unit'].'%');
        }

        if (! empty($filters['resident_filters']['email'] ?? null)) {
            $query->where('email', 'LIKE', '%'.$filters['resident_filters']['email'].'%');
        }

        $provinceIds = array_map('intval', array_filter((array) ($filters['province_filters']['province'] ?? [])));
        $districtIds = array_map('intval', array_filter((array) ($filters['province_filters']['district'] ?? [])));
        $subdistrictIds = array_map('intval', array_filter((array) ($filters['province_filters']['subdistrict'] ?? [])));
        $residenceIds = ThailandLocationService::getResidenceIdsByLocation($provinceIds, $districtIds, $subdistrictIds);

        if (! empty($provinceIds) || ! empty($districtIds) || ! empty($subdistrictIds)) {
            QueryGuardSupport::whereInOrDenyAll($query, 'residence_id', $residenceIds);
        }

        if (! empty($filters['province_filters']['main_road'] ?? null)) {
            $query->where('main_road', 'LIKE', '%'.$filters['province_filters']['main_road'].'%');
        }

        if (! empty($filters['status_filters']['residence_activation_status_id'] ?? null)) {
            $query->whereIn(
                'residence_activation_status_id',
                (array) $filters['status_filters']['residence_activation_status_id']
            );
        }

        if (! empty($filters['date_filters']['created_from'] ?? null)) {
            $query->whereDate('unit_user_created_at', '>=', $filters['date_filters']['created_from']);
        }

        if (! empty($filters['date_filters']['created_until'] ?? null)) {
            $query->whereDate('unit_user_created_at', '<=', $filters['date_filters']['created_until']);
        }

        if (! empty($filters['date_filters']['updated_from'] ?? null)) {
            $query->whereDate('unit_user_updated_at', '>=', $filters['date_filters']['updated_from']);
        }

        if (! empty($filters['date_filters']['updated_until'] ?? null)) {
            $query->whereDate('unit_user_updated_at', '<=', $filters['date_filters']['updated_until']);
        }

        if (! empty($filters['user_demographics']['country_id'] ?? null)) {
            $query->whereIn('country_id', (array) $filters['user_demographics']['country_id']);
        }

        if (! empty($filters['user_demographics']['gender'] ?? null)) {
            $selected = (array) $filters['user_demographics']['gender'];
            $includeNotSet = in_array('not_set', $selected, true);
            $selected = array_values(array_filter($selected, fn ($v) => $v !== 'not_set'));

            if ($includeNotSet && ! empty($selected)) {
                $query->where(function ($q) use ($selected) {
                    $q->whereNull('gender')->orWhereIn('gender', $selected);
                });
            } elseif ($includeNotSet) {
                $query->whereNull('gender');
            } else {
                $query->whereIn('gender', $selected);
            }
        }

        if (! empty($filters['user_demographics']['age_group'] ?? null)) {
            $query->whereIn('widget_age_group', (array) $filters['user_demographics']['age_group']);
        }
    }

    protected static function applySoftDeleteFilter($query, mixed $trashedFilterValue): void
    {
        $normalizedValue = static::normalizeTrashedFilterValue($trashedFilterValue);

        if ($normalizedValue === true) {
            return;
        }

        if ($normalizedValue === false) {
            $query->whereNotNull('unit_user_deleted_at');

            return;
        }

        $query->whereNull('unit_user_deleted_at');
    }

    public static function bumpCacheVersion(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, now()->timestamp);
    }

    protected static function aggregateQuery($base): array
    {
        $stats = (clone $base)->selectRaw('COUNT(*) as total_residents')->first();

        $genderCounts = (clone $base)
            ->selectRaw("COALESCE(CAST(gender AS CHAR), 'null') as gender_key, COUNT(*) as total")
            ->groupBy('gender_key')
            ->pluck('total', 'gender_key')
            ->mapWithKeys(fn ($value, $key) => [is_numeric($key) ? (int) $key : $key => (int) $value])
            ->toArray();

        $ownershipCounts = (clone $base)
            ->selectRaw('is_owner, COUNT(*) as total')
            ->groupBy('is_owner')
            ->pluck('total', 'is_owner')
            ->map(fn ($value) => (int) $value)
            ->toArray();

        $ageGroupCounts = (clone $base)
            ->selectRaw("COALESCE(widget_age_group, 'Not Set') as age_group, COUNT(*) as total")
            ->groupBy('age_group')
            ->pluck('total', 'age_group')
            ->map(fn ($value) => (int) $value)
            ->toArray();

        $countryCounts = (clone $base)
            ->selectRaw("COALESCE(NULLIF(country_name, ''), 'Not Yet Set') as country_name, COUNT(*) as total")
            ->groupBy('country_name')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'country_name')
            ->map(fn ($value) => (int) $value)
            ->toArray();

        $activationStatusCounts = (clone $base)
            ->whereIn('residence_activation_status_id', [2, 5])
            ->selectRaw('residence_activation_status_id, COUNT(*) as total')
            ->groupBy('residence_activation_status_id')
            ->pluck('total', 'residence_activation_status_id')
            ->map(fn ($value) => (int) $value)
            ->toArray();

        return [
            'total_residents' => (int) ($stats?->total_residents ?? 0),
            'gender_counts' => $genderCounts,
            'ownership_counts' => [
                'owner' => (int) ($ownershipCounts[1] ?? 0),
                'tenant' => (int) ($ownershipCounts[0] ?? 0),
            ],
            'age_group_counts' => $ageGroupCounts,
            'country_counts' => $countryCounts,
            'activation_status_counts' => $activationStatusCounts,
            'creation_trend' => static::computeCreationTrendFromReadModel($base),
        ];
    }

    public static function computeCreationTrendFromReadModel($base, string $period = 'day'): array
    {
        $startDate = match ($period) {
            'week' => Carbon::now()->subWeek()->startOfDay(),
            'month' => Carbon::now()->subMonths(6)->startOfMonth(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        $bucketExpression = match ($period) {
            'month' => "DATE_FORMAT(unit_user_created_at, '%Y-%m-01')",
            default => 'DATE(unit_user_created_at)',
        };

        return (clone $base)
            ->whereNotNull('unit_user_created_at')
            ->where('unit_user_created_at', '>=', $startDate)
            ->selectRaw("{$bucketExpression} as trend_date, COUNT(*) as aggregate")
            ->groupBy('trend_date')
            ->orderBy('trend_date')
            ->pluck('aggregate', 'trend_date')
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }

    public static function syncWidgetAggregates(): void
    {
        $data = static::computeFromReadModel([]);
        WidgetAggregate::putCache('unit_user_global_stats', $data, 'unit_user');
        static::bumpCacheVersion();
    }

    public static function empty(): array
    {
        return [
            'total_residents' => 0,
            'gender_counts' => [],
            'ownership_counts' => ['owner' => 0, 'tenant' => 0],
            'age_group_counts' => [],
            'country_counts' => [],
            'activation_status_counts' => [],
            'creation_trend' => [],
        ];
    }

    protected static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    protected static function resolveResidenceIdsForUser($user): array
    {
        if (UnitUserPolicy::isPropertyManager($user)) {
            $residence = get_residence_by_property_management($user->id);

            return $residence ? [$residence->id] : [];
        }

        if (UnitUserPolicy::isOperationCenter($user)) {
            return array_values(array_filter((array) get_residence_by_property_management_operation_center($user->id)));
        }

        return [];
    }

    protected static function flattenFilters(array $filters): array
    {
        $flat = [];

        array_walk_recursive($filters, function ($value) use (&$flat) {
            if ($value === null) {
                return;
            }

            if (is_string($value) && trim($value) === '') {
                return;
            }

            $flat[] = $value;
        });

        return $flat;
    }

    protected static function normalizedFilters(array $filters): array
    {
        return static::sortArrayRecursively($filters);
    }

    protected static function sortArrayRecursively(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = static::sortArrayRecursively($item);
            }
        }

        if (array_is_list($value)) {
            $allScalar = collect($value)->every(fn ($item) => is_scalar($item) || $item === null);

            if ($allScalar) {
                sort($value);

                return $value;
            }

            usort($value, fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));

            return $value;
        }

        ksort($value);

        return $value;
    }

    protected static function normalizeTrashedFilterValue(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (in_array($value, [1, '1', 'true'], true)) {
            return true;
        }

        if (in_array($value, [0, '0', 'false'], true)) {
            return false;
        }

        return null;
    }
}
