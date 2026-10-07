<?php

namespace App\Console\Commands\DataMigrations;

use App\Jobs\MigrateVisitorDataJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class VisitorsData
{
    public function execute($residence_id)
    {
        try {
            $expiryDate = new Carbon('1 month ago');
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('visitors')
                ->where('visitors.residence_id', $residence_id)
                ->where('updated_at', '>=', $expiryDate)
                ->orderBy('visitors.id')
                ->chunk(300, function ($datas, $unit) use ($residence_id) {
                    foreach ($datas as $key => $value) {
                        MigrateVisitorDataJob::dispatch($value, $residence_id);
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
