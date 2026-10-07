<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\Event;
use App\Services\CloudObjectStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventsImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('events')
            ->select('*', 'events.id as id', 'events.created_at as created_at', 'events.updated_at as updated_at', 'residences.name as residence_name')
            ->join('residences', 'residences.id', 'events.residence_id')
            ->where('residences.id', $residence_id)
            ->orderBy('events.id')
            ->chunk(1000, function ($datas) {
                $cosClient = CloudObjectStorageService::execute();
                $mmb1_bucket = Storage::disk('cos2');
                $mmb2_bucket = Storage::disk('cos');
                foreach ($datas as $key => $value) {
                    $mmb2_event = Event::where('title', $value->title)->where('created_at', $value->created_at)->first();

                    $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
                    $result = $cosClient->listObjects([
                        'Bucket' => $bucket,
                        'Prefix' => config('app.path.cos')."/event/$value->id/gallery/",
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
                            $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/event/$mmb2_event->id/gallery/$file_name");
                            if (! $mmb2_image_exist) {
                                $url = $mmb1_bucket->url($image);
                                MigrateFilesJobs::dispatch($url, $mmb2_event, null, 'images');
                            }
                        }
                    }
                }
            });
    }
}
