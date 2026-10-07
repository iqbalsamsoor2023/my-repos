<?php

namespace App\Services;

use App\Models\WidgetAggregate;
use Illuminate\Support\Facades\DB;

class ParcelStatsViewSyncService
{
    private const LIVE_TABLE = 'parcel_stats_view';

    private const SYNC_BATCH_SIZE = 5000;

    public static function syncOne(int $parcelId): void
    {
        DB::table(self::LIVE_TABLE)->where('parcel_id', $parcelId)->delete();

        static::syncParcelRowsInRange($parcelId, $parcelId);

        ParcelWidgetDataService::bumpCacheVersion();
    }

    public static function syncAll(int $chunkSize = self::SYNC_BATCH_SIZE, ?callable $progressCallback = null): int
    {
        DB::connection()->disableQueryLog();

        $maxId = (int) DB::table('parcels')->max('id');

        if ($maxId === 0) {
            return 0;
        }

        for ($startId = 1; $startId <= $maxId; $startId += $chunkSize) {
            $endId = min($startId + $chunkSize - 1, $maxId);

            static::syncParcelRowsInRange($startId, $endId);

            if ($progressCallback !== null) {
                $progressCallback($endId - $startId + 1);
            }
        }

        DB::table(self::LIVE_TABLE)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('parcels')
                    ->whereColumn('parcels.id', self::LIVE_TABLE.'.parcel_id')
                    ->whereNull('parcels.deleted_at');
            })
            ->delete();

        return (int) DB::table(self::LIVE_TABLE)->count();
    }

    public static function syncWidgetAggregates(): void
    {
        $data = ParcelWidgetDataService::computeFromReadModel([]);
        WidgetAggregate::putCache('parcel_global_stats', $data, 'parcel');
        ParcelWidgetDataService::bumpCacheVersion();
    }

    protected static function syncParcelRowsInRange(int $startId, int $endId): void
    {
        DB::statement('
            INSERT INTO `parcel_stats_view` (
                `parcel_id`, `unit_id`, `residence_id`, `property_management_user_id`,
                `courier_id`, `courier_name`, `status`, `residence_activation_status_id`,
                `mooban_type`, `sub_type`, `subdistrict_id`, `main_road`,
                `parcel_created_at`, `parcel_updated_at`, `pickup_time`, `synced_at`,
                `created_at`, `updated_at`
            )
            SELECT
                p.id, p.unit_id, u.residence_id, r.property_management_user_id,
                p.courier_id, lp.name, p.status, r.residence_activation_status_id,
                r.mooban_type, u.sub_type, r.subdistrict_id, r.main_road,
                p.created_at, p.updated_at, p.pickup_time, NOW(), NOW(), NOW()
            FROM `parcels` p
            INNER JOIN `units` u ON p.unit_id = u.id AND u.deleted_at IS NULL
            INNER JOIN `residences` r ON u.residence_id = r.id AND r.deleted_at IS NULL
            LEFT JOIN `logistic_partners` lp ON p.courier_id = lp.id
            WHERE p.id BETWEEN ? AND ?
              AND p.deleted_at IS NULL
            ON DUPLICATE KEY UPDATE
                `unit_id`                        = VALUES(`unit_id`),
                `residence_id`                   = VALUES(`residence_id`),
                `property_management_user_id`    = VALUES(`property_management_user_id`),
                `courier_id`                     = VALUES(`courier_id`),
                `courier_name`                   = VALUES(`courier_name`),
                `status`                         = VALUES(`status`),
                `residence_activation_status_id` = VALUES(`residence_activation_status_id`),
                `mooban_type`                    = VALUES(`mooban_type`),
                `sub_type`                       = VALUES(`sub_type`),
                `subdistrict_id`                 = VALUES(`subdistrict_id`),
                `main_road`                      = VALUES(`main_road`),
                `parcel_created_at`              = VALUES(`parcel_created_at`),
                `parcel_updated_at`              = VALUES(`parcel_updated_at`),
                `pickup_time`                    = VALUES(`pickup_time`),
                `synced_at`                      = NOW(),
                `updated_at`                     = NOW()
        ', [$startId, $endId]);
    }
}
