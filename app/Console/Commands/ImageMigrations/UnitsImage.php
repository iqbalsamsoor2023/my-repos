<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\ImageMigrations\CopyBookingFormJob;
use App\Jobs\ImageMigrations\CopyFloorPlanImageJob;
use App\Jobs\ImageMigrations\CopyFloorPlanJob;
use App\Jobs\ImageMigrations\CopyHouseContractJob;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UnitsImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1_bucket = Storage::disk('cos2');
        $mmb2_bucket = Storage::disk('cos');

        $mmb1_units = DB::connection('mmb1')
            ->table('residence_units')
            ->where('residence_id', $residence_id)
            ->orderBy('id', 'asc')
            ->chunk(1000, function ($datas) {
                foreach ($datas as $key => $value) {
                    $mmb2_unit = Unit::withTrashed()->where('home_id', $value->home_id)
                        ->where('unit_number', $value->unit)
                        ->first();
                    CopyBookingFormJob::dispatch($value);
                    CopyHouseContractJob::dispatch($value);
                    CopyFloorPlanJob::dispatch($value);
                    CopyFloorPlanImageJob::dispatch($value);
                }
            });
    }
}
