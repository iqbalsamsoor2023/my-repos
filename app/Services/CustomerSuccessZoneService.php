<?php

namespace App\Services;

use App\Models\Erp\CustomerSuccessZone;
use App\Models\Erp\DistrictCustomerSuccessZone;
use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandProvince;
use App\Support\QueryGuardSupport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class CustomerSuccessZoneService
{
    /**
     * @return array<int, string>
     */
    public static function getOptions(): array
    {
        $cacheVersion = self::getOptionsCacheVersion();
        $cacheKey = "customer_success_zones:options:v3:{$cacheVersion}";

        return Cache::remember($cacheKey, now()->addHours(1), function (): array {
            $options = CustomerSuccessZone::query()
                ->pluck('name', 'id')
                ->toArray();

            natcasesort($options);

            return $options;
        });
    }

    private static function getOptionsCacheVersion(): string
    {
        $snapshot = CustomerSuccessZone::query()
            ->selectRaw('COUNT(*) as total_count, MAX(id) as max_id, MAX(updated_at) as max_updated_at')
            ->first();

        $totalCount = (int) ($snapshot?->total_count ?? 0);
        $maxId = (int) ($snapshot?->max_id ?? 0);
        $maxUpdatedAt = (string) ($snapshot?->max_updated_at ?? '');

        return md5("{$totalCount}|{$maxId}|{$maxUpdatedAt}");
    }

    /**
     * @param  array<int|string|null>  $customerSuccessZoneIds
     * @return array<int>
     */
    public static function getDistrictIds(array $customerSuccessZoneIds): array
    {
        $customerSuccessZoneIds = self::normalizeIds($customerSuccessZoneIds);

        if (empty($customerSuccessZoneIds)) {
            return [];
        }

        $cacheKey = 'customer_success_zones:district_ids:'.md5(json_encode($customerSuccessZoneIds));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($customerSuccessZoneIds): array {
            $districtIds = DistrictCustomerSuccessZone::query()
                ->whereIn('customer_success_zone_id', $customerSuccessZoneIds, 'and', false)
                ->distinct()
                ->pluck('thailand_district_id')
                ->map(fn ($districtId): int => (int) $districtId)
                ->all();

            sort($districtIds);

            return $districtIds;
        });
    }

    /**
     * @param  array<int|string|null>  $customerSuccessZoneIds
     * @return array<int, string>
     */
    public static function getProvinceOptions(array $customerSuccessZoneIds = []): array
    {
        $customerSuccessZoneIds = self::normalizeIds($customerSuccessZoneIds);

        if (empty($customerSuccessZoneIds)) {
            return ThailandLocationService::getProvinces();
        }

        $districtIds = self::getDistrictIds($customerSuccessZoneIds);

        if (empty($districtIds)) {
            return [];
        }

        $provinceIds = ThailandDistrict::query()
            ->whereIn('id', $districtIds, 'and', false)
            ->distinct()
            ->pluck('province_id')
            ->map(fn ($provinceId): int => (int) $provinceId)
            ->all();

        if (empty($provinceIds)) {
            return [];
        }

        return ThailandProvince::query()
            ->whereIn('id', $provinceIds, 'and', false)
            ->orderBy('name_in_english', 'asc')
            ->get(['id', 'name_in_english', 'name_in_thai'])
            ->mapWithKeys(fn (ThailandProvince $province) => [
                $province->id => $province->getRawOriginal('name_in_english')." ({$province->name_in_thai})",
            ])
            ->toArray();
    }

    /**
     * @param  array<int|string|null>  $provinceIds
     * @param  array<int|string|null>  $customerSuccessZoneIds
     * @return array<int, string>
     */
    public static function getDistrictOptions(array $provinceIds = [], array $customerSuccessZoneIds = []): array
    {
        $provinceIds = self::normalizeIds($provinceIds);
        $customerSuccessZoneIds = self::normalizeIds($customerSuccessZoneIds);

        if (empty($customerSuccessZoneIds)) {
            return ThailandLocationService::getDistrictsByProvinces($provinceIds);
        }

        $districtIds = self::getDistrictIds($customerSuccessZoneIds);

        if (empty($districtIds)) {
            return [];
        }

        $query = ThailandDistrict::query()
            ->whereIn('id', $districtIds, 'and', false)
            ->orderBy('name_in_english', 'asc');

        if (! empty($provinceIds)) {
            $query->whereIn('province_id', $provinceIds, 'and', false);
        }

        return $query->get(['id', 'name_in_english', 'name_in_thai'])
            ->mapWithKeys(fn (ThailandDistrict $district) => [
                $district->id => $district->getRawOriginal('name_in_english')." ({$district->name_in_thai})",
            ])
            ->toArray();
    }

    /**
     * @param  array<int|string|null>  $customerSuccessZoneIds
     */
    public static function constrainResidenceQueryByZones(Builder $query, array $customerSuccessZoneIds): Builder
    {
        $customerSuccessZoneIds = self::normalizeIds($customerSuccessZoneIds);

        if (empty($customerSuccessZoneIds)) {
            return $query;
        }

        $districtIds = self::getDistrictIds($customerSuccessZoneIds);

        if (empty($districtIds)) {
            return QueryGuardSupport::denyAll($query);
        }

        return $query->whereHas('subdistrict.district', fn (Builder $districtQuery) => $districtQuery->whereIn('id', $districtIds));
    }

    /**
     * @param  array<int|string|null>  $ids
     * @return array<int>
     */
    private static function normalizeIds(array $ids): array
    {
        $normalized = array_values(array_unique(array_map(
            'intval',
            array_filter($ids, static fn ($value) => $value !== null && $value !== '')
        )));

        sort($normalized);

        return $normalized;
    }
}
