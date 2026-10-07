<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\Pet;
use App\Services\CloudObjectStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PetsImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $cosClient = CloudObjectStorageService::execute();
        $mmb1_bucket = Storage::disk('cos2');
        $mmb2_bucket = Storage::disk('cos');

        $mmb1_pets = DB::connection('mmb1')
            ->table('pets')
            ->select('*', 'pets.id as id', 'pets.created_at as created_at', 'pets.updated_at as updated_at')
            ->leftJoin('residence_units', 'residence_units.id', 'pets.residence_unit_id')
            ->where('residence_units.residence_id', $residence_id)
            ->get();

        foreach ($mmb1_pets as $mmb1_pet) {
            $mmb2_pet = Pet::withTrashed()->where('generated_pet_no', $mmb1_pet->pets_id)->where('created_at', $mmb1_pet->created_at)->first();

            $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
            $result = $cosClient->listObjects([
                'Bucket' => $bucket,
                'Prefix' => config('app.path.cos')."/pet/$mmb1_pet->id/gallery/",
            ]);

            $images = [];
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $rt) {
                    $images[] = $rt['Key'];
                }
            }

            foreach ($images as $image) {
                if ($mmb1_bucket->has($image)) {
                    $file_name = basename($image);
                    $imagePreFix = strstr($file_name, '_', true);
                    $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/pet/$mmb2_pet->id/gallery/$file_name");

                    if (! $mmb2_image_exist) {
                        $url = $mmb1_bucket->url($image);

                        if ($imagePreFix == 'back') {
                            MigrateFilesJobs::dispatch($url, $mmb2_pet, ['side' => 'back'], 'back');
                        } elseif ($imagePreFix == 'bottom') {
                            MigrateFilesJobs::dispatch($url, $mmb2_pet, ['side' => 'bottom'], 'bottom');
                        } elseif ($imagePreFix == 'front') {
                            MigrateFilesJobs::dispatch($url, $mmb2_pet, ['side' => 'front'], 'front');
                        } elseif ($imagePreFix == 'left') {
                            MigrateFilesJobs::dispatch($url, $mmb2_pet, ['side' => 'left'], 'left');
                        } elseif ($imagePreFix == 'right') {
                            MigrateFilesJobs::dispatch($url, $mmb2_pet, ['side' => 'right'], 'right');
                        } elseif ($imagePreFix == 'top') {
                            MigrateFilesJobs::dispatch($url, $mmb2_pet, ['side' => 'top'], 'top');
                        }
                    }
                }
            }
        }
    }
}
