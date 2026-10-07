<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\ImageMigrations\CopyVoucherImageJob;
use Illuminate\Support\Facades\DB;

class VouchersImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('visitor_parkings')
            ->select('*', 'visitor_parkings.id as id', 'visitor_parkings.created_at as created_at', 'visitor_parkings.updated_at as updated_at')
            ->join('visitors', 'visitors.id', 'visitor_parkings.visitor_id')
            ->where('visitors.residence_id', $residence_id)
            ->whereNotNull('visitors.vehicle_type')
            ->whereNotNull('visitors.arrive_at')
            ->orderBy('visitor_parkings.id')
            ->chunk(10000, function ($datas) {
                foreach ($datas as $key => $value) {
                    CopyVoucherImageJob::dispatch($value);
                }
            });

        return true;
    }
}
