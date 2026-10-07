<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Brand;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BrandsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1_brands = DB::connection('mmb1')
                ->table('vehicle_brands')
                ->get();

            foreach ($mmb1_brands as $mmb1_brand) {
                $mmb2_brand = Brand::create([
                    'name' => $mmb1_brand->name,
                    'name_th' => $mmb1_brand->name,
                    'gpl_priority' => $mmb1_brand->ordering,
                ]);

                $mmb1_bucket = Storage::disk('cos2');
                $mmb2_bucket = Storage::disk('cos');

                if (isset($mmb1_brand->logo) && $mmb1_bucket->has($mmb1_brand->logo)) {
                    $file_name = basename($mmb1_brand->logo);
                    $mmb2_image_exist = $mmb2_bucket->exists($mmb1_brand->logo);
                    if (! $mmb2_image_exist) {
                        $url = $mmb1_bucket->url($mmb1_brand->logo);
                        $mmb2_brand->addMediaFromUrl($url)->withCustomProperties(['type' => 'vehicle_brand_image'])->toMediaCollection('vehicle_brand_images');
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
