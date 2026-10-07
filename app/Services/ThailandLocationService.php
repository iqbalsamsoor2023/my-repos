<?php

namespace App\Services;

use App\Enums\Residence\LocationTagType;
use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandProvince;
use App\Models\Erp\ThailandSubDistrict;
use App\Models\ErpLocationTag;
use App\Models\Residence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ThailandLocationService
{
    /**
     * Get all provinces as [id => "English Name (Thai Name)"] options.
     *
     * @return array<int, string>
     */
    public static function getProvinces(): array
    {
        return Cache::remember('thailand_locations:provinces', now()->addHours(6), function (): array {
            return ThailandProvince::query()
                ->orderBy('name_in_english')
                ->get(['id', 'name_in_english', 'name_in_thai'])
                ->mapWithKeys(fn (ThailandProvince $province) => [
                    $province->id => $province->getRawOriginal('name_in_english')." ({$province->name_in_thai})",
                ])
                ->toArray();
        });
    }

    /**
     * Get districts filtered by province IDs.
     *
     * @param  array<int>  $provinceIds
     * @return array<int, string>
     */
    public static function getDistrictsByProvinces(array $provinceIds): array
    {
        if (empty($provinceIds)) {
            return [];
        }

        $cacheKey = 'thailand_locations:districts:'.md5(json_encode(array_values($provinceIds)));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($provinceIds): array {
            return ThailandDistrict::query()
                ->whereIn('province_id', $provinceIds)
                ->orderBy('name_in_english')
                ->get(['id', 'name_in_english', 'name_in_thai'])
                ->mapWithKeys(fn (ThailandDistrict $district) => [
                    $district->id => $district->getRawOriginal('name_in_english')." ({$district->name_in_thai})",
                ])
                ->toArray();
        });
    }

    /**
     * Get subdistricts filtered by district IDs.
     *
     * @param  array<int>  $districtIds
     * @return array<int, string>
     */
    public static function getSubdistrictsByDistricts(array $districtIds): array
    {
        if (empty($districtIds)) {
            return [];
        }

        $cacheKey = 'thailand_locations:subdistricts:'.md5(json_encode(array_values($districtIds)));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($districtIds): array {
            return ThailandSubDistrict::query()
                ->whereIn('district_id', $districtIds)
                ->orderBy('name_in_english')
                ->get(['id', 'name_in_english', 'name_in_thai'])
                ->mapWithKeys(fn (ThailandSubDistrict $subdistrict) => [
                    $subdistrict->id => "{$subdistrict->getRawOriginal('name_in_english')} ({$subdistrict->name_in_thai})",
                ])
                ->toArray();
        });
    }

    /**
     * Get main roads filtered by district IDs.
     *
     * @param  array<int>  $districtIds
     * @return array<string, string>
     */
    public static function getMainRoadsByDistricts(array $districtIds): array
    {
        $districtIds = self::normalizeIds($districtIds);

        if (empty($districtIds)) {
            return [];
        }

        $cacheKey = 'thailand_locations:main_roads:'.md5(json_encode($districtIds));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($districtIds): array {
            return ErpLocationTag::query()
                ->whereJsonContains('thailand_districts', $districtIds)
                ->where('location_tag_type_id', LocationTagType::MAIN_ROAD->value)
                ->orderBy('name', 'asc')
                ->get(['name', 'name_th'])
                ->mapWithKeys(fn (ErpLocationTag $tag) => [
                    $tag->name => "{$tag->name} ({$tag->name_th})",
                ])
                ->toArray();
        });
    }

    /**
     * Resolve residence IDs for dependent location filters.
     *
     * This keeps nested location joins on the much smaller residences table,
     * so large module tables can filter by residence_id with a fast whereIn.
     *
     * @param  array<int>  $provinceIds
     * @param  array<int>  $districtIds
     * @param  array<int>  $subdistrictIds
     * @return array<int>
     */
    public static function getResidenceIdsByLocation(
        array $provinceIds = [],
        array $districtIds = [],
        array $subdistrictIds = []
    ): array {
        $provinceIds = self::normalizeIds($provinceIds);
        $districtIds = self::normalizeIds($districtIds);
        $subdistrictIds = self::normalizeIds($subdistrictIds);

        if (empty($provinceIds) && empty($districtIds) && empty($subdistrictIds)) {
            return [];
        }

        $cacheKey = 'thailand_locations:residence_ids:'.md5(json_encode([
            $provinceIds,
            $districtIds,
            $subdistrictIds,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($provinceIds, $districtIds, $subdistrictIds): array {
            return Residence::query()
                ->select('id')
                ->when(
                    ! empty($subdistrictIds),
                    fn (Builder $query): Builder => $query->whereIn('subdistrict_id', $subdistrictIds)
                )
                ->when(
                    ! empty($districtIds),
                    fn (Builder $query): Builder => $query->whereHas(
                        'subdistrict.district',
                        fn ($subdistrictQuery) => $subdistrictQuery->whereIn('id', $districtIds)
                    )
                )
                ->when(
                    ! empty($provinceIds),
                    fn (Builder $query): Builder => $query->whereHas(
                        'subdistrict.district.province',
                        fn ($provinceQuery) => $provinceQuery->whereIn('id', $provinceIds)
                    )
                )
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        });
    }

    /**
     * @param  array<int|string|null>  $ids
     * @return array<int>
     */
    private static function normalizeIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));

        sort($ids);

        return $ids;
    }
}
