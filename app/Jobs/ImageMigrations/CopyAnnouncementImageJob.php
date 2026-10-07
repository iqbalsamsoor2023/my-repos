<?php

namespace App\Jobs\ImageMigrations;

use App\Models\Announcement;
use App\Services\CloudObjectStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CopyAnnouncementImageJob implements ShouldQueue
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

        $mmb2_announcement = Announcement::where('title', $value->title)->where('description', $value->description)->where('created_at', $value->created_at)->first();

        if (! $mmb2_announcement) {
            Log::info('Announcements');
            Log::info($value->title);
        }

        $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
        $result = $cosClient->listObjects([
            'Bucket' => $bucket,
            'Prefix' => config('app.path.cos')."/announcement/$value->id/gallery/",
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
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/announcement/$mmb2_announcement->id/gallery/$file_name");
                if (! $mmb2_image_exist) {
                    $url = $mmb1_bucket->url($image);
                    $mmb2_announcement->addMediaFromUrl($url)->withCustomProperties(['type' => 'image'])->toMediaCollection('images');
                }
            }
        }

        $result = $cosClient->listObjects([
            'Bucket' => $bucket,
            'Prefix' => config('app.path.cos')."/announcement/$value->id/",
        ]);

        $attachments = [];
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $rt) {
                $attachments[] = $rt['Key'];
            }
        }

        foreach ($attachments as $attachment) {
            if ($mmb1_bucket->has($attachment)) {
                $file_name = basename($image);
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/announcement/$mmb2_announcement->id/$file_name");
                if (! $mmb2_image_exist) {
                    $url = $mmb1_bucket->url($attachment);
                    $mmb2_announcement->addMediaFromUrl($url)->withCustomProperties(['type' => 'document'])->toMediaCollection('document');
                }
            }
        }
    }
}
