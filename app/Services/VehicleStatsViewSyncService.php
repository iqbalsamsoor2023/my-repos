<?php

namespace App\Services;

use App\Models\VehicleStatsView;
use App\Models\WidgetAggregate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VehicleStatsViewSyncService
{
    private const LIVE_TABLE = 'vehicle_stats_view';

    private const SYNC_BATCH_SIZE = 1000;

    public static function syncOne(int $vehicleId): void
    {
        DB::table(self::LIVE_TABLE)->where('vehicle_id', $vehicleId)->delete();

        static::syncVehicleRows([$vehicleId]);

        VehicleWidgetDataService::bumpCacheVersion();
    }

    public static function syncAll(int $chunkSize = 1000, ?callable $progressCallback = null): int
    {
        $total = 0;

        DB::connection()->disableQueryLog();

        DB::table('vehicles')
            ->select('id')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $chunk) use (&$total, $progressCallback) {
                $ids = $chunk->pluck('id')->toArray();
                static::syncVehicleRows($ids);

                $processed = count($ids);
                $total += $processed;

                if ($progressCallback !== null) {
                    $progressCallback($processed);
                }

                unset($ids);
                gc_collect_cycles();
            });

        // Remove rows for soft-deleted vehicles
        DB::table(self::LIVE_TABLE)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('vehicles')
                    ->whereColumn('vehicles.id', self::LIVE_TABLE.'.vehicle_id')
                    ->whereNull('vehicles.deleted_at');
            })
            ->delete();

        return $total;
    }

    public static function syncWidgetAggregates(): void
    {
        $data = VehicleWidgetDataService::computeFromReadModel([]);
        WidgetAggregate::putCache('vehicle_global_stats', $data, 'vehicle');
        VehicleWidgetDataService::bumpCacheVersion();
    }

    protected static function syncVehicleRows(array $vehicleIds): void
    {
        if (empty($vehicleIds)) {
            return;
        }

        DB::table('vehicles as v')
            ->join('vehicle_models as vm', 'v.vehicle_model_id', '=', 'vm.id')
            ->leftJoin('vehicle_brands as vb', 'vm.vehicle_brand_id', '=', 'vb.id')
            ->join('units as u', 'v.unit_id', '=', 'u.id')
            ->join('residences as r', 'u.residence_id', '=', 'r.id')
            ->leftJoin('insurance_companies as ic', 'v.insurance_company_id', '=', 'ic.id')
            ->whereIn('v.id', $vehicleIds)
            ->whereNull('v.deleted_at')
            ->whereNull('vm.deleted_at')
            ->whereNull('u.deleted_at')
            ->whereNull('r.deleted_at')
            ->select([
                'v.id as vehicle_id',
                'v.unit_id',
                'u.residence_id',
                'r.property_management_user_id',
                'vm.type as vehicle_type',
                'vb.id as vehicle_brand_id',
                'vb.name as vehicle_brand_name',
                'vm.body_type',
                'v.fuel_type',
                'v.model_year',
                'v.insurance_company_id',
                'ic.name_th as insurance_company_name',
                'ic.name as insurance_company_name_en',
                'r.residence_activation_status_id',
                'r.mooban_type',
                'u.sub_type',
                'r.subdistrict_id',
                'r.main_road',
                'v.created_at as vehicle_created_at',
                'v.updated_at as vehicle_updated_at',
            ])
            ->orderBy('v.id')
            ->chunkById(self::SYNC_BATCH_SIZE, function (Collection $rows) {
                $now = now();

                $upsertData = $rows->map(fn ($row) => [
                    'vehicle_id' => $row->vehicle_id,
                    'unit_id' => $row->unit_id,
                    'residence_id' => $row->residence_id,
                    'property_management_user_id' => $row->property_management_user_id,
                    'vehicle_type' => $row->vehicle_type,
                    'vehicle_brand_id' => $row->vehicle_brand_id,
                    'vehicle_brand_name' => $row->vehicle_brand_name,
                    'body_type' => $row->body_type,
                    'fuel_type' => $row->fuel_type,
                    'model_year' => $row->model_year,
                    'insurance_company_id' => $row->insurance_company_id,
                    'insurance_company_name' => $row->insurance_company_name,
                    'insurance_company_name_en' => $row->insurance_company_name_en,
                    'residence_activation_status_id' => $row->residence_activation_status_id,
                    'mooban_type' => $row->mooban_type,
                    'sub_type' => $row->sub_type,
                    'subdistrict_id' => $row->subdistrict_id,
                    'main_road' => $row->main_road,
                    'vehicle_created_at' => $row->vehicle_created_at,
                    'vehicle_updated_at' => $row->vehicle_updated_at,
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->toArray();

                if (! empty($upsertData)) {
                    VehicleStatsView::upsert(
                        $upsertData,
                        ['vehicle_id'],
                        array_keys($upsertData[0])
                    );
                }

                unset($upsertData);
                gc_collect_cycles();
            }, 'v.id', 'vehicle_id');
    }
}
