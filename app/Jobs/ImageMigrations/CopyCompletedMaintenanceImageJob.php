<?php

namespace App\Jobs\ImageMigrations;

use App\Models\Maintenance;
use App\Services\CloudObjectStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CopyCompletedMaintenanceImageJob implements ShouldQueue
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
        $maintenance_progress_id = 0;
        $cosClient = CloudObjectStorageService::execute();
        $mmb1_bucket = Storage::disk('cos2');
        $mmb2_bucket = Storage::disk('cos');

        $mmb2_maintenance = Maintenance::where('updated_at', $value->updated_at)->where('issue_description', $value->remark)->where('created_at', $value->created_at)->first();
        $maintenance_progress_id = DB::connection('mmb1')->table('maintenance_progresses')->where('maintenance_id', $value->id)->latest()->value('id');

        $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
        $result = $cosClient->listObjects([
            'Bucket' => $bucket,
            'Prefix' => config('app.path.cos')."/maintenance/$value->id/completed/$maintenance_progress_id/",
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
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/maintenance/$mmb2_maintenance->id/completed/$mmb2_maintenance->id/$file_name");
                if (! $mmb2_image_exist) {
                    $url = $mmb1_bucket->url($image);
                    $mmb2_maintenance->addMediaFromUrl($url)->withCustomProperties(['type' => 'maintenance_completed'])->toMediaCollection('maintenance_completed_images');
                }
            }
        }
    }
}
