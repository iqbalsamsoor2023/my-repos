<?php

namespace App\Jobs\ImageMigrations;

use App\Models\Parcel;
use App\Services\CloudObjectStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CopyParcelSignatureImageJob implements ShouldQueue
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
        $this->onQueue('migrateParcelDataQueue');
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

        $mmb2_parcel = Parcel::withTrashed()->where('qr_code', $value->code)->first();

        $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
        $result = $cosClient->listObjects([
            'Bucket' => $bucket,
            'Prefix' => config('app.path.cos')."/parcel/signature/$value->id/",
        ]);

        $signatures = [];
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $rt) {
                $signatures[] = $rt['Key'];
            }
        }

        foreach ($signatures as $sign) {
            if ($mmb1_bucket->has($sign)) {
                $file_name = basename($sign);
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/parcel/$mmb2_parcel->id/signature/$file_name");

                if (! $mmb2_image_exist) {
                    $url = $mmb1_bucket->url($sign);
                    $mmb2_parcel->addMediaFromUrl($url)->withCustomProperties(['type' => 'signature'])->toMediaCollection('signature_image');
                }
            }
        }
    }
}
