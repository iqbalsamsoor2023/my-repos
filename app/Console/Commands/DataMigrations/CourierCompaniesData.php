<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\CompaniesLogo;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourierCompaniesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1_couriers = DB::connection('mmb1')
                ->table('courier_service_companies')
                ->get();

            foreach ($mmb1_couriers as $mmb1_courier) {
                $mmb2_courier = CompaniesLogo::create([
                    'category' => 'Courier',
                    'modes' => $mmb1_courier->modes == 1 ? 'Local' : 'International',
                    'name' => $mmb1_courier->name,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $mmb1_bucket = Storage::disk('cos2');
                $mmb2_bucket = Storage::disk('cos');

                if (isset($mmb1_courier->logo)) {
                    $file_name = basename($mmb1_courier->logo);
                    $mmb2_image_exist = $mmb2_bucket->exists($mmb1_courier->logo);
                    if (! $mmb2_image_exist) {
                        $url = $mmb1_bucket->url(config('app.path.cos')."/courier_company_logo/$mmb1_courier->logo");
                        $mmb2_courier->addMediaFromUrl($url)->toMediaCollection();
                    }
                }
            }

            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
