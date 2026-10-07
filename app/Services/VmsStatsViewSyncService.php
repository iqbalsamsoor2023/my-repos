<?php

namespace App\Services;

use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\VehicleType;
use App\Models\VmsAnalyticsDaily;
use App\Models\WidgetAggregate;
use App\Support\VisitorPurposeNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VmsStatsViewSyncService
{
    private const MODULE = 'visitors';

    public static function syncRange(
        Carbon $from,
        Carbon $until,
        int $chunkSize = 500,
        ?callable $onProgress = null,
        int $sleepMs = 0
    ): int {
        $total = 0;
        $current = $from->copy()->startOfDay();
        $end = $until->copy()->endOfDay();

        while ($current->lte($end)) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            $dateString = $current->toDateString();

            $rows = static::buildRowsBulkForDay($dayStart, $dayEnd, $dateString);
            $dayTotal = 0;

            if (! empty($rows)) {
                foreach (array_chunk($rows, $chunkSize) as $batch) {
                    VmsAnalyticsDaily::upsert(
                        $batch,
                        ['summary_date', 'residence_id'],
                        array_keys($batch[0])
                    );
                    $dayTotal += count($batch);
                }
            }

            $total += $dayTotal;

            if ($onProgress !== null) {
                ($onProgress)($dateString, $dayTotal);
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            $current->addDay();
        }

        return $total;
    }

    protected static function buildRowsBulkForDay(Carbon $dayStart, Carbon $dayEnd, string $dateString): array
    {
        $now = now();

        static $locationCache = null;
        if ($locationCache === null) {
            $locationCache = DB::table('residences as r')
                ->leftJoin('mmbcnerp.thailand_sub_districts as sd', 'r.subdistrict_id', '=', 'sd.id')
                ->leftJoin('mmbcnerp.thailand_districts as d', 'sd.district_id', '=', 'd.id')
                ->select('r.id as residence_id', 'r.subdistrict_id', 'd.id as district_id', 'd.province_id')
                ->get()
                ->keyBy('residence_id');
        }

        static $archiveCutoff = false;
        if ($archiveCutoff === false) {
            $raw = DB::table('visitor_logs_archive')->max('arrival_time');
            $archiveCutoff = $raw ? Carbon::parse($raw)->startOfDay() : null;
        }
        $skipArchive = $archiveCutoff === null || $dayStart->gt($archiveCutoff);

        $empty = collect();

        $vehicleSelects = [];
        $vehicleBindings = [];
        foreach (VehicleType::cases() as $type) {
            $vehicleSelects[] = 'COUNT(CASE WHEN vehicle_type = ? THEN 1 END) as vehicle_'.strtolower($type->name);
            $vehicleBindings[] = $type->value;
        }

        $combinedBindings = array_merge(
            [$dayEnd, $dayEnd, ArrivalType::DRIVE_IN->value, ArrivalType::WALK_IN->value],
            $vehicleBindings
        );

        $combinedSelect = implode(',', array_merge([
            'residence_id',
            'COUNT(*) as visitors_in',
            'COUNT(CASE WHEN leave_time IS NOT NULL AND leave_time <= ? THEN 1 END) as visitors_out',
            'COUNT(CASE WHEN leave_time IS NULL OR leave_time > ? THEN 1 END) as visitors_remaining',
            'COUNT(CASE WHEN leave_time IS NULL OR DATE(leave_time) > DATE(arrival_time) THEN 1 END) as visitors_overnight',
            'COUNT(CASE WHEN is_pre_register = 1 THEN 1 END) as prebook',
            'COUNT(CASE WHEN is_pre_register = 0 AND arrival_type = ? THEN 1 END) as drive_in',
            'COUNT(CASE WHEN is_pre_register = 0 AND arrival_type = ? THEN 1 END) as walk_in',
        ], $vehicleSelects));

        $combinedQuery = fn ($table) => DB::table($table)
            ->whereBetween('arrival_time', [$dayStart, $dayEnd])
            ->whereNull('deleted_at')
            ->whereNotNull('residence_id')
            ->selectRaw($combinedSelect, $combinedBindings)
            ->groupBy('residence_id');

        $combinedLive = $combinedQuery('visitor_logs')->get()->keyBy('residence_id');
        $combinedArchive = $skipArchive ? $empty : $combinedQuery('visitor_logs_archive')->get()->keyBy('residence_id');

        $purposeQuery = fn ($table) => DB::table($table)
            ->whereBetween('arrival_time', [$dayStart, $dayEnd])
            ->whereNull('deleted_at')
            ->whereNotNull('residence_id')
            ->whereNotNull('visitor_purpose')
            ->where('visitor_purpose', '<>', '')
            ->selectRaw('residence_id, visitor_purpose as purpose, COUNT(*) as total')
            ->groupBy('residence_id', 'visitor_purpose');

        $purposeLive = $purposeQuery('visitor_logs')->get()->groupBy('residence_id');
        $purposeArchive = $skipArchive ? $empty : $purposeQuery('visitor_logs_archive')->get()->groupBy('residence_id');

        $parcelQuery = fn ($table) => DB::table($table)
            ->whereBetween($table.'.arrival_time', [$dayStart, $dayEnd])
            ->whereNull($table.'.deleted_at')
            ->whereNotNull($table.'.residence_id')
            ->whereNotNull($table.'.courier_logistic_partner_id')
            ->join('logistic_partners as lp', $table.'.courier_logistic_partner_id', '=', 'lp.id')
            ->selectRaw($table.'.residence_id, lp.name as partner, COUNT(*) as total')
            ->groupBy($table.'.residence_id', 'lp.name');

        $parcelLive = $parcelQuery('visitor_logs')->get()->groupBy('residence_id');
        $parcelArchive = $skipArchive ? $empty : $parcelQuery('visitor_logs_archive')->get()->groupBy('residence_id');

        $foodQuery = fn ($table) => DB::table($table)
            ->whereBetween($table.'.arrival_time', [$dayStart, $dayEnd])
            ->whereNull($table.'.deleted_at')
            ->whereNotNull($table.'.residence_id')
            ->whereNotNull($table.'.food_delivery_logistic_partner_id')
            ->join('logistic_partners as lp', $table.'.food_delivery_logistic_partner_id', '=', 'lp.id')
            ->selectRaw($table.'.residence_id, lp.name as partner, COUNT(*) as total')
            ->groupBy($table.'.residence_id', 'lp.name');

        $foodLive = $foodQuery('visitor_logs')->get()->groupBy('residence_id');
        $foodArchive = $skipArchive ? $empty : $foodQuery('visitor_logs_archive')->get()->groupBy('residence_id');

        $allResidenceIds = collect()
            ->merge($combinedLive->keys())
            ->merge($combinedArchive->keys())
            ->unique();

        $rows = [];
        foreach ($allResidenceIds as $rid) {
            $cL = $combinedLive->get($rid);
            $cA = $combinedArchive->get($rid);

            $loc = $locationCache->get($rid);

            $purposeData = [];
            foreach ([$purposeLive->get($rid, collect()), $purposeArchive->get($rid, collect())] as $purposeRows) {
                foreach ($purposeRows as $r) {
                    $normalized = VisitorPurposeNormalizer::normalize((string) $r->purpose);

                    if ($normalized === null) {
                        continue;
                    }

                    $purposeData[$normalized] = ($purposeData[$normalized] ?? 0) + (int) $r->total;
                }
            }
            arsort($purposeData);
            $purposeBreakdown = collect($purposeData)->map(fn ($count, $purpose) => ['purpose' => $purpose, 'count' => $count])->values()->toArray();

            $parcelData = [];
            foreach ([$parcelLive->get($rid, collect()), $parcelArchive->get($rid, collect())] as $parcelRows) {
                foreach ($parcelRows as $r) {
                    $parcelData[$r->partner] = ($parcelData[$r->partner] ?? 0) + (int) $r->total;
                }
            }
            arsort($parcelData);
            $parcelBreakdown = collect($parcelData)->map(fn ($count, $partner) => ['partner' => $partner, 'count' => $count])->values()->toArray();

            $foodData = [];
            foreach ([$foodLive->get($rid, collect()), $foodArchive->get($rid, collect())] as $foodRows) {
                foreach ($foodRows as $r) {
                    $foodData[$r->partner] = ($foodData[$r->partner] ?? 0) + (int) $r->total;
                }
            }
            arsort($foodData);
            $foodBreakdown = collect($foodData)->map(fn ($count, $partner) => ['partner' => $partner, 'count' => $count])->values()->toArray();

            $rows[] = [
                'summary_date' => $dateString,
                'residence_id' => $rid,
                'province_id' => $loc->province_id ?? null,
                'district_id' => $loc->district_id ?? null,
                'subdistrict_id' => $loc->subdistrict_id ?? null,
                'visitors_in' => (int) ($cL->visitors_in ?? 0) + (int) ($cA->visitors_in ?? 0),
                'visitors_out' => (int) ($cL->visitors_out ?? 0) + (int) ($cA->visitors_out ?? 0),
                'visitors_remaining' => (int) ($cL->visitors_remaining ?? 0) + (int) ($cA->visitors_remaining ?? 0),
                'visitors_overnight' => (int) ($cL->visitors_overnight ?? 0) + (int) ($cA->visitors_overnight ?? 0),
                'drive_in' => (int) ($cL->drive_in ?? 0) + (int) ($cA->drive_in ?? 0),
                'walk_in' => (int) ($cL->walk_in ?? 0) + (int) ($cA->walk_in ?? 0),
                'prebook' => (int) ($cL->prebook ?? 0) + (int) ($cA->prebook ?? 0),
                'vehicle_car' => (int) ($cL->vehicle_car ?? 0) + (int) ($cA->vehicle_car ?? 0),
                'vehicle_truck' => (int) ($cL->vehicle_truck ?? 0) + (int) ($cA->vehicle_truck ?? 0),
                'vehicle_motorbike' => (int) ($cL->vehicle_motorbike ?? 0) + (int) ($cA->vehicle_motorbike ?? 0),
                'vehicle_van' => (int) ($cL->vehicle_van ?? 0) + (int) ($cA->vehicle_van ?? 0),
                'vehicle_taxi' => (int) ($cL->vehicle_taxi ?? 0) + (int) ($cA->vehicle_taxi ?? 0),
                'vehicle_pickup' => (int) ($cL->vehicle_pickup ?? 0) + (int) ($cA->vehicle_pickup ?? 0),
                'purpose_breakdown' => json_encode($purposeBreakdown),
                'parcel_courier_breakdown' => json_encode($parcelBreakdown),
                'food_delivery_breakdown' => json_encode($foodBreakdown),
                'courier_count' => array_sum($parcelData),
                'food_delivery_count' => array_sum($foodData),
                'synced_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        return $rows;
    }

    public static function syncWidgetAggregates(): void
    {
        $startTime = microtime(true);

        try {
            $latestDate = DB::table('vms_analytics_daily')
                ->where('visitors_in', '>', 0)
                ->max('summary_date');

            if (! $latestDate) {
                Log::warning('VMS widget aggregates skipped: no data in vms_analytics_daily');

                return;
            }

            $latestDate = Carbon::parse($latestDate);
            $allTimeStart = DB::table('vms_analytics_daily')->min('summary_date') ?? $latestDate->toDateString();
            $allTimeEnd = $latestDate->toDateString();
            $last30Start = $latestDate->copy()->subDays(29)->toDateString();

            static::syncVmsGlobalStats($allTimeStart, $allTimeEnd, $latestDate);
            static::syncVmsVisitorTypeStats($allTimeStart, $allTimeEnd);
            static::syncVmsVehicleTypeStats($allTimeStart, $allTimeEnd);
            static::syncVmsPurposeStats($allTimeStart, $allTimeEnd);
            static::syncVmsParcelCourierStats($allTimeStart, $allTimeEnd);
            static::syncVmsFoodDeliveryStats($allTimeStart, $allTimeEnd);
            static::syncVmsSummaryTrend($last30Start, $allTimeEnd, $latestDate);

            $elapsed = round(microtime(true) - $startTime, 2);
            Log::info("VMS widget aggregates synced in {$elapsed}s (all-time start: {$allTimeStart}, latest: {$latestDate->toDateString()})");
        } catch (\Throwable $e) {
            Log::error('VMS widget aggregate sync failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected static function syncVmsGlobalStats(string $monthStart, string $monthEnd, Carbon $latestDate): void
    {
        $stats = DB::table('vms_analytics_daily')
            ->whereBetween('summary_date', [$monthStart, $monthEnd])
            ->selectRaw('
                COALESCE(SUM(visitors_in), 0) as total_in,
                COALESCE(SUM(visitors_out), 0) as total_out,
                COALESCE(SUM(visitors_overnight), 0) as total_overnight
            ')
            ->first();

        $latestRemaining = DB::table('vms_analytics_daily')
            ->where('summary_date', $latestDate->toDateString())
            ->sum('visitors_remaining');

        WidgetAggregate::putCache('vms_global_stats', [
            'visitors_in' => (int) ($stats->total_in ?? 0),
            'visitors_out' => (int) ($stats->total_out ?? 0),
            'visitors_remaining' => (int) ($latestRemaining ?? 0),
            'visitors_overnight' => (int) ($stats->total_overnight ?? 0),
        ], self::MODULE);
    }

    protected static function syncVmsVisitorTypeStats(string $monthStart, string $monthEnd): void
    {
        $stats = DB::table('vms_analytics_daily')
            ->whereBetween('summary_date', [$monthStart, $monthEnd])
            ->selectRaw('
                COALESCE(SUM(drive_in), 0) as drive_in,
                COALESCE(SUM(walk_in), 0) as walk_in,
                COALESCE(SUM(prebook), 0) as prebook
            ')
            ->first();

        WidgetAggregate::putCache('vms_visitor_type_stats', [
            'drive_in' => (int) ($stats->drive_in ?? 0),
            'walk_in' => (int) ($stats->walk_in ?? 0),
            'prebook' => (int) ($stats->prebook ?? 0),
        ], self::MODULE);
    }

    protected static function syncVmsVehicleTypeStats(string $monthStart, string $monthEnd): void
    {
        $stats = DB::table('vms_analytics_daily')
            ->whereBetween('summary_date', [$monthStart, $monthEnd])
            ->selectRaw('
                COALESCE(SUM(vehicle_car), 0) as vehicle_car,
                COALESCE(SUM(vehicle_truck), 0) as vehicle_truck,
                COALESCE(SUM(vehicle_motorbike), 0) as vehicle_motorbike,
                COALESCE(SUM(vehicle_van), 0) as vehicle_van,
                COALESCE(SUM(vehicle_taxi), 0) as vehicle_taxi,
                COALESCE(SUM(vehicle_pickup), 0) as vehicle_pickup
            ')
            ->first();

        WidgetAggregate::putCache('vms_vehicle_type_stats', [
            'vehicle_car' => (int) ($stats->vehicle_car ?? 0),
            'vehicle_truck' => (int) ($stats->vehicle_truck ?? 0),
            'vehicle_motorbike' => (int) ($stats->vehicle_motorbike ?? 0),
            'vehicle_van' => (int) ($stats->vehicle_van ?? 0),
            'vehicle_taxi' => (int) ($stats->vehicle_taxi ?? 0),
            'vehicle_pickup' => (int) ($stats->vehicle_pickup ?? 0),
        ], self::MODULE);
    }

    protected static function syncVmsPurposeStats(string $monthStart, string $monthEnd): void
    {
        $rows = DB::table('vms_analytics_daily')
            ->whereBetween('summary_date', [$monthStart, $monthEnd])
            ->whereNotNull('purpose_breakdown')
            ->pluck('purpose_breakdown');

        $purposeData = [];
        foreach ($rows as $json) {
            $breakdown = is_string($json) ? json_decode($json, true) : $json;
            if (is_array($breakdown)) {
                foreach ($breakdown as $item) {
                    $purpose = VisitorPurposeNormalizer::normalize($item['purpose'] ?? null) ?? 'Unknown';
                    $count = $item['count'] ?? 0;
                    $purposeData[$purpose] = ($purposeData[$purpose] ?? 0) + $count;
                }
            }
        }

        arsort($purposeData);

        WidgetAggregate::putCache('vms_purpose_stats', $purposeData, self::MODULE);
    }

    protected static function syncVmsParcelCourierStats(string $monthStart, string $monthEnd): void
    {
        $rows = DB::table('vms_analytics_daily')
            ->whereBetween('summary_date', [$monthStart, $monthEnd])
            ->whereNotNull('parcel_courier_breakdown')
            ->pluck('parcel_courier_breakdown');

        $courierData = [];
        foreach ($rows as $json) {
            $breakdown = is_string($json) ? json_decode($json, true) : $json;
            if (is_array($breakdown)) {
                foreach ($breakdown as $item) {
                    $partner = $item['partner'] ?? 'Unknown';
                    $count = $item['count'] ?? 0;
                    $courierData[$partner] = ($courierData[$partner] ?? 0) + $count;
                }
            }
        }

        arsort($courierData);

        WidgetAggregate::putCache('vms_parcel_courier_stats', $courierData, self::MODULE);
    }

    protected static function syncVmsFoodDeliveryStats(string $monthStart, string $monthEnd): void
    {
        $rows = DB::table('vms_analytics_daily')
            ->whereBetween('summary_date', [$monthStart, $monthEnd])
            ->whereNotNull('food_delivery_breakdown')
            ->pluck('food_delivery_breakdown');

        $foodData = [];
        foreach ($rows as $json) {
            $breakdown = is_string($json) ? json_decode($json, true) : $json;
            if (is_array($breakdown)) {
                foreach ($breakdown as $item) {
                    $partner = $item['partner'] ?? 'Unknown';
                    $count = $item['count'] ?? 0;
                    $foodData[$partner] = ($foodData[$partner] ?? 0) + $count;
                }
            }
        }

        arsort($foodData);

        WidgetAggregate::putCache('vms_food_delivery_stats', $foodData, self::MODULE);
    }

    protected static function syncVmsSummaryTrend(string $monthStart, string $monthEnd, Carbon $latestDate): void
    {
        $dailyTotals = DB::table('vms_analytics_daily')
            ->whereBetween('summary_date', [$monthStart, $monthEnd])
            ->selectRaw('summary_date, SUM(visitors_in) as total_in')
            ->groupBy('summary_date')
            ->orderBy('summary_date')
            ->pluck('total_in', 'summary_date')
            ->toArray();

        $weekEnd = $latestDate->toDateString();
        $weekStart = $latestDate->copy()->subDays(7)->toDateString();

        $weeklyTotals = DB::table('vms_analytics_daily')
            ->whereBetween('summary_date', [$weekStart, $weekEnd])
            ->selectRaw('summary_date, SUM(visitors_in) as total_in')
            ->groupBy('summary_date')
            ->orderBy('summary_date')
            ->pluck('total_in', 'summary_date')
            ->toArray();

        $yesterday = Carbon::yesterday()->toDateString();
        $yesterdayTotal = DB::table('vms_analytics_daily')
            ->where('summary_date', $yesterday)
            ->selectRaw('SUM(visitors_in) as total_in')
            ->value('total_in');

        WidgetAggregate::putCache('vms_summary_trend', [
            'month' => $dailyTotals,
            'week' => $weeklyTotals,
            'yesterday' => (int) ($yesterdayTotal ?? 0),
        ], self::MODULE);
    }
}
