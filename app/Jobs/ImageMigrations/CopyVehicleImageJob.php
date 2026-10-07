<?php

namespace App\Jobs\ImageMigrations;

use App\Models\VisitorLog;
use App\Services\CloudObjectStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CopyVehicleImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $collection;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($collection)
    {
        $this->onQueue('imageQueue');
        $this->collection = $collection;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $value = $this->collection;

        $cosClient = CloudObjectStorageService::execute();
        $mmb1_bucket = Storage::disk('cos2');
        $mmb2_bucket = Storage::disk('cos');

        $mmb2_visitor = VisitorLog::withTrashed()->where('visitor_generated_no', $value->run_visitor_no)
            ->where('visitor_code', $value->code)->first();
        // Vehicle Image

        $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
        $result = $cosClient->listObjects([
            'Bucket' => $bucket,
            'Prefix' => config('app.path.cos')."/visitor/vehicle/$value->id/",
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
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/visitor/vehicle/$mmb2_visitor->id/$file_name");
                if (! $mmb2_image_exist) {
                    $url = $mmb1_bucket->url($image);
                    $mmb2_visitor->addMediaFromUrl($url)->withCustomProperties(['type' => 'vehicle_image'])->toMediaCollection('vehicle_image');
                }
            }
        }
    }
}
