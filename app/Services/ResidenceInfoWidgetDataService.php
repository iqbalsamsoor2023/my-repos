<?php

namespace App\Services;

use App\Enums\Residence\EntranceBarrierType;
use App\Enums\Residence\InternetProviderCompany;
use App\Enums\Residence\MoobanType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResidenceInfoWidgetDataService
{
    /**
     * @param  array<string, mixed>  $tableFilters
     * @return array<string, mixed>
     */
    public static function getAllData(array $tableFilters = []): array
    {
        $locationFilters = self::normalizeLocationFilters((array) ($tableFilters['province_filters'] ?? []));

        $cacheKey = 'residence_info_widget_data_'.md5(json_encode($locationFilters));

        return Cache::remember($cacheKey, 60, fn (): array => self::computeFromReadModel($locationFilters));
    }

    /**
     * @param  array<string, mixed>  $locationFilters
     * @return array<string, mixed>
     */
    protected static function computeFromReadModel(array $locationFilters): array
    {
        $baseQuery = DB::table('residence_stats_view')
            ->where('mooban_type', MoobanType::PUBLIC->value);

        self::applyLocationFilters($baseQuery, $locationFilters);

        $totalResidences = (clone $baseQuery)->count();

        $entryNumbers = self::entryNumberCounts(clone $baseQuery);
        $laneTypes = self::laneTypeCounts(clone $baseQuery);
        $roofPresence = self::roofPresenceCounts(clone $baseQuery);
        $entranceBarrierTypes = self::entranceBarrierCounts(clone $baseQuery);
        $internetProviders = self::internetProviderCounts(clone $baseQuery);
        $cctvPresence = self::cctvPresenceCounts(clone $baseQuery);
        $cctvRanges = self::cctvRangeCounts(clone $baseQuery);

        return [
            'total_residences' => (int) $totalResidences,
            'entry_numbers' => $entryNumbers,
            'guard_house_lane_types' => $laneTypes,
            'guard_house_roof' => $roofPresence,
            'entrance_barrier_types' => $entranceBarrierTypes,
            'internet_providers' => $internetProviders,
            'cctv_presence' => $cctvPresence,
            'cctv_ranges' => $cctvRanges,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected static function applyLocationFilters($query, array $filters): void
    {
        if (! empty($filters['province'])) {
            $query->whereIn('province_id', (array) $filters['province']);
        }

        if (! empty($filters['district'])) {
            $query->whereIn('district_id', (array) $filters['district']);
        }

        if (! empty($filters['subdistrict'])) {
            $query->whereIn('subdistrict_id', (array) $filters['subdistrict']);
        }
    }

    /**
     * @param  array<string, mixed>  $locationFilters
     * @return array<string, array<int>>
     */
    public static function normalizeLocationFilters(array $locationFilters): array
    {
        return [
            'province' => self::normalizeIds((array) ($locationFilters['province'] ?? [])),
            'district' => self::normalizeIds((array) ($locationFilters['district'] ?? [])),
            'subdistrict' => self::normalizeIds((array) ($locationFilters['subdistrict'] ?? [])),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected static function entryNumberCounts($query): array
    {
        $rows = $query
            ->selectRaw('guard_house_entry_number, COUNT(*) as count')
            ->groupBy('guard_house_entry_number')
            ->get();

        $counts = ['null' => 0];

        foreach ($rows as $row) {
            $key = $row->guard_house_entry_number === null
                ? 'null'
                : (string) (int) $row->guard_house_entry_number;

            $counts[$key] = (int) $row->count;
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    protected static function laneTypeCounts($query): array
    {
        $rows = $query
            ->selectRaw('guard_house_lane_type, COUNT(*) as count')
            ->groupBy('guard_house_lane_type')
            ->get();

        $counts = ['null' => 0];

        foreach ($rows as $row) {
            $key = $row->guard_house_lane_type === null
                ? 'null'
                : (string) (int) $row->guard_house_lane_type;

            $counts[$key] = (int) $row->count;
        }

        return $counts;
    }

    /**
     * @return array{has_roof: int, no_roof: int, null: int}
     */
    protected static function roofPresenceCounts($query): array
    {
        $result = $query
            ->selectRaw('SUM(CASE WHEN has_roof = 1 THEN 1 ELSE 0 END) as has_roof')
            ->selectRaw('SUM(CASE WHEN has_roof = 0 THEN 1 ELSE 0 END) as no_roof')
            ->selectRaw('SUM(CASE WHEN has_roof IS NULL THEN 1 ELSE 0 END) as null_count')
            ->first();

        return [
            'has_roof' => (int) ($result->has_roof ?? 0),
            'no_roof' => (int) ($result->no_roof ?? 0),
            'null' => (int) ($result->null_count ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected static function entranceBarrierCounts($query): array
    {
        $selects = collect(EntranceBarrierType::cases())
            ->map(fn (EntranceBarrierType $type): string => "SUM(CASE WHEN entrance_barrier_type = {$type->value} THEN 1 ELSE 0 END) as type_{$type->value}")
            ->all();

        $selects[] = 'SUM(CASE WHEN entrance_barrier_type IS NULL THEN 1 ELSE 0 END) as type_null';

        $result = $query->selectRaw(implode(', ', $selects))->first();

        $counts = ['null' => (int) ($result->type_null ?? 0)];

        foreach (EntranceBarrierType::cases() as $type) {
            $counts[(string) $type->value] = (int) ($result->{'type_'.$type->value} ?? 0);
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    protected static function internetProviderCounts($query): array
    {
        $selects = collect(InternetProviderCompany::cases())
            ->map(fn (InternetProviderCompany $provider): string => "SUM(CASE WHEN JSON_CONTAINS(internet_provider_id, '\"{$provider->value}\"') THEN 1 ELSE 0 END) AS provider_{$provider->value}")
            ->all();

        $selects[] = 'SUM(CASE WHEN internet_provider_id IS NULL OR JSON_LENGTH(internet_provider_id) = 0 THEN 1 ELSE 0 END) AS not_set_count';

        $result = $query->selectRaw(implode(', ', $selects))->first();

        $counts = ['not_set' => (int) ($result->not_set_count ?? 0)];

        foreach (InternetProviderCompany::cases() as $provider) {
            $counts[(string) $provider->value] = (int) ($result->{'provider_'.$provider->value} ?? 0);
        }

        return $counts;
    }

    /**
     * @return array{yes: int, no: int, not_set: int}
     */
    protected static function cctvPresenceCounts($query): array
    {
        $result = $query
            ->selectRaw('SUM(CASE WHEN has_cctv = 1 THEN 1 ELSE 0 END) as yes_count')
            ->selectRaw('SUM(CASE WHEN has_cctv = 0 THEN 1 ELSE 0 END) as no_count')
            ->selectRaw('SUM(CASE WHEN has_cctv IS NULL THEN 1 ELSE 0 END) as not_set_count')
            ->first();

        return [
            'yes' => (int) ($result->yes_count ?? 0),
            'no' => (int) ($result->no_count ?? 0),
            'not_set' => (int) ($result->not_set_count ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected static function cctvRangeCounts($query): array
    {
        $rangeSelects = collect(range(1, 10))
            ->map(fn (int $range): string => 'SUM(CASE WHEN cctv_count BETWEEN '.(($range - 1) * 10 + 1).' AND '.($range * 10).' AND has_cctv = 1 THEN 1 ELSE 0 END) as range_'.$range)
            ->all();

        $rangeSelects[] = 'SUM(CASE WHEN has_cctv IS NULL OR has_cctv = 0 THEN 1 ELSE 0 END) as not_set_count';

        $result = $query->selectRaw(implode(', ', $rangeSelects))->first();

        $counts = [
            'not_set' => (int) ($result->not_set_count ?? 0),
        ];

        foreach (range(1, 10) as $range) {
            $counts['range_'.$range] = (int) ($result->{'range_'.$range} ?? 0);
        }

        return $counts;
    }

    /**
     * @param  array<int|string|null>  $ids
     * @return array<int>
     */
    protected static function normalizeIds(array $ids): array
    {
        $normalized = array_values(array_unique(array_map(
            'intval',
            array_filter($ids, static fn ($value): bool => $value !== null && $value !== '')
        )));

        sort($normalized);

        return $normalized;
    }
}
