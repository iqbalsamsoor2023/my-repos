<?php

namespace App\Services;

use App\Enums\Unit\HouseType;
use App\Models\User;
use App\Models\WidgetAggregate;
use App\Policies\UnitPolicy;
use App\Support\QueryGuardSupport;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UnitWidgetDataService
{
    private const FILTERED_CACHE_TTL_SECONDS = 60;

    private const CACHE_VERSION_KEY = 'usv_widget_version';

    public static function getDashboardDataForUser(?User $user, array $filters = []): ?array
    {
        if (! $user) {
            return null;
        }

        if (UnitPolicy::isPropertyManager($user)) {
            $residence = get_residence_by_property_management($user->id);

            if (! $residence) {
                return null;
            }

            return static::getByResidenceIds([$residence->id], $filters);
        }

        if (UnitPolicy::isOperationCenter($user)) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);

            return static::getByResidenceIds($residenceIds, $filters);
        }

        return null;
    }

    public static function getAllData(array $filters = []): array
    {
        $normalizedFilters = static::normalizedFilters($filters);
        $hasFilters = ! empty(static::flattenFilters($normalizedFilters));

        if (! $hasFilters) {
            $cached = WidgetAggregate::getCached('unit_global_stats', 300, 'unit');
            if ($cached) {
                return $cached;
            }

            $data = static::computeFromReadModel([]);
            WidgetAggregate::putCache('unit_global_stats', $data, 'unit');

            return $data;
        }

        $cacheKey = 'usv_widget_'.static::cacheVersion().'_'.md5(json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, fn () => static::computeFromReadModel($normalizedFilters));
    }

    public static function getByResidenceIds(array $residenceIds, array $filters = []): array
    {
        if (empty($residenceIds)) {
            return static::empty();
        }

        sort($residenceIds);
        $normalizedFilters = static::normalizedFilters($filters);
        $cacheKey = 'usv_pm_'.static::cacheVersion().'_'.md5(implode(',', $residenceIds).'|'.json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($residenceIds, $normalizedFilters) {
            $base = DB::table('units_stats_view')->whereIn('residence_id', $residenceIds);

            static::applyReadModelFilters($base, $normalizedFilters);

            $result = static::aggregateQuery($base);
            $result['creation_trend'] = static::computeCreationTrendFromReadModel($base);

            return $result;
        });
    }

    public static function getBySubdistrictIds(?array $subdistrictIds): array
    {
        if ($subdistrictIds !== null) {
            sort($subdistrictIds);
        }

        $cacheKey = 'usv_subdistricts_'.static::cacheVersion().'_'.($subdistrictIds === null ? 'all' : md5(implode(',', $subdistrictIds)));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($subdistrictIds) {
            $base = DB::table('units_stats_view');

            if ($subdistrictIds !== null) {
                if (empty($subdistrictIds)) {
                    return static::empty();
                }
                $base->whereIn('subdistrict_id', $subdistrictIds);
            }

            return static::aggregateQuery($base);
        });
    }

    public static function computeFromReadModel(array $filters): array
    {
        $base = DB::table('units_stats_view');
        static::applyReadModelFilters($base, static::normalizedFilters($filters));

        $result = static::aggregateQuery($base);
        $result['creation_trend'] = static::computeCreationTrendFromReadModel($base);

        return $result;
    }

    protected static function aggregateQuery($base): array
    {
        // One grouped scan instead of 4 separate full scans over the same base query.
        $rows = (clone $base)
            ->selectRaw('house_type, sub_type, status, is_signed_up, COUNT(*) as cnt')
            ->groupBy('house_type', 'sub_type', 'status', 'is_signed_up')
            ->get();

        $totalUnits = 0;
        $signedUpCount = 0;
        $ownerCount = 0;
        $tenantCount = 0;
        $subTypeCounts = [];
        $statusCounts = [];
        $houseTypeSubType = [];
        $validHouseTypeCount = 0;

        foreach ($rows as $row) {
            $cnt = (int) $row->cnt;
            $totalUnits += $cnt;

            if ((int) $row->is_signed_up === 1) {
                $signedUpCount += $cnt;
            }

            $status = $row->status === null ? null : (int) $row->status;

            if ($status === 1) {
                $ownerCount += $cnt;
            } elseif ($status === 4) {
                $tenantCount += $cnt;
            }

            $subType = $row->sub_type === null ? null : (int) $row->sub_type;
            $subTypeKey = $subType ?? 'null';
            $subTypeCounts[$subTypeKey] = ($subTypeCounts[$subTypeKey] ?? 0) + $cnt;

            $statusKey = $status ?? 'null';
            $statusCounts[$statusKey] = ($statusCounts[$statusKey] ?? 0) + $cnt;

            if ($row->house_type !== null && $subType !== null) {
                $houseType = HouseType::tryFrom((int) $row->house_type);

                if ($houseType && $houseType->subType()->value === $subType) {
                    $houseTypeSubType[$houseType->value][$subType] = ($houseTypeSubType[$houseType->value][$subType] ?? 0) + $cnt;
                    $validHouseTypeCount += $cnt;
                }
            }
        }

        return [
            'total_units' => $totalUnits,
            'sub_type_counts' => $subTypeCounts,
            'house_type_sub_type' => $houseTypeSubType,
            'house_type_null_count' => max(0, $totalUnits - $validHouseTypeCount),
            'status_counts' => $statusCounts,
            'signed_up_count' => $signedUpCount,
            'owner_count' => $ownerCount,
            'tenant_count' => $tenantCount,
            'creation_trend' => [],
        ];
    }

    public static function computeCreationTrendFromReadModel($base, string $period = 'day'): array
    {
        $startDate = match ($period) {
            'week' => Carbon::now()->subWeek()->startOfDay(),
            'month' => Carbon::now()->subMonths(6)->startOfMonth(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        $column = 'unit_created_at';
        $bucketExpression = match ($period) {
            'month' => "DATE_FORMAT($column, '%Y-%m-01')",
            default => "DATE($column)",
        };

        return (clone $base)
            ->whereNotNull($column)
            ->where($column, '>=', $startDate)
            ->selectRaw("$bucketExpression as trend_date, COUNT(*) as aggregate")
            ->groupBy('trend_date')
            ->orderBy('trend_date')
            ->pluck('aggregate', 'trend_date')
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }

    public static function applyReadModelFilters($query, array $filters): void
    {
        if (! empty($filters['mooban']['mooban_type'] ?? null)) {
            $query->whereIn('mooban_type', (array) $filters['mooban']['mooban_type']);
        }

        if (! empty($filters['mooban']['sub_type'] ?? null)) {
            $query->whereIn('sub_type', (array) $filters['mooban']['sub_type']);
        }

        if (! empty($filters['mooban']['residence_id'] ?? null)) {
            $query->where('residence_id', $filters['mooban']['residence_id']);
        }

        if (! empty($filters['status_filters']['residence_activation_status_id'] ?? null)) {
            $query->whereIn(
                'residence_activation_status_id',
                (array) $filters['status_filters']['residence_activation_status_id']
            );
        }

        $statuses = array_merge(
            (array) ($filters['unit_search']['status'] ?? []),
            (array) ($filters['status_filters']['status'] ?? [])
        );

        if (! empty($statuses)) {
            $query->whereIn('status', array_values(array_unique($statuses)));
        }

        $unitNumbers = array_filter([
            $filters['unit_search']['unit_number'] ?? null,
            $filters['mooban']['unit_number'] ?? null,
        ]);

        foreach ($unitNumbers as $unitNumber) {
            $query->where('unit_number', 'LIKE', '%'.$unitNumber.'%');
        }

        $signUp = $filters['unit_search']['sign_up'] ?? $filters['status_filters']['sign_up'] ?? null;

        if ($signUp === 'signed_up') {
            $query->where('is_signed_up', true);
        } elseif ($signUp === 'not_signed_up') {
            $query->where('is_signed_up', false);
        }

        $ownership = $filters['unit_search']['ownership_status'] ?? $filters['status_filters']['ownership_status'] ?? null;

        if ($ownership === 'owner') {
            $query->where('is_registered_owner', true);
        } elseif ($ownership === 'tenant') {
            $query->where('is_registered_tenant', true);
        }

        $provinceIds = array_map('intval', array_filter((array) ($filters['province_filters']['province'] ?? [])));
        $districtIds = array_map('intval', array_filter((array) ($filters['province_filters']['district'] ?? [])));
        $subdistrictIds = array_map('intval', array_filter((array) ($filters['province_filters']['subdistrict'] ?? [])));

        if (! empty($provinceIds)) {
            $query->whereIn('province_id', $provinceIds);
        }

        if (! empty($districtIds)) {
            $query->whereIn('district_id', $districtIds);
        }

        if (! empty($subdistrictIds)) {
            $query->whereIn('subdistrict_id', $subdistrictIds);
        }

        if (! empty($filters['province_filters']['main_road'] ?? null)) {
            $mainRoadIds = DB::table('residences')
                ->where('main_road', 'LIKE', '%'.$filters['province_filters']['main_road'].'%')
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();
            QueryGuardSupport::whereInOrDenyAll($query, 'residence_id', $mainRoadIds);
        }

        if (! empty($filters['date_filters']['created_from'] ?? null)) {
            $query->whereDate('unit_created_at', '>=', $filters['date_filters']['created_from']);
        }

        if (! empty($filters['date_filters']['created_until'] ?? null)) {
            $query->whereDate('unit_created_at', '<=', $filters['date_filters']['created_until']);
        }

        if (! empty($filters['date_filters']['updated_from'] ?? null)) {
            $query->whereDate('unit_updated_at', '>=', $filters['date_filters']['updated_from']);
        }

        if (! empty($filters['date_filters']['updated_until'] ?? null)) {
            $query->whereDate('unit_updated_at', '<=', $filters['date_filters']['updated_until']);
        }
    }

    public static function empty(): array
    {
        return [
            'total_units' => 0,
            'sub_type_counts' => [],
            'house_type_sub_type' => [],
            'house_type_null_count' => 0,
            'status_counts' => [],
            'signed_up_count' => 0,
            'owner_count' => 0,
            'tenant_count' => 0,
            'creation_trend' => [],
        ];
    }

    public static function getCreationTrend(array $filters = [], string $period = 'day'): array
    {
        $base = DB::table('units_stats_view');
        static::applyReadModelFilters($base, static::normalizedFilters($filters));

        return static::computeCreationTrendFromReadModel($base, $period);
    }

    public static function bumpCacheVersion(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, now()->timestamp);
    }

    protected static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    protected static function flattenFilters(array $filters): array
    {
        return collect(Arr::dot($filters))
            ->filter(fn ($value): bool => filled($value))
            ->values()
            ->all();
    }

    protected static function normalizedFilters(array $filters): array
    {
        return Arr::sortRecursive($filters);
    }
}
