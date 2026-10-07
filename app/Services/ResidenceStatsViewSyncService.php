<?php

namespace App\Services;

use App\Enums\Residence\MoobanType;
use App\Models\Residence;
use App\Models\ResidenceStatsView;
use App\Models\WidgetAggregate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResidenceStatsViewSyncService
{
    /** Value ERP stores in invoices.projectable_type for residence invoices. */
    private const ERP_RESIDENCE_TYPE = 'App\Models\Mmb\Residence';

    /**
     * Sync a single residence to the read model.
     * Called from observer after create/update.
     */
    public static function syncOne(int $residenceId): void
    {
        $rows = static::buildRows([$residenceId]);

        if ($rows->isEmpty()) {
            ResidenceStatsView::query()
                ->whereIn('residence_id', [$residenceId], 'and', false)
                ->delete();

            return;
        }

        ResidenceStatsView::upsert(
            $rows->toArray(),
            ['residence_id'],
            array_keys($rows->first())
        );
    }

    /**
     * Full sync of all residences. Runs in chunks for memory efficiency.
     */
    public static function syncAll(int $chunkSize = 500): int
    {
        $total = 0;
        $processedIds = [];

        Residence::query()
            ->select('id')
            ->whereNull('deleted_at', 'and', false)
            ->orderBy('id', 'asc')
            ->chunk($chunkSize, function (Collection $chunk) use (&$total, &$processedIds) {
                $ids = $chunk->pluck('id')->toArray();
                $rows = static::buildRows($ids);

                if ($rows->isNotEmpty()) {
                    ResidenceStatsView::upsert(
                        $rows->toArray(),
                        ['residence_id'],
                        array_keys($rows->first())
                    );
                }

                $processedIds = array_merge($processedIds, $ids);
                $total += count($ids);
            });

        // Remove deleted residences from view
        if (! empty($processedIds)) {
            ResidenceStatsView::query()
                ->whereNotIn('residence_id', $processedIds, 'and')
                ->delete();
        }

        return $total;
    }

    /**
     * Highest ERP invoice service duration per residence for invoices dated in the given month.
     *
     * @param  array<int, int>  $residenceIds
     * @return Collection<int, string> Service duration keyed by residence id.
     */
    public static function currentMonthServiceDurations(array $residenceIds, Carbon $month): Collection
    {
        // ERP links invoices to residences polymorphically; it has no residence_id column.
        return DB::table(config('database.connections.mmbcnerp.database').'.invoices')
            ->where('projectable_type', self::ERP_RESIDENCE_TYPE)
            ->whereIn('projectable_id', $residenceIds)
            ->whereNull('deleted_at')
            ->whereBetween('invoice_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->select('projectable_id', DB::raw('MAX(service_duration) as service_duration_latest'))
            ->groupBy('projectable_id')
            ->pluck('service_duration_latest', 'projectable_id');
    }

    /**
     * Build denormalized rows for given residence IDs.
     */
    protected static function buildRows(array $residenceIds): Collection
    {
        // Main residence data with location joins
        $residences = DB::table('residences as r')
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.thailand_sub_districts as sd',
                'r.subdistrict_id',
                '=',
                'sd.id'
            )
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.thailand_districts as d',
                'sd.district_id',
                '=',
                'd.id'
            )
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.thailand_provinces as p',
                'd.province_id',
                '=',
                'p.id'
            )
            ->leftJoin(
                'residence_activation_statuses as ras',
                'r.residence_activation_status_id',
                '=',
                'ras.id'
            )

            // Developer
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.cdp_companies as dev',
                'r.developer_id',
                '=',
                'dev.id'
            )
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.business_entities as dev_be',
                'dev.business_entity_id',
                '=',
                'dev_be.id'
            )

            // Property Management Company
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.cdp_companies as pm',
                'r.property_management_id',
                '=',
                'pm.id'
            )
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.business_entities as pm_be',
                'pm.business_entity_id',
                '=',
                'pm_be.id'
            )

            // Property Management User
            ->leftJoin(
                'users as pmu',
                'r.property_management_user_id',
                '=',
                'pmu.id'
            )

            // SGOC Company
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.cdp_companies as sgoc',
                'r.sgoc_company_id',
                '=',
                'sgoc.id'
            )
            ->leftJoin(
                config('database.connections.mmbcnerp.database') . '.business_entities as sgoc_be',
                'sgoc.business_entity_id',
                '=',
                'sgoc_be.id'
            )

            ->whereIn('r.id', $residenceIds)
            ->whereNull('r.deleted_at')
            ->select([
                'r.id as residence_id',
                'r.name',
                'r.name_th',
                'r.mooban_type',
                'r.sub_type',

                'r.residence_activation_status_id',
                'ras.status as activation_status_name',

                'r.property_management_type',

                'r.subdistrict_id',
                'd.id as district_id',
                'p.id as province_id',

                'p.name_in_english as province_name_en',
                'p.name_in_thai as province_name_th',

                'd.name_in_english as district_name_en',
                'd.name_in_thai as district_name_th',

                'sd.name_in_english as subdistrict_name_en',
                'sd.name_in_thai as subdistrict_name_th',

                'r.main_road',
                'r.full_address',

                // Developer
                'r.developer_id',
                'dev_be.name as developer_name',
                'dev_be.name_th as developer_name_th',

                // PM Company
                'r.property_management_id',
                'pm_be.name as pm_company_name',
                'pm_be.name_th as pm_company_name_th',

                // PM User
                'r.property_management_user_id',
                'pmu.name as pm_user_name',

                // SGOC
                'r.sgoc_company_id',
                'sgoc_be.name as sgoc_company_name',
                'sgoc_be.name_th as sgoc_company_name_th',

                'r.completion_year',
                'r.guard_house_entry_number',
                'r.guard_house_lane_type',
                'r.has_roof',
                'r.entrance_barrier_type',
                'r.internet_provider_id',
                'r.has_cctv',
                'r.cctv_count',
                'r.security_guard_count',
                'r.subscription_start_date',
                'r.subscription_end_date',
                'r.juristic_details',
                'r.bpo_software_suppliers',
                'r.person_in_charges',

                'r.created_at as original_created_at',
                'r.updated_at as original_updated_at',
            ])
            ->get()
            ->keyBy('residence_id');

        if ($residences->isEmpty()) {
            return collect();
        }

        // Pre-aggregate unit counts per residence
        $unitCounts = DB::table('units')
            ->whereIn('residence_id', $residenceIds)
            ->whereNull('deleted_at')
            ->select('residence_id', DB::raw('COUNT(*) as units_count'))
            ->groupBy('residence_id')
            ->pluck('units_count', 'residence_id');

        // Pre-aggregate distinct user counts per residence
        $userCounts = DB::table('units')
            ->join('unit_user', 'unit_user.unit_id', '=', 'units.id')
            ->whereIn('units.residence_id', $residenceIds)
            ->whereNull('units.deleted_at')
            ->whereNull('unit_user.deleted_at')
            ->select('units.residence_id', DB::raw('COUNT(DISTINCT unit_user.user_id) as distinct_user_count'))
            ->groupBy('units.residence_id')
            ->pluck('distinct_user_count', 'residence_id');

        $now = now();
        $invoiceDurations = static::currentMonthServiceDurations($residenceIds, $now);

        return $residences->map(function ($r) use ($unitCounts, $userCounts, $invoiceDurations, $now) {
            $unitsCount = $unitCounts[$r->residence_id] ?? 0;
            $distinctUsers = $userCounts[$r->residence_id] ?? 0;
            $signUpPct = $unitsCount > 0
                ? round(($distinctUsers / $unitsCount) * 100, 2)
                : 0;

            $searchParts = array_filter([
                $r->name,
                $r->name_th,
                $r->province_name_en,
                $r->province_name_th,
                $r->district_name_en,
                $r->district_name_th,
                $r->pm_company_name,
                $r->pm_company_name_th,
                $r->developer_name,
                $r->developer_name_th,
                $r->sgoc_company_name,
                $r->sgoc_company_name_th,
                $r->pm_user_name,
                $r->main_road,
            ]);

            // Decode juristic details for search index
            $juristic = is_string($r->juristic_details) ? json_decode($r->juristic_details, true) : $r->juristic_details;
            if (is_array($juristic)) {
                $searchParts[] = $juristic['name'] ?? '';
                $searchParts[] = $juristic['phone_no'] ?? '';
            }

            return [
                'residence_id' => $r->residence_id,
                'name' => $r->name,
                'name_th' => $r->name_th,
                'mooban_type' => $r->mooban_type,
                'sub_type' => $r->sub_type,
                'residence_activation_status_id' => $r->residence_activation_status_id,
                'activation_status_name' => $r->activation_status_name,
                'property_management_type' => $r->property_management_type,
                'subdistrict_id' => $r->subdistrict_id,
                'district_id' => $r->district_id,
                'province_id' => $r->province_id,
                'province_name_en' => $r->province_name_en,
                'province_name_th' => $r->province_name_th,
                'district_name_en' => $r->district_name_en,
                'district_name_th' => $r->district_name_th,
                'subdistrict_name_en' => $r->subdistrict_name_en,
                'subdistrict_name_th' => $r->subdistrict_name_th,
                'main_road' => $r->main_road,
                'full_address' => $r->full_address,
                'developer_id' => $r->developer_id,
                'developer_name' => $r->developer_name,
                'property_management_id' => $r->property_management_id,
                'pm_company_name' => $r->pm_company_name,
                'property_management_user_id' => $r->property_management_user_id,
                'pm_user_name' => $r->pm_user_name,
                'sgoc_company_id' => $r->sgoc_company_id,
                'sgoc_company_name' => $r->sgoc_company_name,
                'units_count' => $unitsCount,
                'distinct_user_count' => $distinctUsers,
                'sign_up_percentage' => $signUpPct,
                'completion_year' => $r->completion_year,
                'guard_house_entry_number' => $r->guard_house_entry_number,
                'guard_house_lane_type' => $r->guard_house_lane_type,
                'has_roof' => $r->has_roof,
                'entrance_barrier_type' => $r->entrance_barrier_type,
                'internet_provider_id' => $r->internet_provider_id,
                'has_cctv' => $r->has_cctv ?? false,
                'cctv_count' => $r->cctv_count ?? 0,
                'security_guard_count' => $r->security_guard_count,
                'subscription_start_date' => $r->subscription_start_date,
                'subscription_end_date' => $r->subscription_end_date,
                'juristic_details' => $r->juristic_details,
                'bpo_software_suppliers' => $r->bpo_software_suppliers,
                'person_in_charges' => $r->person_in_charges,
                'service_duration_latest' => $invoiceDurations[$r->residence_id] ?? null,
                'search_index' => implode(' ', $searchParts),
                'synced_at' => $now,
                'updated_at' => $r->original_updated_at ?? $now,
                'created_at' => $r->original_created_at ?? $now,
            ];
        });
    }

    /**
     * Pre-compute and cache all widget aggregates.
     */
    public static function syncWidgetAggregates(): void
    {
        $startTime = microtime(true);

        try {
            // 1. Global residence stats (used by ListResidences widgets)
            static::syncGlobalResidenceStats();

            // 2. DDI stats by province/district (used by DistrictDashboard)
            static::syncDdiGlobalStats();

            // 3. DDI grid card data (replaces 70+ queries)
            static::syncDdiGridCardData();

            // 4. Market share data
            static::syncMarketShareData();

            $elapsed = round(microtime(true) - $startTime, 2);
            Log::info("Widget aggregates synced in {$elapsed}s");
        } catch (\Throwable $e) {
            Log::error('Widget aggregate sync failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Global stats used by ListResidences header widgets.
     */
    protected static function syncGlobalResidenceStats(): void
    {
        $currentYear = now()->year;

        // Total by mooban_type
        $moobanTypes = DB::table('residence_stats_view')
            ->selectRaw('mooban_type, COUNT(*) as total')
            ->groupBy('mooban_type')
            ->pluck('total', 'mooban_type')
            ->toArray();

        // Total by sub_type
        $subTypes = DB::table('residence_stats_view')
            ->selectRaw('sub_type, COUNT(*) as total')
            ->groupBy('sub_type')
            ->pluck('total', 'sub_type')
            ->toArray();

        // Activation status
        $statusCounts = DB::table('residence_stats_view')
            ->selectRaw('activation_status_name as status, COUNT(*) as total')
            ->whereNotNull('activation_status_name')
            ->groupBy('activation_status_name')
            ->pluck('total', 'status')
            ->toArray();

        // Lane types
        $laneCounts = DB::table('residence_stats_view')
            ->selectRaw('guard_house_lane_type, COUNT(*) as total')
            ->groupBy('guard_house_lane_type')
            ->pluck('total', 'guard_house_lane_type')
            ->toArray();

        // Entry numbers
        $entryCounts = DB::table('residence_stats_view')
            ->selectRaw('guard_house_entry_number, COUNT(*) as total')
            ->groupBy('guard_house_entry_number')
            ->pluck('total', 'guard_house_entry_number')
            ->toArray();

        // Age buckets
        $ageBuckets = DB::table('residence_stats_view')
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
                $currentYear - 5,
                $currentYear - 10,
                $currentYear - 15,
                $currentYear - 20,
                $currentYear - 25,
                $currentYear - 30,
                $currentYear - 35,
                $currentYear - 40,
                $currentYear - 45,
                $currentYear - 50,
            ])
            ->groupBy('age_bucket')
            ->pluck('total', 'age_bucket')
            ->toArray();

        // Creation trend (last 30 days)
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

        $total = array_sum($moobanTypes);

        WidgetAggregate::putCache('residence_global_stats', [
            'total' => $total,
            'mooban_types' => $moobanTypes,
            'sub_types' => $subTypes,
            'activation_status' => $statusCounts,
            'lane_types' => $laneCounts,
            'entry_numbers' => $entryCounts,
            'age_buckets' => $ageBuckets,
            'creation_trend' => $creationTrend,
        ]);
    }

    /**
     * DDI global stats (PropertyStatsOverview, TotalUnits, TotalResidents)
     */
    protected static function syncDdiGlobalStats(): void
    {
        $publicType = MoobanType::PUBLIC->value;

        // Sub type counts for public residences
        $subTypeCounts = DB::table('residence_stats_view')
            ->where('mooban_type', $publicType)
            ->selectRaw('sub_type, COUNT(*) as count')
            ->groupBy('sub_type')
            ->pluck('count', 'sub_type')
            ->toArray();

        // Total units and residents for public residences
        $totals = DB::table('residence_stats_view')
            ->where('mooban_type', $publicType)
            ->selectRaw('SUM(units_count) as total_units, SUM(distinct_user_count) as total_residents')
            ->first();

        WidgetAggregate::putCache('ddi_global_stats', [
            'sub_type_counts' => $subTypeCounts,
            'total_units' => (int) ($totals->total_units ?? 0),
            'total_residents' => (int) ($totals->total_residents ?? 0),
        ]);
    }

    /**
     * DDI grid card data - replaces ~70 COUNT queries with 1 batch query.
     */
    protected static function syncDdiGridCardData(): void
    {
        $publicType = MoobanType::PUBLIC->value;

        // All counts grouped by sub_type and activation_status_id
        $counts = DB::table('residence_stats_view')
            ->where('mooban_type', $publicType)
            ->selectRaw('sub_type, residence_activation_status_id, COUNT(*) as cnt')
            ->groupBy('sub_type', 'residence_activation_status_id')
            ->get()
            ->groupBy('sub_type')
            ->map(fn($items) => $items->pluck('cnt', 'residence_activation_status_id')->toArray())
            ->toArray();

        // Totals by sub_type
        $totals = DB::table('residence_stats_view')
            ->where('mooban_type', $publicType)
            ->selectRaw('sub_type, COUNT(*) as total')
            ->groupBy('sub_type')
            ->pluck('total', 'sub_type')
            ->toArray();

        WidgetAggregate::putCache('ddi_grid_card_data', [
            'counts_by_subtype_status' => $counts,
            'totals_by_subtype' => $totals,
        ]);

        // Also pre-compute per province
        $provinces = DB::table('residence_stats_view')
            ->where('mooban_type', $publicType)
            ->whereNotNull('province_id')
            ->select('province_id')
            ->distinct()
            ->pluck('province_id');

        foreach ($provinces as $provinceId) {
            $provCounts = DB::table('residence_stats_view')
                ->where('mooban_type', $publicType)
                ->where('province_id', $provinceId)
                ->selectRaw('sub_type, residence_activation_status_id, COUNT(*) as cnt')
                ->groupBy('sub_type', 'residence_activation_status_id')
                ->get()
                ->groupBy('sub_type')
                ->map(fn($items) => $items->pluck('cnt', 'residence_activation_status_id')->toArray())
                ->toArray();

            $provTotals = DB::table('residence_stats_view')
                ->where('mooban_type', $publicType)
                ->where('province_id', $provinceId)
                ->selectRaw('sub_type, COUNT(*) as total')
                ->groupBy('sub_type')
                ->pluck('total', 'sub_type')
                ->toArray();

            WidgetAggregate::putCache("ddi_grid_card_data_province_{$provinceId}", [
                'counts_by_subtype_status' => $provCounts,
                'totals_by_subtype' => $provTotals,
            ]);
        }
    }

    /**
     * Market share historical data.
     */
    protected static function syncMarketShareData(): void
    {
        // Pre-compute monthly active counts by sub_type (last 12 months)
        $publicType = MoobanType::PUBLIC->value;
        $activeStatuses = [3, 4, 5];

        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $endOfMonth = $month->endOfMonth();

            $counts = DB::table('residence_stats_view')
                ->where('mooban_type', $publicType)
                ->whereIn('residence_activation_status_id', $activeStatuses)
                ->selectRaw('sub_type, COUNT(*) as cnt')
                ->groupBy('sub_type')
                ->pluck('cnt', 'sub_type')
                ->toArray();

            $monthlyData[$monthKey] = $counts;
        }

        WidgetAggregate::putCache('ddi_market_share_monthly', $monthlyData);
    }
}
