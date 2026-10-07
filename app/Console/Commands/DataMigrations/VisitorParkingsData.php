<?php

namespace App\Console\Commands\DataMigrations;

use App\Jobs\MigrateVisitorParkingJob;
use Exception;
use Illuminate\Support\Facades\DB;

class VisitorParkingsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();

            $mmb1Data = DB::connection('mmb1')
                ->table('visitor_parkings')
                ->select('*', 'visitor_parkings.id as id', 'visitor_parkings.created_at as created_at', 'visitor_parkings.updated_at as updated_at')
                ->join('visitors', 'visitors.id', 'visitor_parkings.visitor_id')
                ->where('visitors.residence_id', $residence_id)
                ->whereNotNull('visitors.vehicle_type')
                ->whereNotNull('visitors.arrive_at')
                ->orderBy('visitor_parkings.id')
                ->chunk(500, function ($datas) use ($residence_id) {
                    foreach ($datas as $key => $value) {
                        MigrateVisitorParkingJob::dispatch($value, $residence_id);
                    }
                });

            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
