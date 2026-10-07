<?php

namespace App\Services;

use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\VehicleType;
use App\Models\WidgetAggregate;
use App\Support\VisitorPurposeNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VmsAnalyticsQueryService
{
    private const TTL_AGGREGATE = 86400;

    private const TTL_LIVE = 60;

    public static function globalStats(
        ?string $from,
        ?string $until,
        ?int $residenceId = null
    ): array {
        if ($from === null && $until === null && $residenceId === null) {
            $global = WidgetAggregate::getCached('vms_global_stats', self::TTL_AGGREGATE, 'visitors') ?? [];
            $type = WidgetAggregate::getCached('vms_visitor_type_stats', self::TTL_AGGREGATE, 'visitors') ?? [];
            $vehicle = WidgetAggregate::getCached('vms_vehicle_type_stats', self::TTL_AGGREGATE, 'visitors') ?? [];

            return array_merge($global, $type, $vehicle);
        }

        [$from, $until] = self::normalizeRangeForLiveQuery($from, $until, $residenceId);

        if ($residenceId !== null) {
            return self::globalStatsLive($residenceId, $from, $until);
        }

        $cacheKey = 'vms:global:'.($from ?? '*').':'.($until ?? '*');

        return Cache::remember($cacheKey, self::TTL_LIVE, function () use ($from, $until) {
            $row = DB::table('vms_analytics_daily')
                ->when($from, fn ($q) => $q->whereDate('summary_date', '>=', $from))
                ->when($until, fn ($q) => $q->whereDate('summary_date', '<=', $until))
                ->selectRaw('
                    SUM(visitors_in)        as visitors_in,
                    SUM(visitors_out)       as visitors_out,
                    SUM(visitors_remaining) as visitors_remaining,
                    SUM(visitors_overnight) as visitors_overnight,
                    SUM(drive_in)           as drive_in,
                    SUM(walk_in)            as walk_in,
                    SUM(prebook)            as prebook,
                    SUM(vehicle_car)        as vehicle_car,
                    SUM(vehicle_truck)      as vehicle_truck,
                    SUM(vehicle_motorbike)  as vehicle_motorbike,
                    SUM(vehicle_van)        as vehicle_van,
                    SUM(vehicle_taxi)       as vehicle_taxi,
                    SUM(vehicle_pickup)     as vehicle_pickup
                ')
                ->first();

            return self::mapGlobalStatsRow($row);
        });
    }

    private static function globalStatsLive(int $residenceId, ?string $from, ?string $until): array
    {
        $cacheKey = "vms:global:live:{$residenceId}:".($from ?? '*').':'.($until ?? '*');

        return Cache::remember($cacheKey, self::TTL_LIVE, function () use ($residenceId, $from, $until) {
            $row = DB::table('visitor_logs')
                ->where('residence_id', $residenceId)
                ->whereNull('deleted_at')
                ->when($from, fn ($q) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
                ->when($until, fn ($q) => $q->where('created_at', '<=', Carbon::parse($until)->endOfDay()))
                ->selectRaw('
                    COUNT(*) as visitors_in,
                    SUM(CASE WHEN arrival_time IS NOT NULL AND leave_time IS NOT NULL THEN 1 ELSE 0 END) as visitors_out,
                    SUM(CASE WHEN arrival_time IS NOT NULL AND leave_time IS NULL THEN 1 ELSE 0 END) as visitors_remaining,
                    SUM(CASE WHEN arrival_time IS NOT NULL AND (
                        (leave_time IS NULL AND arrival_time < ?)
                        OR (leave_time IS NOT NULL AND DATE(leave_time) > DATE(arrival_time))
                    ) THEN 1 ELSE 0 END) as visitors_overnight,
                    SUM(CASE WHEN is_pre_register = 0 AND arrival_type = ? THEN 1 ELSE 0 END) as drive_in,
                    SUM(CASE WHEN is_pre_register = 0 AND arrival_type = ? THEN 1 ELSE 0 END) as walk_in,
                    SUM(CASE WHEN is_pre_register = 1 THEN 1 ELSE 0 END) as prebook,
                    SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_car,
                    SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_truck,
                    SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_motorbike,
                    SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_van,
                    SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_taxi,
                    SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_pickup
                ', [
                    today(),
                    ArrivalType::DRIVE_IN->value,
                    ArrivalType::WALK_IN->value,
                    VehicleType::CAR->value,
                    VehicleType::TRUCK->value,
                    VehicleType::MOTORBIKE->value,
                    VehicleType::VAN->value,
                    VehicleType::TAXI->value,
                    VehicleType::PICKUP->value,
                ])
                ->first();

            return self::mapGlobalStatsRow($row);
        });
    }

    private static function mapGlobalStatsRow(?object $row): array
    {
        return [
            'visitors_in' => (int) ($row->visitors_in ?? 0),
            'visitors_out' => (int) ($row->visitors_out ?? 0),
            'visitors_remaining' => (int) ($row->visitors_remaining ?? 0),
            'visitors_overnight' => (int) ($row->visitors_overnight ?? 0),
            'drive_in' => (int) ($row->drive_in ?? 0),
            'walk_in' => (int) ($row->walk_in ?? 0),
            'prebook' => (int) ($row->prebook ?? 0),
            'vehicle_car' => (int) ($row->vehicle_car ?? 0),
            'vehicle_truck' => (int) ($row->vehicle_truck ?? 0),
            'vehicle_motorbike' => (int) ($row->vehicle_motorbike ?? 0),
            'vehicle_van' => (int) ($row->vehicle_van ?? 0),
            'vehicle_taxi' => (int) ($row->vehicle_taxi ?? 0),
            'vehicle_pickup' => (int) ($row->vehicle_pickup ?? 0),
        ];
    }

    public static function purposeBreakdown(
        ?string $from,
        ?string $until,
        ?int $residenceId = null
    ): array {
        if ($from === null && $until === null && $residenceId === null) {
            $data = WidgetAggregate::getCached('vms_purpose_stats', self::TTL_AGGREGATE, 'visitors') ?? [];

            return self::normalizePurposeBuckets($data);
        }

        [$from, $until] = self::normalizeRangeForLiveQuery($from, $until, $residenceId);

        if ($residenceId !== null) {
            return self::normalizePurposeBuckets(self::purposeBreakdownLive($residenceId, $from, $until));
        }

        $data = self::aggregateJsonColumn('purpose_breakdown', 'purpose', $from, $until);

        return self::normalizePurposeBuckets($data);
    }

    private static function purposeBreakdownLive(int $residenceId, ?string $from, ?string $until): array
    {
        $cacheKey = "vms:purpose:live:{$residenceId}:".($from ?? '*').':'.($until ?? '*');

        return Cache::remember($cacheKey, self::TTL_LIVE, function () use ($residenceId, $from, $until) {
            return DB::table('visitor_logs')
                ->where('residence_id', $residenceId)
                ->whereNull('deleted_at')
                ->whereNotNull('visitor_purpose')
                ->where('visitor_purpose', '<>', '')
                ->when($from, fn ($q) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
                ->when($until, fn ($q) => $q->where('created_at', '<=', Carbon::parse($until)->endOfDay()))
                ->selectRaw('visitor_purpose, COUNT(*) as total')
                ->groupBy('visitor_purpose')
                ->pluck('total', 'visitor_purpose')
                ->map(fn ($v) => (int) $v)
                ->all();
        });
    }

    public static function parcelCourierBreakdown(?string $from, ?string $until): array
    {
        if ($from === null && $until === null) {
            $data = WidgetAggregate::getCached('vms_parcel_courier_stats', self::TTL_AGGREGATE, 'visitors') ?? [];
            arsort($data);

            return $data;
        }

        return self::aggregateJsonColumn('parcel_courier_breakdown', 'partner', $from, $until);
    }

    public static function foodDeliveryBreakdown(?string $from, ?string $until): array
    {
        if ($from === null && $until === null) {
            $data = WidgetAggregate::getCached('vms_food_delivery_stats', self::TTL_AGGREGATE, 'visitors') ?? [];
            arsort($data);

            return $data;
        }

        return self::aggregateJsonColumn('food_delivery_breakdown', 'partner', $from, $until);
    }

    public static function summaryTrend(
        ?string $from,
        ?string $until,
        ?int $residenceId = null
    ): array {
        if ($from === null && $until === null && $residenceId === null) {
            return WidgetAggregate::getCached('vms_summary_trend', self::TTL_AGGREGATE, 'visitors') ?? [];
        }

        [$from, $until] = self::normalizeRangeForLiveQuery($from, $until, $residenceId);

        if ($residenceId !== null) {
            return self::summaryTrendLiveRange($residenceId, $from, $until);
        }

        $cacheKey = 'vms:trend:'.($from ?? '*').':'.($until ?? '*');

        return Cache::remember($cacheKey, self::TTL_LIVE, function () use ($from, $until) {
            return DB::table('vms_analytics_daily')
                ->when($from, fn ($q) => $q->whereDate('summary_date', '>=', $from))
                ->when($until, fn ($q) => $q->whereDate('summary_date', '<=', $until))
                ->selectRaw('summary_date, SUM(visitors_in) as total')
                ->groupBy('summary_date')
                ->orderBy('summary_date')
                ->pluck('total', 'summary_date')
                ->map(fn ($v) => (int) $v)
                ->all();
        });
    }

    private static function summaryTrendLiveRange(int $residenceId, ?string $from, ?string $until): array
    {
        $cacheKey = "vms:trend:live:{$residenceId}:".($from ?? '*').':'.($until ?? '*');

        return Cache::remember($cacheKey, self::TTL_LIVE, function () use ($residenceId, $from, $until) {
            return DB::table('visitor_logs')
                ->where('residence_id', $residenceId)
                ->whereNull('deleted_at')
                ->when($from, fn ($q) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
                ->when($until, fn ($q) => $q->where('created_at', '<=', Carbon::parse($until)->endOfDay()))
                ->selectRaw('DATE(created_at) as bucket, COUNT(*) as total')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->pluck('total', 'bucket')
                ->map(fn ($v) => (int) $v)
                ->all();
        });
    }

    public static function summaryTrendLive(int $residenceId, string $period): array
    {
        $cacheKey = "vms:trend_live:{$residenceId}:{$period}:".now()->format($period === 'minute' ? 'YmdHi' : 'YmdH');

        return Cache::remember($cacheKey, self::TTL_LIVE, function () use ($residenceId, $period) {
            return $period === 'minute'
                ? self::trendByMinute($residenceId)
                : self::trendByHour($residenceId);
        });
    }

    public static function isArchiveRange(?string $from, ?string $until = null): bool
    {
        if ($from === null && $until === null) {
            return false;
        }

        $rangeEnd = Carbon::parse($until ?? $from)->endOfDay();

        $oldestLiveRaw = Cache::remember('vms:visitor_logs:oldest_created_at', 300, function () {
            return DB::table('visitor_logs')->min('created_at');
        });

        if ($oldestLiveRaw) {
            $oldestLive = Carbon::parse($oldestLiveRaw)->startOfDay();

            return $rangeEnd->lt($oldestLive);
        }

        $liveDays = (int) config('partitioning.visitor_logs.live_days', 30);

        return $rangeEnd->lt(now()->subDays($liveDays)->startOfDay());
    }

    private static function normalizeRangeForLiveQuery(?string $from, ?string $until, ?int $residenceId): array
    {
        if ($from !== null || $until !== null) {
            return [$from, $until];
        }

        if ($residenceId === null) {
            return [null, null];
        }

        $now = now();

        return [
            $now->copy()->startOfMonth()->toDateString(),
            $now->toDateString(),
        ];
    }

    private static function aggregateJsonColumn(
        string $column,
        string $nameKey,
        ?string $from,
        ?string $until
    ): array {
        $cacheKey = 'vms:'.$column.':'.($from ?? '*').':'.($until ?? '*');

        return Cache::remember($cacheKey, self::TTL_LIVE, function () use ($column, $nameKey, $from, $until) {
            $rows = DB::table('vms_analytics_daily')
                ->when($from, fn ($q) => $q->whereDate('summary_date', '>=', $from))
                ->when($until, fn ($q) => $q->whereDate('summary_date', '<=', $until))
                ->whereNotNull($column)
                ->pluck($column);

            $merged = [];

            foreach ($rows as $json) {
                $breakdown = is_string($json) ? (json_decode($json, true) ?? []) : (array) $json;

                foreach ($breakdown as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $name = $item[$nameKey] ?? 'Unknown';
                    $count = (int) ($item['count'] ?? 0);
                    $merged[$name] = ($merged[$name] ?? 0) + $count;
                }
            }

            arsort($merged);

            return $merged;
        });
    }

    public static function normalizePurposeBuckets(array $data): array
    {
        $normalized = [];

        foreach ($data as $purpose => $count) {
            $label = VisitorPurposeNormalizer::normalize((string) $purpose) ?? 'Unknown';
            $normalized[$label] = ($normalized[$label] ?? 0) + (int) $count;
        }

        arsort($normalized);

        return $normalized;
    }

    private static function trendByHour(int $residenceId): array
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $result[sprintf('%02d:00', $h)] = 0;
        }

        $rows = DB::table('visitor_logs')
            ->where('residence_id', $residenceId)
            ->whereBetween('arrival_time', [$todayStart, $todayEnd])
            ->whereNull('deleted_at')
            ->selectRaw('HOUR(arrival_time) as hr, COUNT(*) as total')
            ->groupBy('hr')
            ->pluck('total', 'hr');

        foreach ($rows as $hr => $total) {
            $result[sprintf('%02d:00', $hr)] = (int) $total;
        }

        return $result;
    }

    private static function trendByMinute(int $residenceId): array
    {
        $from = Carbon::now()->subHour();
        $to = Carbon::now();

        $result = [];
        $cursor = $from->copy()->second(0)->microsecond(0);
        $cursor->minute(intdiv($cursor->minute, 5) * 5);
        $to5 = $to->copy()->second(0)->microsecond(0);
        $to5->minute(intdiv($to5->minute, 5) * 5);

        while ($cursor->lte($to5)) {
            $result[$cursor->format('H:i')] = 0;
            $cursor->addMinutes(5);
        }

        $rows = DB::table('visitor_logs')
            ->where('residence_id', $residenceId)
            ->whereBetween('arrival_time', [$from, $to])
            ->whereNull('deleted_at')
            ->selectRaw("DATE_FORMAT(arrival_time, '%H:%i') as bucket_raw, COUNT(*) as total")
            ->groupBy('bucket_raw')
            ->pluck('total', 'bucket_raw');

        foreach ($rows as $raw => $total) {
            [$hStr, $mStr] = explode(':', $raw);
            $bucket = sprintf('%02d:%02d', (int) $hStr, intdiv((int) $mStr, 5) * 5);

            if (array_key_exists($bucket, $result)) {
                $result[$bucket] += (int) $total;
            }
        }

        return $result;
    }
}
