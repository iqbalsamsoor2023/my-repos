<?php

namespace App\Services;

use App\Enums\Vehicle\VehicleType;
use App\Models\User;
use App\Models\WidgetAggregate;
use App\Policies\VehiclePolicy;
use App\Support\VehicleAgeCategorySupport;
use App\Support\VehicleDashboardSupport;
use App\Support\WidgetColorPalette;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VehicleWidgetDataService
{
    private const FILTERED_CACHE_TTL_SECONDS = 60;

    private const CACHE_VERSION_KEY = 'vsv_widget_version';

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

        $normalizedFilters = VehicleDashboardSupport::normalizedFilters($filters);
        $cacheKey = 'data_'.$user->id.'_'.md5(json_encode($normalizedFilters));

        if (isset(static::$requestCache[$cacheKey])) {
            return static::$requestCache[$cacheKey];
        }

        if (VehiclePolicy::hasAdminDashboardAccess($user)) {
            $result = static::getAllData($normalizedFilters);
        } elseif (VehiclePolicy::isPropertyManager($user)) {
            $residenceId = DB::table('residences')
                ->where('property_management_user_id', $user->id)
                ->value('id');

            $result = $residenceId
                ? static::getByResidenceId((int) $residenceId, $normalizedFilters)
                : static::empty();
        } elseif (VehiclePolicy::isOperationCenter($user)) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);

            $result = static::getByResidenceIds($residenceIds, $normalizedFilters);
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
            return ['car' => [], 'motorcycle' => []];
        }

        $normalizedFilters = VehicleDashboardSupport::normalizedFilters($filters);
        $cacheKey = 'trend_'.$user->id.'_'.md5(json_encode($normalizedFilters)).'_'.$period;

        if (isset(static::$requestCache[$cacheKey])) {
            return static::$requestCache[$cacheKey];
        }

        $base = DB::table('vehicle_stats_view');

        if (! VehicleDashboardSupport::applyReadModelScope($base, $user)) {
            return ['car' => [], 'motorcycle' => []];
        }

        static::applyReadModelFilters($base, $normalizedFilters);

        return static::$requestCache[$cacheKey] = static::computeCreationTrend($base, $period);
    }

    public static function getCreationTrendForUser(User $user, array $filters, string $period = 'day'): array
    {
        return static::getScopedCreationTrendForUser($user, $filters, $period);
    }

    public static function getAllData(array $filters = []): array
    {
        $normalizedFilters = VehicleDashboardSupport::normalizedFilters($filters);
        $hasFilters = ! empty(VehicleDashboardSupport::flattenFilters($normalizedFilters));

        if (! $hasFilters) {
            $cached = WidgetAggregate::getCached('vehicle_global_stats', 300, 'vehicle');
            if ($cached) {
                return $cached;
            }

            $data = static::computeFromReadModel([]);
            WidgetAggregate::putCache('vehicle_global_stats', $data, 'vehicle');

            return $data;
        }

        $cacheKey = 'vsv_widget_'.static::cacheVersion().'_'.md5(json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, fn () => static::computeFromReadModel($normalizedFilters));
    }

    public static function getByResidenceId(int $residenceId, array $filters = []): array
    {
        $normalizedFilters = VehicleDashboardSupport::normalizedFilters($filters);
        $cacheKey = 'vsv_pm_'.static::cacheVersion().'_'.$residenceId.'_'.md5(json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($residenceId, $normalizedFilters) {
            $base = DB::table('vehicle_stats_view')->where('residence_id', $residenceId);
            static::applyReadModelFilters($base, $normalizedFilters);

            $result = static::aggregateQuery($base);
            $result['creation_trend'] = static::computeCreationTrend($base);

            return $result;
        });
    }

    public static function getByResidenceIds(array $residenceIds, array $filters = []): array
    {
        if (empty($residenceIds)) {
            return static::empty();
        }

        sort($residenceIds);
        $normalizedFilters = VehicleDashboardSupport::normalizedFilters($filters);
        $cacheKey = 'vsv_pmoc_'.static::cacheVersion().'_'.md5(implode(',', $residenceIds).'|'.json_encode($normalizedFilters));

        return Cache::remember($cacheKey, self::FILTERED_CACHE_TTL_SECONDS, function () use ($residenceIds, $normalizedFilters) {
            $base = DB::table('vehicle_stats_view')->whereIn('residence_id', $residenceIds);
            static::applyReadModelFilters($base, $normalizedFilters);

            $result = static::aggregateQuery($base);
            $result['creation_trend'] = static::computeCreationTrend($base);

            return $result;
        });
    }

    public static function computeFromReadModel(array $filters): array
    {
        $normalizedFilters = VehicleDashboardSupport::normalizedFilters($filters);
        $base = DB::table('vehicle_stats_view');
        static::applyReadModelFilters($base, $normalizedFilters);

        $result = static::aggregateQuery($base);
        $result['creation_trend'] = static::computeCreationTrend($base);

        return $result;
    }

    public static function ageColorMap(): array
    {
        return WidgetColorPalette::vehicleAge();
    }

    protected static function aggregateQuery($base): array
    {
        $typeCounts = (clone $base)
            ->selectRaw('vehicle_type, COUNT(*) as total')
            ->groupBy('vehicle_type')
            ->pluck('total', 'vehicle_type');

        $totalCars = (int) ($typeCounts[VehicleType::CAR->value] ?? 0);
        $totalMotorcycles = (int) ($typeCounts[VehicleType::MOTORCYCLE->value] ?? 0);

        $carModelYears = static::groupByModelYear($base, VehicleType::CAR->value);
        $motorcycleModelYears = static::groupByModelYear($base, VehicleType::MOTORCYCLE->value);

        return [
            'total_vehicles' => $totalCars + $totalMotorcycles,
            'total_cars' => $totalCars,
            'total_motorcycles' => $totalMotorcycles,
            'car_brands' => static::groupByBrand($base, VehicleType::CAR->value),
            'motorcycle_brands' => static::groupByBrand($base, VehicleType::MOTORCYCLE->value),
            'car_fuel_types' => static::groupByNullableInt(clone $base, 'fuel_type', VehicleType::CAR->value),
            'car_body_types' => static::groupByNullableInt(clone $base, 'body_type', VehicleType::CAR->value),
            'motorcycle_body_types' => static::groupByNullableInt(clone $base, 'body_type', VehicleType::MOTORCYCLE->value),
            'car_model_years' => $carModelYears,
            'motorcycle_model_years' => $motorcycleModelYears,
            'car_age_buckets' => static::bucketModelYearsByAge($carModelYears),
            'motorcycle_age_buckets' => static::bucketModelYearsByAge($motorcycleModelYears),
            'car_insurance' => static::groupByInsurance($base, VehicleType::CAR->value),
            'motorcycle_insurance' => static::groupByInsurance($base, VehicleType::MOTORCYCLE->value),
            'creation_trend' => [],
        ];
    }

    protected static function groupByBrand($base, int $vehicleType): array
    {
        return (clone $base)
            ->where('vehicle_type', $vehicleType)
            ->whereNotNull('vehicle_brand_name')
            ->selectRaw('vehicle_brand_name as name, COUNT(*) as count')
            ->groupBy('vehicle_brand_name')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->count])
            ->toArray();
    }

    protected static function groupByModelYear($base, int $vehicleType): array
    {
        return (clone $base)
            ->where('vehicle_type', $vehicleType)
            ->selectRaw('model_year, COUNT(*) as total')
            ->groupBy('model_year')
            ->pluck('total', 'model_year')
            ->mapWithKeys(fn ($v, $k) => [$k === null ? 'null' : (int) $k => (int) $v])
            ->toArray();
    }

    protected static function groupByInsurance($base, int $vehicleType): array
    {
        return (clone $base)
            ->where('vehicle_type', $vehicleType)
            ->whereNotNull('insurance_company_name')
            ->selectRaw('insurance_company_name as name, insurance_company_name_en as name_en, COUNT(*) as count')
            ->groupBy('insurance_company_name', 'insurance_company_name_en')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'name_en' => $r->name_en, 'count' => (int) $r->count])
            ->toArray();
    }

    public static function bucketModelYearsByAge(array $modelYears): array
    {
        return VehicleAgeCategorySupport::bucketModelYearsByAge($modelYears);
    }

    protected static function groupByNullableInt($base, string $column, int $vehicleType): array
    {
        return (clone $base)
            ->where('vehicle_type', $vehicleType)
            ->selectRaw("COALESCE(CAST({$column} AS CHAR), 'null') as the_key, COUNT(*) as total")
            ->groupBy('the_key')
            ->pluck('total', 'the_key')
            ->mapWithKeys(fn ($v, $k) => [is_numeric($k) ? (int) $k : $k => (int) $v])
            ->toArray();
    }

    public static function computeCreationTrend($base, string $period = 'day'): array
    {
        $startDate = match ($period) {
            'week' => Carbon::now()->subWeek()->startOfDay(),
            'month' => Carbon::now()->subMonths(6)->startOfMonth(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        $column = 'vehicle_created_at';
        $bucketExpression = match ($period) {
            'month' => "DATE_FORMAT({$column}, '%Y-%m-01')",
            default => "DATE({$column})",
        };

        $rows = (clone $base)
            ->whereNotNull($column)
            ->where($column, '>=', $startDate)
            ->selectRaw("vehicle_type, {$bucketExpression} as trend_date, COUNT(*) as aggregate")
            ->groupBy('vehicle_type', 'trend_date')
            ->orderBy('trend_date')
            ->get();

        $car = [];
        $motorcycle = [];

        foreach ($rows as $row) {
            if ((int) $row->vehicle_type === VehicleType::CAR->value) {
                $car[$row->trend_date] = (int) $row->aggregate;
            } else {
                $motorcycle[$row->trend_date] = (int) $row->aggregate;
            }
        }

        return ['car' => $car, 'motorcycle' => $motorcycle];
    }

    public static function applyReadModelFilters($query, array $filters): void
    {
        $residenceId = $filters['residence_filters']['name'] ?? null;
        if (! empty($residenceId)) {
            $query->where('residence_id', $residenceId);
        }

        // mooban_filters.residence_id (Admin-only mooban filter also has a residence select)
        $moobanResidenceId = $filters['mooban_filters']['residence_id'] ?? null;
        if (! empty($moobanResidenceId)) {
            $query->where('residence_id', $moobanResidenceId);
        }

        $moobanTypes = $filters['mooban_filters']['mooban_type'] ?? [];
        if (! empty($moobanTypes)) {
            $query->whereIn('mooban_type', (array) $moobanTypes);
        }

        $subTypes = $filters['mooban_filters']['sub_type'] ?? [];
        if (! empty($subTypes)) {
            $query->whereIn('sub_type', (array) $subTypes);
        }

        // province_filters — resolve province → district → subdistrict chain
        $provinceSubdistrictIds = null;
        $provinceSubdistricts = $filters['province_filters']['subdistrict'] ?? [];
        if (! empty($provinceSubdistricts)) {
            $provinceSubdistrictIds = (array) $provinceSubdistricts;
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

        $vehicleTypes = $filters['vehicle_filters']['vehicle_type'] ?? [];
        if (! empty($vehicleTypes)) {
            $query->whereIn('vehicle_type', array_map('intval', (array) $vehicleTypes));
        }

        $carBrandIds = $filters['vehicle_filters']['car_brand'] ?? [];
        if (! empty($carBrandIds)) {
            $query->whereIn('vehicle_brand_id', array_map('intval', (array) $carBrandIds));
        }

        $motorcycleBrandIds = $filters['vehicle_filters']['motorcycle_brand'] ?? [];
        if (! empty($motorcycleBrandIds)) {
            $query->whereIn('vehicle_brand_id', array_map('intval', (array) $motorcycleBrandIds));
        }

        $fuelTypes = $filters['vehicle_filters']['fuel_type'] ?? [];
        if (! empty($fuelTypes)) {
            $query->whereIn('fuel_type', array_map('intval', (array) $fuelTypes));
        }

        $bodyTypes = $filters['vehicle_filters']['body_type'] ?? [];
        if (! empty($bodyTypes)) {
            $query->whereIn('body_type', array_map('intval', (array) $bodyTypes));
        }

        $insuranceCompanyIds = $filters['vehicle_filters']['insurance_company'] ?? [];
        if (! empty($insuranceCompanyIds)) {
            $query->whereIn('insurance_company_id', array_map('intval', (array) $insuranceCompanyIds));
        }

        $ageCategories = $filters['vehicle_filters']['age_category'] ?? [];
        if (! empty($ageCategories)) {
            VehicleAgeCategorySupport::applyCategoryFilters($query, (array) $ageCategories);
        }

        $createdFrom = $filters['date_filters']['created_from'] ?? null;
        if (! empty($createdFrom)) {
            $query->whereDate('vehicle_created_at', '>=', $createdFrom);
        }

        $createdUntil = $filters['date_filters']['created_until'] ?? null;
        if (! empty($createdUntil)) {
            $query->whereDate('vehicle_created_at', '<=', $createdUntil);
        }

        $updatedFrom = $filters['date_filters']['updated_from'] ?? null;
        if (! empty($updatedFrom)) {
            $query->whereDate('vehicle_updated_at', '>=', $updatedFrom);
        }

        $updatedUntil = $filters['date_filters']['updated_until'] ?? null;
        if (! empty($updatedUntil)) {
            $query->whereDate('vehicle_updated_at', '<=', $updatedUntil);
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

    public static function empty(): array
    {
        return [
            'total_vehicles' => 0,
            'total_cars' => 0,
            'total_motorcycles' => 0,
            'car_brands' => [],
            'motorcycle_brands' => [],
            'car_fuel_types' => [],
            'car_body_types' => [],
            'motorcycle_body_types' => [],
            'car_model_years' => [],
            'motorcycle_model_years' => [],
            'car_age_buckets' => VehicleAgeCategorySupport::emptyBuckets(),
            'motorcycle_age_buckets' => VehicleAgeCategorySupport::emptyBuckets(),
            'car_insurance' => [],
            'motorcycle_insurance' => [],
            'creation_trend' => ['car' => [], 'motorcycle' => []],
        ];
    }
}
