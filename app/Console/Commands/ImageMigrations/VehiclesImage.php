<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehiclesImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1_user_vehicles = DB::connection('mmb1')
            ->table('user_vehicles')
            ->select('*', 'user_vehicles.id as id', 'user_vehicles.created_at as created_at', 'user_vehicles.updated_at as updated_at')
            ->leftJoin('residence_units', 'residence_units.id', 'user_vehicles.residence_unit_id')
            ->where('residence_units.residence_id', $residence_id)
            ->get();
        $mmb1_bucket = Storage::disk('cos2');
        $mmb2_bucket = Storage::disk('cos');

        foreach ($mmb1_user_vehicles as $mmb1_user_vehicle) {
            $mmb2_vehicle = Vehicle::withTrashed()->where('plate_number', $mmb1_user_vehicle->plate_no)->where('created_at', $mmb1_user_vehicle->created_at)->first();

            // reason why vehicle image and roadtax image did not fetch by folder is because roadtax and vehicle image is in the same folder.
            // we need to seperate it to attach to mmb2 model
            if ($mmb1_user_vehicle->image) {
                $url = $mmb1_bucket->url(config('app.path.cos')."/vehicle/$mmb1_user_vehicle->id/$mmb1_user_vehicle->image");
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/vehicle/$mmb2_vehicle->id/$mmb1_user_vehicle->image");
                if (! $mmb2_image_exist) {
                    MigrateFilesJobs::dispatch($url, $mmb2_vehicle, ['type' => 'vehicle'], 'vehicle_image');
                }
            }

            if ($mmb1_user_vehicle->roadtax_image) {
                $roadtax_url = $mmb1_bucket->url(config('app.path.cos')."/vehicle/$mmb1_user_vehicle->id/$mmb1_user_vehicle->roadtax_image");
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/vehicle/$mmb2_vehicle->id/$mmb1_user_vehicle->roadtax_image");
                if (! $mmb2_image_exist) {
                    MigrateFilesJobs::dispatch($roadtax_url, $mmb2_vehicle, ['type' => 'roadtax'], 'roadtax_image');
                }
            }
        }
    }
}
