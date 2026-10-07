<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\ImageMigrations\CopyPDPASignImageJob;
use App\Jobs\ImageMigrations\CopyVehicleImageJob;
use App\Jobs\ImageMigrations\CopyVisitorIdImageJob;
use App\Jobs\ImageMigrations\CopyVisitorImageJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VisitorsImage
{
    public static function execute($residence_id)
    {
        $expiryDate = new Carbon('1 month ago');

        $mmb1Data = DB::connection('mmb1')
            ->table('visitors')
            ->where('visitors.residence_id', $residence_id)
            ->where('updated_at', '>=', $expiryDate)
            ->orderBy('visitors.id')
            ->chunk(1000, function ($datas, $unit) {
                foreach ($datas as $key => $value) {
                    CopyVisitorImageJob::dispatch($value);

                    CopyVisitorIdImageJob::dispatch($value);

                    CopyVehicleImageJob::dispatch($value);

                    CopyPDPASignImageJob::dispatch($value);
                }
            });

        return true;
    }
}
