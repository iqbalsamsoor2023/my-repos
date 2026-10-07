<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\Company;
use App\Services\CloudObjectStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DevelopersImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public static function execute($residence_id)
    {
        $mmb1_developers = DB::connection('mmb1')
            ->table('developers')
            ->get();

        foreach ($mmb1_developers as $mmb1_developer) {
            $mmb2_company = Company::where('name', $mmb1_developer->name)->where('contact_email', $mmb1_developer->email)->first();

            $cosClient = CloudObjectStorageService::execute();
            $mmb1_bucket = Storage::disk('cos2');
            $mmb2_bucket = Storage::disk('cos');

            $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
            $result = $cosClient->listObjects([
                'Bucket' => $bucket,
                'Prefix' => config('app.path.cos')."/developer/$mmb1_developer->id/",
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
                    $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/developer/$mmb2_company->id/$file_name");

                    if (! $mmb2_image_exist) {
                        $url = $mmb1_bucket->url($image);
                        MigrateFilesJobs::dispatch($url, $mmb2_company, ['type' => 'Developer'], 'company_logo');
                    }
                }
            }
        }

        return true;
    }
}
