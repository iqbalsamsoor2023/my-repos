<?php

namespace App\Console\Commands\DataMigrations;

use App\Jobs\MigrateParcelDataJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ParcelsData
{
    public static function execute($residence_id)
    {
        try {
            $expiryDate = new Carbon('6 months ago');
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('parcels')
                ->select('*', 'parcels.id as id', 'parcels.created_at as created_at', 'parcels.updated_at as updated_at', 'parcels.contact_no as contact_no')
                ->leftJoin('residence_units', 'residence_units.id', 'parcels.residence_unit_id')
                ->leftJoin('users', 'users.id', 'parcels.user_id')
                ->where('residence_units.residence_id', $residence_id)
                ->where('parcels.updated_at', '>=', $expiryDate)
                ->orderBy('parcels.id', 'asc')
                ->chunk(300, function ($datas) {
                    $courier_id = null;

                    foreach ($datas as $key => $value) {
                        MigrateParcelDataJob::dispatch($value);
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
