<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnitUserStatsViewSyncService
{
    private const LIVE_TABLE = 'unit_user_stats_view';

    private const SYNC_BATCH_SIZE = 500;

    public static function syncOne(int $residenceId): void
    {
        DB::table(self::LIVE_TABLE)->where('residence_id', $residenceId)->delete();

        static::syncResidenceRows([$residenceId]);

        UnitUserWidgetDataService::bumpCacheVersion();
    }

    public static function syncAll(int $chunkSize = 5, ?callable $progressCallback = null): int
    {
        $total = 0;
        $chunkSize = max(1, $chunkSize);

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

        DB::table(self::LIVE_TABLE.' as uuv')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('unit_user as uu')
                    ->join('users as usr', function ($join) {
                        $join->on('usr.id', '=', 'uu.user_id')
                            ->whereNull('usr.deleted_at');
                    })
                    ->join('units as unt', function ($join) {
                        $join->on('unt.id', '=', 'uu.unit_id')
                            ->whereNull('unt.deleted_at');
                    })
                    ->join('residences as res', function ($join) {
                        $join->on('res.id', '=', 'unt.residence_id')
                            ->whereNull('res.deleted_at');
                    })
                    ->whereColumn('uu.id', 'uuv.unit_user_id');
            })
            ->delete();

        return $total;
    }

    public static function syncWidgetAggregates(): void
    {
        UnitUserWidgetDataService::syncWidgetAggregates();
    }

    protected static function syncResidenceRows(array $residenceIds): void
    {
        if (empty($residenceIds)) {
            return;
        }

        $now = now();

        DB::table('unit_user as uu')
            ->join('users as usr', function ($join) {
                $join->on('usr.id', '=', 'uu.user_id')
                    ->whereNull('usr.deleted_at');
            })
            ->join('units as unt', function ($join) {
                $join->on('unt.id', '=', 'uu.unit_id')
                    ->whereNull('unt.deleted_at');
            })
            ->join('residences as res', function ($join) {
                $join->on('res.id', '=', 'unt.residence_id')
                    ->whereNull('res.deleted_at');
            })
            ->leftJoin('mmbcnerp.thailand_sub_districts as sd', 'res.subdistrict_id', '=', 'sd.id')
            ->leftJoin('mmbcnerp.thailand_districts as d', 'sd.district_id', '=', 'd.id')
            ->leftJoin('mmbcnerp.thailand_provinces as p', 'd.province_id', '=', 'p.id')
            ->leftJoin('countries as c', 'c.id', '=', 'usr.country_id')
            ->whereIn('unt.residence_id', $residenceIds)
            ->selectRaw("
                uu.id as unit_user_id,
                uu.unit_id,
                uu.user_id,
                unt.residence_id,
                res.mooban_type,
                res.sub_type,
                res.residence_activation_status_id,
                res.main_road,
                res.subdistrict_id,
                d.id as district_id,
                p.id as province_id,
                unt.unit_number,
                usr.email,
                usr.country_id,
                COALESCE(c.name, 'Not Yet Set') as country_name,
                usr.gender,
                usr.date_of_birth as user_date_of_birth,
                CASE
                    WHEN usr.date_of_birth IS NULL THEN 'Not Set'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 6 AND 12 THEN '06-12'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 13 AND 22 THEN '13-22'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 23 AND 30 THEN '23-30'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 31 AND 40 THEN '31-40'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 41 AND 50 THEN '41-50'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 51 AND 60 THEN '51-60'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 61 AND 70 THEN '61-70'
                    ELSE '>70'
                END as widget_age_group,
                CASE
                    WHEN usr.date_of_birth IS NULL THEN 'Not Set'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 0 AND 17 THEN '0-17'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                    WHEN TIMESTAMPDIFF(YEAR, usr.date_of_birth, CURDATE()) BETWEEN 55 AND 64 THEN '55-64'
                    ELSE '65+'
                END as table_age_group,
                uu.is_owner,
                uu.is_main_owner,
                uu.is_main_tenant,
                uu.approval_status,
                CASE WHEN usr.email_verified_at IS NULL THEN 0 ELSE 1 END as is_email_verified,
                uu.created_at as unit_user_created_at,
                uu.updated_at as unit_user_updated_at,
                uu.deleted_at as unit_user_deleted_at
            ")
            ->orderBy('uu.id')
            ->chunkById(self::SYNC_BATCH_SIZE, function (Collection $rows) use ($now) {
                $payload = $rows->map(function ($row) use ($now) {
                    $data = (array) $row;
                    $data['synced_at'] = $now;
                    $data['created_at'] = $now;
                    $data['updated_at'] = $now;

                    return $data;
                });

                static::upsertRows($payload);

                unset($payload);
                gc_collect_cycles();
            }, 'uu.id', 'unit_user_id');
    }

    protected static function upsertRows(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows->chunk(self::SYNC_BATCH_SIZE) as $batch) {
            DB::table(self::LIVE_TABLE)->upsert(
                $batch->toArray(),
                ['unit_user_id'],
                array_keys($batch->first()),
            );
        }
    }
}
