<?php

namespace App\Services;

use App\Enums\Parcel\ParcelStatus;
use App\Models\User;
use App\Models\WidgetAggregate;
use App\Policies\ParcelPolicy;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ParcelWidgetDataService
{
    private const FILTERED_CACHE_TTL_SECONDS = 60;

    private const CACHE_VERSION_KEY = 'psv_widget_version';

    /** @var array<string, array> */
    private static array $requestCache = [];

    public static function flushRequestCache(): void
    {
        static::$requestCache = [];
    }

    public static function getScopedDataForUser($user, array $filters = []): array
    {
        if (! $user instanceof User) {
            return static::empty();
        }

        $normalizedFilters = static::normalizedFilters($filters);
        $cacheKey = 'pdata_'.$user->id.'_'.md5(json_encode($normalizedFilters));

        if (isset(static::$requestCache[$cacheKey])) {
            return static::$requestCache[$cacheKey];
        }

        if (ParcelPolicy::isGlobalAdmin($user)) {
            $result = static::getAllData($normalizedFilters);
        } elseif (ParcelPolicy::isPropertyManager($user)) {
            $residenceId = DB::table('residences')
                ->where('property_management_user_id', $user->id)
                ->value('id');

            $result = $residenceId
                ? static::getByResidenceId((int) $residenceId, $normalizedFilters)
                : static::empty();
        } elseif (ParcelPolicy::isOperationCenter($user)) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);

            $result = static::getByResidenceIds((array) $residenceIds, $normalizedFilters);
        } else {
            $result = static::empty();
        }

        return static::$requestCache[$cacheKey] = $result;
    }

    public static function getAllData(array $filters = []): array
    {
        $normalizedFilters = static::normalizedFilters($filters);
        $hasFilters = ! empty(static::flattenFilters($normalizedFilters));

        if (! $hasFilters) {
            $cached = WidgetAggregate::getCached('parcel_global_stats', 300, 'parcel');
            if ($cached) {
                return $cached;
            }

            $data = static::computeFromReadModel([]);
            WidgetAggregate::putCache('parcel_global_stats', $data, 'parcel');

            return $data;
        }

        $cacheKey = 'psv_widget_'.static::cacheVersion().'_'.md5(json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, fn () => static::computeFromReadModel($normalizedFilters));
    }

    public static function getByResidenceId(int $residenceId, array $filters = []): array
    {
        $normalizedFilters = static::normalizedFilters($filters);
        $cacheKey = 'psv_pm_'.static::cacheVersion().'_'.$residenceId.'_'.md5(json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($residenceId, $normalizedFilters) {
            $base = DB::table('parcel_stats_view')->where('residence_id', $residenceId);
            static::applyReadModelFilters($base, $normalizedFilters);

            return static::aggregateQuery($base);
        });
    }

    public static function getByResidenceIds(array $residenceIds, array $filters = []): array
    {
        if (empty($residenceIds)) {
            return static::empty();
        }

        sort($residenceIds);
        $normalizedFilters = static::normalizedFilters($filters);
        $cacheKey = 'psv_pmoc_'.static::cacheVersion().'_'.md5(implode(',', $residenceIds).'|'.json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($residenceIds, $normalizedFilters) {
            $base = DB::table('parcel_stats_view')->whereIn('residence_id', $residenceIds);
            static::applyReadModelFilters($base, $normalizedFilters);

            return static::aggregateQuery($base);
        });
    }

    public static function computeFromReadModel(array $filters): array
    {
        $normalizedFilters = static::normalizedFilters($filters);
        $base = DB::table('parcel_stats_view');
        static::applyReadModelFilters($base, $normalizedFilters);

        return static::aggregateQuery($base);
    }

    public static function getScopedCreationTrendForUser($user, array $filters = [], string $period = 'day'): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $normalizedFilters = static::normalizedFilters($filters);
        $cacheKey = 'ptrend_'.$user->id.'_'.md5(json_encode($normalizedFilters)).'_'.$period;

        if (isset(static::$requestCache[$cacheKey])) {
            return static::$requestCache[$cacheKey];
        }

        $base = DB::table('parcel_stats_view');

        if (ParcelPolicy::isGlobalAdmin($user)) {
            // no scope restriction
        } elseif (ParcelPolicy::isPropertyManager($user)) {
            $residenceId = DB::table('residences')
                ->where('property_management_user_id', $user->id)
                ->value('id');

            if (! $residenceId) {
                return static::$requestCache[$cacheKey] = [];
            }

            $base->where('residence_id', $residenceId);
        } elseif (ParcelPolicy::isOperationCenter($user)) {
            $residenceIds = (array) get_residence_by_property_management_operation_center($user->id);

            if (empty($residenceIds)) {
                return static::$requestCache[$cacheKey] = [];
            }

            $base->whereIn('residence_id', $residenceIds);
        } else {
            return static::$requestCache[$cacheKey] = [];
        }

        static::applyReadModelFilters($base, $normalizedFilters);

        return static::$requestCache[$cacheKey] = static::computeCreationTrend($base, $period);
    }

    public static function getScopedStatusTrendForUser($user, string $period = '1 month'): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $base = DB::table('parcel_stats_view');

        if (ParcelPolicy::isGlobalAdmin($user)) {
            // no scope restriction
        } elseif (ParcelPolicy::isPropertyManager($user)) {
            $residenceId = DB::table('residences')
                ->where('property_management_user_id', $user->id)
                ->value('id');

            if (! $residenceId) {
                return [];
            }

            $base->where('residence_id', $residenceId);
        } elseif (ParcelPolicy::isOperationCenter($user)) {
            $residenceIds = (array) get_residence_by_property_management_operation_center($user->id);

            if (empty($residenceIds)) {
                return [];
            }

            $base->whereIn('residence_id', $residenceIds);
        } else {
            return [];
        }

        $startDate = match ($period) {
            '1 week' => Carbon::now()->subWeek()->startOfDay(),
            '2 weeks' => Carbon::now()->subWeeks(2)->startOfDay(),
            default => Carbon::now()->subMonth()->startOfDay(),
        };

        $rows = (clone $base)
            ->whereNotNull('parcel_created_at')
            ->where('parcel_created_at', '>=', $startDate)
            ->selectRaw('status, DATE(parcel_created_at) as trend_date, COUNT(*) as aggregate')
            ->groupBy('status', 'trend_date')
            ->orderBy('trend_date')
            ->get();

        $byStatus = [];
        foreach ($rows as $row) {
            $byStatus[$row->status][$row->trend_date] = (int) $row->aggregate;
        }

        return $byStatus;
    }

    protected static function aggregateQuery($base): array
    {
        $today = today()->toDateString();

        $totals = (clone $base)
            ->selectRaw(
                'COUNT(*) as total_parcels,
                 SUM(CASE WHEN DATE(parcel_created_at) = ? THEN 1 ELSE 0 END) as today_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as picked_up_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as not_my_parcel_count',
                [
                    $today,
                    ParcelStatus::PICKED_UP->value,
                    ParcelStatus::PENDING_PICK_UP->value,
                    ParcelStatus::NOT_MY_PARCEL->value,
                ]
            )
            ->first();

        $courierCounts = (clone $base)
            ->whereNotNull('courier_name')
            ->selectRaw('courier_id, courier_name, COUNT(*) as count')
            ->groupBy('courier_id', 'courier_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['courier_id' => $r->courier_id, 'name' => $r->courier_name, 'count' => (int) $r->count])
            ->toArray();

        return [
            'total_parcels' => (int) ($totals->total_parcels ?? 0),
            'today_count' => (int) ($totals->today_count ?? 0),
            'picked_up_count' => (int) ($totals->picked_up_count ?? 0),
            'pending_count' => (int) ($totals->pending_count ?? 0),
            'not_my_parcel_count' => (int) ($totals->not_my_parcel_count ?? 0),
            'courier_counts' => $courierCounts,
            'creation_trend' => [],
        ];
    }

    public static function computeCreationTrend($base, string $period = 'day'): array
    {
        $startDate = match ($period) {
            'week' => Carbon::now()->subWeek()->startOfDay(),
            'month' => Carbon::now()->subMonths(6)->startOfMonth(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        $column = 'parcel_created_at';
        $bucketExpression = match ($period) {
            'month' => "DATE_FORMAT({$column}, '%Y-%m-01')",
            default => "DATE({$column})",
        };

        return (clone $base)
            ->whereNotNull($column)
            ->where($column, '>=', $startDate)
            ->selectRaw("{$bucketExpression} as trend_date, COUNT(*) as aggregate")
            ->groupBy('trend_date')
            ->orderBy('trend_date')
            ->pluck('aggregate', 'trend_date')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    public static function applyReadModelFilters($query, array $filters): void
    {
        $residenceId = $filters['residence_filters']['residence_id'] ?? null;
        if (! empty($residenceId)) {
            $query->where('residence_id', $residenceId);
        }

        $moobanTypes = $filters['residence_filters']['mooban_type'] ?? [];
        if (! empty($moobanTypes)) {
            $query->whereIn('mooban_type', (array) $moobanTypes);
        }

        $subTypes = $filters['residence_filters']['sub_type'] ?? [];
        if (! empty($subTypes)) {
            $query->whereIn('sub_type', (array) $subTypes);
        }

        $provinceSubdistrictIds = null;
        $subdistrictIds = $filters['province_filters']['subdistrict'] ?? [];
        if (! empty($subdistrictIds)) {
            $provinceSubdistrictIds = (array) $subdistrictIds;
        } elseif (! empty($filters['province_filters']['district'] ?? [])) {
            $subdistrictMap = ThailandLocationService::getSubdistrictsByDistricts((array) $filters['province_filters']['district']);
            $provinceSubdistrictIds = array_keys($subdistrictMap);
        } elseif (! empty($filters['province_filters']['province'] ?? [])) {
            $districtIds = array_keys(ThailandLocationService::getDistrictsByProvinces((array) $filters['province_filters']['province']));
            $subdistrictMap = ThailandLocationService::getSubdistrictsByDistricts($districtIds);
            $provinceSubdistrictIds = array_keys($subdistrictMap);
        }

        if (! empty($provinceSubdistrictIds)) {
            $query->whereIn('subdistrict_id', $provinceSubdistrictIds);
        }

        $mainRoad = $filters['province_filters']['main_road'] ?? null;
        if (! empty($mainRoad)) {
            $query->where('main_road', 'LIKE', '%'.$mainRoad.'%');
        }

        $activationStatusIds = $filters['status_filters']['residence_activation_status_id'] ?? [];
        if (! empty($activationStatusIds)) {
            $query->whereIn('residence_activation_status_id', (array) $activationStatusIds);
        }

        $createdFrom = $filters['date_filters']['created_from'] ?? null;
        if (! empty($createdFrom)) {
            $query->whereDate('parcel_created_at', '>=', $createdFrom);
        }

        $createdUntil = $filters['date_filters']['created_until'] ?? null;
        if (! empty($createdUntil)) {
            $query->whereDate('parcel_created_at', '<=', $createdUntil);
        }

        $parcelStatus = $filters['status']['value'] ?? null;
        if (isset($parcelStatus) && $parcelStatus !== '') {
            $query->where('status', $parcelStatus);
        }

        $courierId = $filters['courier_id']['value'] ?? null;
        if (! empty($courierId)) {
            $query->where('courier_id', $courierId);
        }
    }

    public static function bumpCacheVersion(): void
    {
        Cache::increment(self::CACHE_VERSION_KEY);
    }

    protected static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    protected static function normalizedFilters(array $filters): array
    {
        return Arr::sortRecursive($filters);
    }

    protected static function flattenFilters(array $filters): array
    {
        return collect(Arr::dot($filters))
            ->filter(static fn (mixed $value): bool => filled($value))
            ->values()
            ->all();
    }

    public static function empty(): array
    {
        return [
            'total_parcels' => 0,
            'today_count' => 0,
            'picked_up_count' => 0,
            'pending_count' => 0,
            'not_my_parcel_count' => 0,
            'courier_counts' => [],
            'creation_trend' => [],
        ];
    }
}
