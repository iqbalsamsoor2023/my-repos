<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\ImageMigrations\CopyCompletedMaintenanceImageJob;
use App\Jobs\ImageMigrations\CopyMaintenanceImageJob;
use App\Jobs\ImageMigrations\CopyMaintenanceVerificationImageJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MaintenancesImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('maintenances')
            ->where('residence_id', $residence_id)
            ->orderBy('id', 'asc')
            ->chunk(1000, function ($datas) {
                $mmb1_bucket = Storage::disk('cos2');
                $mmb2_bucket = Storage::disk('cos');
                foreach ($datas as $key => $value) {
                    CopyMaintenanceImageJob::dispatch($value);
                    CopyCompletedMaintenanceImageJob::dispatch($value);
                    CopyMaintenanceVerificationImageJob::dispatch($value);
                }
            });
    }
}
