<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\BlacklistedVisitor;
use App\Services\CloudObjectStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BlacklistedVisitorsImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1_blacklisted_visitors = DB::connection('mmb1')
            ->table('visitor_blacklists')
            ->where('residence_id', $residence_id)
            ->get();

        $cosClient = CloudObjectStorageService::execute();
        $mmb1_bucket = Storage::disk('cos2');
        $mmb2_bucket = Storage::disk('cos');

        foreach ($mmb1_blacklisted_visitors as $mmb1_blacklisted_visitor) {
            $mmb2_blacklisted_visitor = BlacklistedVisitor::withTrashed()->where('residence_id', $residence_id)->where('vehicle_plate_no', $mmb1_blacklisted_visitor->plate_number)->where('created_at', $mmb1_blacklisted_visitor->created_at)->first();

            $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
            $result = $cosClient->listObjects([
                'Bucket' => $bucket,
                'Prefix' => config('app.path.cos')."/visitor_blacklist/$mmb1_blacklisted_visitor->id/",
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
                    $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/visitor_blacklist/$mmb2_blacklisted_visitor->id/$file_name");
                    if (! $mmb2_image_exist) {
                        $url = $mmb1_bucket->url($image);
                        MigrateFilesJobs::dispatch($url, $mmb2_blacklisted_visitor, null, 'blacklist_visitor');
                    }
                }
            }
        }
    }
}
