<?php

namespace App\Services;

use App\Models\WidgetAggregate;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Residence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResidenceWidgetDataService
{
    /**
     * Get aggregated widget data based on Filament table filters.
     *
     * @param  array  $filters
     */
    public static function getAllData(array $filters = []): array
    {
        // For unfiltered requests, try the pre-aggregated cache first (instant)
        if (empty(static::flattenFilters($filters))) {
            $cached = WidgetAggregate::getCached('residence_global_stats', 300);
            if ($cached) {
                return $cached;
            }
        }

        // For filtered requests, use short-lived runtime cache
        $cacheKey = 'rsv_widget_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($filters) {
            return static::computeFromReadModel($filters);
        });
    }

    /**
     * Compute stats from the denormalized read model (fast).
     */
    protected static function computeFromReadModel(array $filters): array
    {
        $baseQuery = DB::table('residence_stats_view');
        static::applyReadModelFilters($baseQuery, $filters);

        $total = (clone $baseQuery)->count();

        $moobanTypes = (clone $baseQuery)
            ->selectRaw('mooban_type, COUNT(*) as total')
            ->groupBy('mooban_type')
            ->pluck('total', 'mooban_type')
            ->toArray();

        $subTypes = (clone $baseQuery)
            ->selectRaw('sub_type, COUNT(*) as total')
            ->groupBy('sub_type')
            ->pluck('total', 'sub_type')
            ->toArray();

        $statusCounts = (clone $baseQuery)
            ->selectRaw('activation_status_name as status, COUNT(*) as total')
            ->whereNotNull('activation_status_name')
            ->groupBy('activation_status_name')
            ->pluck('total', 'status')
            ->toArray();

        $laneCounts = (clone $baseQuery)
            ->selectRaw('guard_house_lane_type, COUNT(*) as total')
            ->groupBy('guard_house_lane_type')
            ->pluck('total', 'guard_house_lane_type')
            ->toArray();

        $entryCounts = (clone $baseQuery)
            ->selectRaw('guard_house_entry_number, COUNT(*) as total')
            ->groupBy('guard_house_entry_number')
            ->pluck('total', 'guard_house_entry_number')
            ->toArray();

        $currentYear = now()->year;
        $rawAgeCounts = (clone $baseQuery)
            ->whereNotNull('completion_year')
            ->selectRaw("
                CASE
                    WHEN completion_year >= ? THEN '1-5 years'
                    WHEN completion_year >= ? THEN '6-10 years'
                    WHEN completion_year >= ? THEN '11-15 years'
                    WHEN completion_year >= ? THEN '16-20 years'
                    WHEN completion_year >= ? THEN '21-25 years'
                    WHEN completion_year >= ? THEN '26-30 years'
                    WHEN completion_year >= ? THEN '31-35 years'
                    WHEN completion_year >= ? THEN '36-40 years'
                    WHEN completion_year >= ? THEN '41-45 years'
                    WHEN completion_year >= ? THEN '46-50 years'
                    ELSE '50+ years'
                END AS age_bucket,
                COUNT(*) AS total
            ", [
                $currentYear - 5, $currentYear - 10, $currentYear - 15,
                $currentYear - 20, $currentYear - 25, $currentYear - 30,
                $currentYear - 35, $currentYear - 40, $currentYear - 45,
                $currentYear - 50,
            ])
            ->groupBy('age_bucket')
            ->pluck('total', 'age_bucket')
            ->toArray();

        $ageBucketLabels = [
            '1-5 years', '6-10 years', '11-15 years', '16-20 years',
            '21-25 years', '26-30 years', '31-35 years', '36-40 years',
            '41-45 years', '46-50 years', '50+ years',
        ];
        $ageCounts = [];
        foreach ($ageBucketLabels as $label) {
            $ageCounts[$label] = (int) ($rawAgeCounts[$label] ?? 0);
        }

        $creationTrend = DB::table('residences')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [
                Carbon::now()->subDays(29)->startOfDay(),
                Carbon::now()->endOfDay(),
            ])
            ->whereNull('deleted_at')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        return [
            'total' => $total,
            'mooban_types' => $moobanTypes,
            'sub_types' => $subTypes,
            'activation_status' => $statusCounts,
            'lane_types' => $laneCounts,
            'entry_numbers' => $entryCounts,
            'age_buckets' => $ageCounts,
            'creation_trend' => $creationTrend,
        ];
    }

    /**
     * Apply Filament table filters to the read model query.
     */
    protected static function applyReadModelFilters($query, array $filters): void
    {
        if (! empty($filters['mooban']['mooban_type'] ?? null)) {
            $query->whereIn('mooban_type', (array) $filters['mooban']['mooban_type']);
        }

        if (! empty($filters['mooban']['sub_type'] ?? null)) {
            $query->whereIn('sub_type', (array) $filters['mooban']['sub_type']);
        }

        if (! empty($filters['mooban']['name'] ?? null)) {
            $query->where('residence_id', $filters['mooban']['name']);
        }

        // Location filters - direct column lookups, no WHERE EXISTS subqueries
        if (! empty($filters['province_filters']['province'] ?? null)) {
            $query->whereIn('province_id', (array) $filters['province_filters']['province']);
        }

        if (! empty($filters['province_filters']['district'] ?? null)) {
            $query->whereIn('district_id', (array) $filters['province_filters']['district']);
        }

        if (! empty($filters['province_filters']['subdistrict'] ?? null)) {
            $query->whereIn('subdistrict_id', (array) $filters['province_filters']['subdistrict']);
        }

        if (! empty($filters['province_filters']['main_road'] ?? null)) {
            $query->where('main_road', 'LIKE', '%' . $filters['province_filters']['main_road'] . '%');
        }

        if (! empty($filters['status_filters']['residence_activation_status_id'] ?? null)) {
            $query->whereIn('residence_activation_status_id', (array) $filters['status_filters']['residence_activation_status_id']);
        }

        if (! empty($filters['date_filters']['created_from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['date_filters']['created_from']);
        }
        if (! empty($filters['date_filters']['created_until'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['date_filters']['created_until']);
        }
        if (! empty($filters['date_filters']['updated_from'] ?? null)) {
            $query->whereDate('updated_at', '>=', $filters['date_filters']['updated_from']);
        }
        if (! empty($filters['date_filters']['updated_until'] ?? null)) {
            $query->whereDate('updated_at', '<=', $filters['date_filters']['updated_until']);
        }

        if (! empty($filters['companies']['developer_id'] ?? null)) {
            $query->where('developer_id', $filters['companies']['developer_id']);
        }
        if (! empty($filters['companies']['property_management_id'] ?? null)) {
            $query->where('property_management_id', $filters['companies']['property_management_id']);
        }
        if (! empty($filters['companies']['sgoc_company_id'] ?? null)) {
            $query->where('sgoc_company_id', $filters['companies']['sgoc_company_id']);
        }

        if (! empty($filters['residence_infrastructure_filters']['residence_age_category'] ?? null)) {
            $value = $filters['residence_infrastructure_filters']['residence_age_category'];
            $currentYear = now()->year;

            if ($value === 'Not Yet Set') {
                $query->whereNull('completion_year');
            } else {
                [$min, $max] = match ($value) {
                    '1-5' => [1, 5], '6-10' => [6, 10], '11-15' => [11, 15],
                    '16-20' => [16, 20], '21-25' => [21, 25], '26-30' => [26, 30],
                    '31-35' => [31, 35], '36-40' => [36, 40], '41-45' => [41, 45],
                    '46-50' => [46, 50], '50+' => [51, PHP_INT_MAX],
                    default => [0, PHP_INT_MAX],
                };

                $query->where('completion_year', '<=', $currentYear - $min);
                if ($min !== 51) {
                    $query->where('completion_year', '>', $currentYear - $max);
                }
            }
        }

        if (isset($filters['residence_infrastructure_filters']['guard_house_entry_number'])) {
            $val = $filters['residence_infrastructure_filters']['guard_house_entry_number'];
            $val === 'Not Yet Set' ? $query->whereNull('guard_house_entry_number') : $query->where('guard_house_entry_number', $val);
        }

        if (! empty($filters['residence_infrastructure_filters']['guard_house_lane_type'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['guard_house_lane_type'];
            $val === 'Not Yet Set' ? $query->whereNull('guard_house_lane_type') : $query->where('guard_house_lane_type', $val);
        }

        if (isset($filters['residence_infrastructure_filters']['guard_house_type'])) {
            $val = $filters['residence_infrastructure_filters']['guard_house_type'];
            $val === 'Not Yet Set' ? $query->whereNull('has_roof') : $query->where('has_roof', (bool) $val);
        }

        if (! empty($filters['residence_infrastructure_filters']['entrance_barrier_type'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['entrance_barrier_type'];
            $val === 'Not Yet Set' ? $query->whereNull('entrance_barrier_type') : $query->where('entrance_barrier_type', $val);
        }

        if (! empty($filters['residence_infrastructure_filters']['internet_provider'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['internet_provider'];
            $val === 'Not Yet Set' ? $query->whereNull('internet_provider_id') : $query->whereJsonContains('internet_provider_id', (string) $val);
        }

        if (isset($filters['residence_infrastructure_filters']['has_cctv'])) {
            $query->where('has_cctv', filter_var($filters['residence_infrastructure_filters']['has_cctv'], FILTER_VALIDATE_BOOLEAN));
        }
    }

    /**
     * Flatten filters to check if any are actually set.
     */
    protected static function flattenFilters(array $filters): array
    {
        $flat = [];
        array_walk_recursive($filters, function ($value) use (&$flat) {
            if ($value !== null && $value !== '' && $value !== [] && $value !== false) {
                $flat[] = $value;
            }
        });
        return $flat;
    }

    /**
     * Legacy: Apply Filament table filters to an Eloquent Residence query.
     * Keep for backward compatibility with any code still using Residence model directly.
     */
    public static function applyFilters($query, array $filters): void
    {
        if (! empty($filters['mooban']['mooban_type'] ?? null)) {
            $query->whereIn('mooban_type', (array) $filters['mooban']['mooban_type']);
        }

        if (! empty($filters['mooban']['sub_type'] ?? null)) {
            $query->whereIn('sub_type', (array) $filters['mooban']['sub_type']);
        }

        if (! empty($filters['mooban']['name'] ?? null)) {
            $query->where('residences.id', $filters['mooban']['name']);
        }

        if (! empty($filters['province_filters']['province'] ?? null)) {
            $provinceIds = (array) $filters['province_filters']['province'];
            $query->whereHas('subdistrict.district.province', fn ($q) => $q->whereIn('id', $provinceIds));
        }

        if (! empty($filters['province_filters']['district'] ?? null)) {
            $districtIds = (array) $filters['province_filters']['district'];
            $query->whereHas('subdistrict.district', fn ($q) => $q->whereIn('id', $districtIds));
        }

        if (! empty($filters['province_filters']['subdistrict'] ?? null)) {
            $subdistrictIds = (array) $filters['province_filters']['subdistrict'];
            $query->whereHas('subdistrict', fn ($q) => $q->whereIn('id', $subdistrictIds));
        }

        if (! empty($filters['province_filters']['main_road'] ?? null)) {
            $query->where('main_road', 'LIKE', '%' . $filters['province_filters']['main_road'] . '%');
        }

        if (! empty($filters['status_filters']['residence_activation_status_id'] ?? null)) {
            $query->whereIn('residence_activation_status_id', (array) $filters['status_filters']['residence_activation_status_id']);
        }

        if (! empty($filters['date_filters']['created_from'] ?? null)) {
            $query->whereDate('residences.created_at', '>=', $filters['date_filters']['created_from']);
        }
        if (! empty($filters['date_filters']['created_until'] ?? null)) {
            $query->whereDate('residences.created_at', '<=', $filters['date_filters']['created_until']);
        }
        if (! empty($filters['date_filters']['updated_from'] ?? null)) {
            $query->whereDate('residences.updated_at', '>=', $filters['date_filters']['updated_from']);
        }
        if (! empty($filters['date_filters']['updated_until'] ?? null)) {
            $query->whereDate('residences.updated_at', '<=', $filters['date_filters']['updated_until']);
        }

        if (! empty($filters['companies']['developer_id'] ?? null)) {
            $query->where('developer_id', $filters['companies']['developer_id']);
        }
        if (! empty($filters['companies']['property_management_id'] ?? null)) {
            $query->where('property_management_id', $filters['companies']['property_management_id']);
        }
        if (! empty($filters['companies']['sgoc_company_id'] ?? null)) {
            $query->where('sgoc_company_id', $filters['companies']['sgoc_company_id']);
        }

        if (! empty($filters['residence_infrastructure_filters']['residence_age_category'] ?? null)) {
            $value = $filters['residence_infrastructure_filters']['residence_age_category'];
            $currentYear = now()->year;

            if ($value === 'Not Yet Set') {
                $query->whereNull('completion_year');
            } else {
                [$min, $max] = match ($value) {
                    '1-5' => [1, 5], '6-10' => [6, 10], '11-15' => [11, 15],
                    '16-20' => [16, 20], '21-25' => [21, 25], '26-30' => [26, 30],
                    '31-35' => [31, 35], '36-40' => [36, 40], '41-45' => [41, 45],
                    '46-50' => [46, 50], '50+' => [51, PHP_INT_MAX],
                    default => [0, PHP_INT_MAX],
                };

                $query->where('completion_year', '<=', $currentYear - $min);
                if ($min !== 51) {
                    $query->where('completion_year', '>', $currentYear - $max);
                }
            }
        }

        if (isset($filters['residence_infrastructure_filters']['guard_house_entry_number'])) {
            $val = $filters['residence_infrastructure_filters']['guard_house_entry_number'];
            $val === 'Not Yet Set' ? $query->whereNull('guard_house_entry_number') : $query->where('guard_house_entry_number', $val);
        }

        if (! empty($filters['residence_infrastructure_filters']['guard_house_lane_type'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['guard_house_lane_type'];
            $val === 'Not Yet Set' ? $query->whereNull('guard_house_lane_type') : $query->where('guard_house_lane_type', $val);
        }

        if (isset($filters['residence_infrastructure_filters']['guard_house_type'])) {
            $val = $filters['residence_infrastructure_filters']['guard_house_type'];
            $val === 'Not Yet Set' ? $query->whereNull('has_roof') : $query->where('has_roof', (bool) $val);
        }

        if (! empty($filters['residence_infrastructure_filters']['entrance_barrier_type'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['entrance_barrier_type'];
            $val === 'Not Yet Set' ? $query->whereNull('entrance_barrier_type') : $query->where('entrance_barrier_type', $val);
        }

        if (! empty($filters['residence_infrastructure_filters']['internet_provider'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['internet_provider'];
            $val === 'Not Yet Set' ? $query->whereNull('internet_provider') : $query->whereJsonContains('internet_provider_id', (string) $val);
        }

        if (isset($filters['residence_infrastructure_filters']['has_cctv'])) {
            $query->where('has_cctv', filter_var($filters['residence_infrastructure_filters']['has_cctv'], FILTER_VALIDATE_BOOLEAN));
        }
    }
}
