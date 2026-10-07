<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\ImageMigrations\CopyParcelImageJob;
use App\Jobs\ImageMigrations\CopyParcelSignatureImageJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ParcelsImage
{
    public static function execute($residence_id)
    {
        $expiryDate = new Carbon('6 months ago');
        $mmb1Data = DB::connection('mmb1')
            ->table('parcels')
            ->select('*', 'parcels.id as id', 'parcels.created_at as created_at', 'parcels.updated_at as updated_at')
            ->leftJoin('residence_units', 'residence_units.id', 'parcels.residence_unit_id')
            ->leftJoin('users', 'users.id', 'parcels.user_id')
            ->where('residence_units.residence_id', $residence_id)
            ->where('parcels.updated_at', '>=', $expiryDate)
            ->orderBy('parcels.id', 'asc')
            ->chunk(1000, function ($datas) {
                foreach ($datas as $key => $value) {
                    CopyParcelSignatureImageJob::dispatch($value);
                    CopyParcelImageJob::dispatch($value);
                }
            });
    }
}
