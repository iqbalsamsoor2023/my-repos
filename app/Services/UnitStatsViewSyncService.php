<?php

namespace App\Services;

use App\Models\WidgetAggregate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnitStatsViewSyncService
{
    private const LIVE_TABLE = 'units_stats_view';
    private const UNIT_SYNC_BATCH_SIZE = 500;

    public static function syncOne(int $residenceId): void
    {
        DB::table(self::LIVE_TABLE)->where('residence_id', $residenceId)->delete();

        static::syncResidenceRows([$residenceId]);

        \App\Services\UnitWidgetDataService::bumpCacheVersion();
    }

    public static function syncAll(int $chunkSize = 5, ?callable $progressCallback = null): int
    {
        $total = 0;
        $chunkSize = max(1, $chunkSize);

        // Prevent memory growth from accumulated SQL query logs during long sync runs.
        DB::connection()->disableQueryLog();

        DB::table('residences')
            ->select('id')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk($chunkSize, function (Collection $chunk) use (&$total, $progressCallback) {
                $ids = $chunk->pluck('id')->toArray();
                static::syncResidenceRows($ids);

                $processed = count($ids);
                $total += $processed;

                if ($progressCallback !== null) {
                    $progressCallback($processed);
                }

                unset($ids);

                gc_collect_cycles();
            });

        DB::table(self::LIVE_TABLE)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('units')
                    ->whereColumn('units.id', self::LIVE_TABLE.'.unit_id')
                    ->whereNull('units.deleted_at');
            })
            ->delete();

        return $total;
    }

    public static function syncWidgetAggregates(): void
    {
        $data = \App\Services\UnitWidgetDataService::computeFromReadModel([]);
        WidgetAggregate::putCache('unit_global_stats', $data, 'unit');
        \App\Services\UnitWidgetDataService::bumpCacheVersion();
    }

    protected static function syncResidenceRows(array $residenceIds): void
    {
        $residences = static::getResidenceContext($residenceIds);

        if ($residences->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('units')
            ->whereIn('residence_id', $residences->keys()->all())
            ->whereNull('deleted_at')
            ->select(['id', 'residence_id', 'unit_number', 'sub_type', 'house_type', 'status', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(self::UNIT_SYNC_BATCH_SIZE, function (Collection $units) use ($residences, $now) {
                $rows = static::mapUnitRows($units, $residences, $now);

                if ($rows->isNotEmpty()) {
                    static::upsertRows($rows);
                }

                unset($rows);
                gc_collect_cycles();
            });
    }

    protected static function getResidenceContext(array $residenceIds): Collection
    {
        if (empty($residenceIds)) {
            return collect();
        }

        return DB::table('residences as r')
            ->leftJoin('mmbcnerp.thailand_sub_districts as sd', 'r.subdistrict_id', '=', 'sd.id')
            ->leftJoin('mmbcnerp.thailand_districts as d', 'sd.district_id', '=', 'd.id')
            ->leftJoin('mmbcnerp.thailand_provinces as p', 'd.province_id', '=', 'p.id')
            ->whereIn('r.id', $residenceIds)
            ->whereNull('r.deleted_at')
            ->select([
                'r.id as residence_id',
                'r.mooban_type',
                'r.residence_activation_status_id',
                'r.subdistrict_id',
                'd.id as district_id',
                'p.id as province_id',
            ])
            ->get()
            ->keyBy('residence_id');
    }

    protected static function mapUnitRows(Collection $units, Collection $residences, mixed $now): Collection
    {
        if ($units->isEmpty()) {
            return collect();
        }

        $unitUserMap = static::loadUnitRegistrationFlags($units->pluck('id')->all());

        return $units->map(function ($unit) use ($residences, $unitUserMap, $now) {
            $res = $residences->get($unit->residence_id);
            $uu = $unitUserMap->get($unit->id, [
                'is_signed_up' => false,
                'is_registered_owner' => false,
                'is_registered_tenant' => false,
            ]);

            return [
                'unit_id' => $unit->id,
                'residence_id' => $unit->residence_id,
                'mooban_type' => $res?->mooban_type,
                'residence_activation_status_id' => $res?->residence_activation_status_id,
                'subdistrict_id' => $res?->subdistrict_id,
                'district_id' => $res?->district_id,
                'province_id' => $res?->province_id,
                'unit_number' => $unit->unit_number,
                'sub_type' => $unit->sub_type,
                'house_type' => $unit->house_type,
                'status' => $unit->status,
                'is_signed_up' => (int) $uu['is_signed_up'],
                'is_registered_owner' => (int) $uu['is_registered_owner'],
                'is_registered_tenant' => (int) $uu['is_registered_tenant'],
                'synced_at' => $now,
                'unit_created_at' => $unit->created_at,
                'unit_updated_at' => $unit->updated_at,
                'created_at' => $unit->created_at,
                'updated_at' => $unit->updated_at,
            ];
        })->values();
    }

    protected static function loadUnitRegistrationFlags(array $unitIds): Collection
    {
        if (empty($unitIds)) {
            return collect();
        }

        return DB::table('unit_user')
            ->whereIn('unit_id', $unitIds)
            ->whereNull('deleted_at')
            ->groupBy('unit_id')
            ->selectRaw('unit_id, 1 as is_signed_up, MAX(CASE WHEN is_owner = 1 THEN 1 ELSE 0 END) as is_registered_owner, MAX(CASE WHEN is_owner = 0 THEN 1 ELSE 0 END) as is_registered_tenant')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->keyBy('unit_id');
    }

    protected static function upsertRows(Collection $rows): void
    {
        foreach ($rows->chunk(self::UNIT_SYNC_BATCH_SIZE) as $batch) {
            DB::table(self::LIVE_TABLE)->upsert(
                $batch->toArray(),
                ['unit_id'],
                array_keys($batch->first()),
            );
        }
    }
}
