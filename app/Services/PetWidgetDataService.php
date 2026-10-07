<?php

namespace App\Services;

use App\Enums\Pet\PetType;
use App\Models\Pet;
use App\Models\User;
use App\Models\WidgetAggregate;
use App\Policies\PetPolicy;
use App\Support\PetDashboardSupport;
use App\Support\QueryGuardSupport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PetWidgetDataService
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

        $normalizedFilters = PetDashboardSupport::normalizedFilters($filters);
        $cacheKey = 'data_'.$user->id.'_'.md5(json_encode($normalizedFilters));

        if (isset(static::$requestCache[$cacheKey])) {
            return static::$requestCache[$cacheKey];
        }

        if (PetPolicy::hasAdminDashboardAccess($user)) {
            $result = static::getAllData($normalizedFilters);
        } elseif (PetPolicy::isPropertyManager($user)) {
            $residenceId = static::propertyManagementResidenceId($user);
            $result = $residenceId
                ? static::getByResidenceId($residenceId, $normalizedFilters)
                : static::empty();
        } elseif (PetPolicy::isOperationCenter($user)) {
            $result = static::getByResidenceIds(static::operationCenterResidenceIds($user), $normalizedFilters);
        } else {
            $result = static::empty();
        }

        return static::$requestCache[$cacheKey] = $result;
    }

    public static function getDataForUser(User $user, array $filters = []): array
    {
        return static::getScopedDataForUser($user, $filters);
    }

    public static function getScopedCreationTrendForUser($user, array $filters = [], string $period = 'day'): array
    {
        if (! $user instanceof User) {
            return static::emptyCreationTrend();
        }

        $normalizedFilters = PetDashboardSupport::normalizedFilters($filters);
        $cacheKey = 'trend_'.$user->id.'_'.md5(json_encode($normalizedFilters)).'_'.$period;

        if (isset(static::$requestCache[$cacheKey])) {
            return static::$requestCache[$cacheKey];
        }

        if (PetPolicy::hasAdminDashboardAccess($user)) {
            $query = static::baseQuery();
            static::applyFilters($query, $normalizedFilters);

            return static::$requestCache[$cacheKey] = static::computeCreationTrend($query, $period);
        }

        if (PetPolicy::isPropertyManager($user)) {
            $residenceId = static::propertyManagementResidenceId($user);

            if (! $residenceId) {
                return static::emptyCreationTrend();
            }

            $query = static::baseQuery()->where('units.residence_id', $residenceId);
            static::applyFilters($query, $normalizedFilters);

            return static::$requestCache[$cacheKey] = static::computeCreationTrend($query, $period);
        }

        if (PetPolicy::isOperationCenter($user)) {
            $residenceIds = static::operationCenterResidenceIds($user);
            if (empty($residenceIds)) {
                return static::emptyCreationTrend();
            }

            $query = static::baseQuery()->whereIn('units.residence_id', $residenceIds);
            static::applyFilters($query, $normalizedFilters);

            return static::$requestCache[$cacheKey] = static::computeCreationTrend($query, $period);
        }

        return static::emptyCreationTrend();
    }

    public static function getCreationTrendForUser(User $user, array $filters = [], string $period = 'day'): array
    {
        return static::getScopedCreationTrendForUser($user, $filters, $period);
    }

    public static function getAllData(array $filters = []): array
    {
        $normalizedFilters = PetDashboardSupport::normalizedFilters($filters);
        $hasFilters = ! empty(PetDashboardSupport::flattenFilters($normalizedFilters));

        if (! $hasFilters) {
            $cached = WidgetAggregate::getCached('pet_global_stats', 300, 'pet');
            if ($cached) {
                return $cached;
            }

            $query = static::baseQuery();
            $data = static::aggregateQuery($query);
            WidgetAggregate::putCache('pet_global_stats', $data, 'pet');

            return $data;
        }

        $cacheKey = 'psv_widget_'.static::cacheVersion().'_'.md5(json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($normalizedFilters) {
            $query = static::baseQuery();
            static::applyFilters($query, $normalizedFilters);

            return static::aggregateQuery($query);
        });
    }

    public static function getByResidenceId(int $residenceId, array $filters = []): array
    {
        $normalizedFilters = PetDashboardSupport::normalizedFilters($filters);
        $cacheKey = 'psv_pm_'.static::cacheVersion().'_'.$residenceId.'_'.md5(json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($residenceId, $normalizedFilters) {
            $query = static::baseQuery()->where('units.residence_id', $residenceId);
            static::applyFilters($query, $normalizedFilters);

            return static::aggregateQuery($query);
        });
    }

    public static function getByResidenceIds(array $residenceIds, array $filters = []): array
    {
        $residenceIds = array_values(array_unique(array_map('intval', array_filter($residenceIds))));

        if (empty($residenceIds)) {
            return static::empty();
        }

        sort($residenceIds);

        $normalizedFilters = PetDashboardSupport::normalizedFilters($filters);
        $cacheKey = 'psv_pmoc_'.static::cacheVersion().'_'.md5(implode(',', $residenceIds).'|'.json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($residenceIds, $normalizedFilters) {
            $query = static::baseQuery()->whereIn('units.residence_id', $residenceIds);
            static::applyFilters($query, $normalizedFilters);

            return static::aggregateQuery($query);
        });
    }

    public static function applyFilters(Builder $query, array $filters): void
    {
        $moobanFilters = $filters['residence_filters'] ?? [];

        $moobanTypes = (array) ($moobanFilters['mooban_type'] ?? []);
        if (! empty($moobanTypes)) {
            $query->whereIn('residences.mooban_type', $moobanTypes);
        }

        $subTypes = (array) ($moobanFilters['sub_type'] ?? []);
        if (! empty($subTypes)) {
            $query->whereIn('residences.sub_type', $subTypes);
        }

        $residenceId = $moobanFilters['residence_id'] ?? null;
        if (filled($residenceId)) {
            $query->where('units.residence_id', (int) $residenceId);
        }

        $provinceFilters = $filters['province_filters'] ?? [];
        $provinceIds = array_map('intval', array_filter((array) ($provinceFilters['province'] ?? [])));
        $districtIds = array_map('intval', array_filter((array) ($provinceFilters['district'] ?? [])));
        $subdistrictIds = array_map('intval', array_filter((array) ($provinceFilters['subdistrict'] ?? [])));
        $residenceIdsByLocation = ThailandLocationService::getResidenceIdsByLocation($provinceIds, $districtIds, $subdistrictIds);
        $hasLocationFilters = ! empty($provinceIds) || ! empty($districtIds) || ! empty($subdistrictIds);

        if ($hasLocationFilters) {
            QueryGuardSupport::whereInOrDenyAll($query, 'units.residence_id', $residenceIdsByLocation);
        }

        $mainRoad = $provinceFilters['main_road'] ?? null;
        if (filled($mainRoad)) {
            $query->where('residences.main_road', 'LIKE', '%'.$mainRoad.'%');
        }

        $statusFilters = (array) ($filters['status_filters']['residence_activation_status_id'] ?? []);
        if (! empty($statusFilters)) {
            $query->whereIn('residences.residence_activation_status_id', $statusFilters);
        }

        $dateFilters = $filters['date_filters'] ?? [];

        $createdFrom = $dateFilters['created_from'] ?? null;
        if (filled($createdFrom)) {
            $query->whereDate('pets.created_at', '>=', $createdFrom);
        }

        $createdUntil = $dateFilters['created_until'] ?? null;
        if (filled($createdUntil)) {
            $query->whereDate('pets.created_at', '<=', $createdUntil);
        }

        $updatedFrom = $dateFilters['updated_from'] ?? null;
        if (filled($updatedFrom)) {
            $query->whereDate('pets.updated_at', '>=', $updatedFrom);
        }

        $updatedUntil = $dateFilters['updated_until'] ?? null;
        if (filled($updatedUntil)) {
            $query->whereDate('pets.updated_at', '<=', $updatedUntil);
        }

        $petType = $filters['pets_info_filters']['pet_type'] ?? null;
        if (filled($petType)) {
            $query->where('pets.type', (int) $petType);
        }
    }

    protected static function baseQuery(): Builder
    {
        return Pet::query()
            ->join('units', 'pets.unit_id', '=', 'units.id', 'inner', false)
            ->join('residences', 'units.residence_id', '=', 'residences.id', 'inner', false)
            ->whereNull('pets.deleted_at', 'and', false)
            ->whereNull('units.deleted_at', 'and', false)
            ->whereNull('residences.deleted_at', 'and', false);
    }

    protected static function aggregateQuery(Builder $base): array
    {
        $stats = (clone $base)->selectRaw(
            'COUNT(*) as total,
             SUM(CASE WHEN pets.type = ? THEN 1 ELSE 0 END) as dog_count,
             SUM(CASE WHEN pets.type = ? THEN 1 ELSE 0 END) as cat_count,
             SUM(CASE WHEN pets.type IS NULL THEN 1 ELSE 0 END) as not_set_count',
            [PetType::DOG->value, PetType::CAT->value]
        )->first();

        return [
            'total_pets' => (int) ($stats->total ?? 0),
            'type_counts' => [
                'dog' => (int) ($stats->dog_count ?? 0),
                'cat' => (int) ($stats->cat_count ?? 0),
                'not_set' => (int) ($stats->not_set_count ?? 0),
            ],
            'age_distribution' => static::ageDistribution($base),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    protected static function ageRanges(): array
    {
        return [
            'lt_2' => 2,
            'lt_4' => 4,
            'lt_6' => 6,
            'lt_8' => 8,
            'lt_10' => 10,
            'lt_12' => 12,
            'lt_14' => 14,
            'lt_16' => 16,
            'lt_18' => 18,
            'lt_20' => 20,
            'not_set' => null,
        ];
    }

    /**
     * @return array<string, int>
     */
    protected static function ageDistribution(Builder $base): array
    {
        $currentYear = (int) now()->year;
        $ranges = static::ageRanges();
        $counts = array_fill_keys(array_keys($ranges), 0);

        $rows = (clone $base)
            ->selectRaw('pets.year, COUNT(*) as total')
            ->groupBy('pets.year')
            ->get();

        foreach ($rows as $row) {
            $total = (int) ($row->total ?? 0);

            if (! $row->year) {
                $counts['not_set'] += $total;

                continue;
            }

            $age = $currentYear - (int) $row->year;

            foreach ($ranges as $bucket => $maxAge) {
                if ($maxAge === null) {
                    continue;
                }

                if ($age < $maxAge) {
                    $counts[$bucket] += $total;
                    break;
                }
            }
        }

        return $counts;
    }

    /**
     * @return array{dog: array<string, int>, cat: array<string, int>}
     */
    protected static function computeCreationTrend(Builder $base, string $period = 'day'): array
    {
        $startDate = match ($period) {
            'week' => Carbon::now()->subWeek()->startOfDay(),
            'month' => Carbon::now()->subMonths(6)->startOfMonth(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        $bucketExpression = match ($period) {
            'month' => "DATE_FORMAT(pets.created_at, '%Y-%m-01')",
            default => 'DATE(pets.created_at)',
        };

        $rows = (clone $base)
            ->whereNotNull('pets.created_at')
            ->where('pets.created_at', '>=', $startDate)
            ->selectRaw("pets.type as type, {$bucketExpression} as trend_date, COUNT(*) as aggregate")
            ->groupBy('type', 'trend_date')
            ->orderBy('trend_date', 'asc')
            ->get();

        $dog = [];
        $cat = [];

        foreach ($rows as $row) {
            if ((int) $row->type === PetType::DOG->value) {
                $dog[$row->trend_date] = (int) $row->aggregate;
            } elseif ((int) $row->type === PetType::CAT->value) {
                $cat[$row->trend_date] = (int) $row->aggregate;
            }
        }

        return [
            'dog' => $dog,
            'cat' => $cat,
        ];
    }

    protected static function propertyManagementResidenceId(User $user): ?int
    {
        $residenceId = DB::table('residences')
            ->where('property_management_user_id', $user->id)
            ->value('id');

        return $residenceId ? (int) $residenceId : null;
    }

    /**
     * @return array<int, int>
     */
    protected static function operationCenterResidenceIds(User $user): array
    {
        return array_values(array_unique(array_map(
            'intval',
            array_filter((array) get_residence_by_property_management_operation_center($user->id))
        )));
    }

    public static function bumpCacheVersion(): void
    {
        Cache::increment(self::CACHE_VERSION_KEY);
    }

    protected static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    /**
     * @return array{total_pets: int, type_counts: array<string, int>, age_distribution: array<string, int>}
     */
    public static function empty(): array
    {
        return [
            'total_pets' => 0,
            'type_counts' => [
                'dog' => 0,
                'cat' => 0,
                'not_set' => 0,
            ],
            'age_distribution' => array_fill_keys(array_keys(static::ageRanges()), 0),
        ];
    }

    /**
     * @return array{dog: array<string, int>, cat: array<string, int>}
     */
    public static function emptyCreationTrend(): array
    {
        return [
            'dog' => [],
            'cat' => [],
        ];
    }
}
