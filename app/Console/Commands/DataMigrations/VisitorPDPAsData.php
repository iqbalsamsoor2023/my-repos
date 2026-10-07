<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\VisitorSetting;
use App\Services\CloudObjectStorageService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VisitorPDPAsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();

            $residence = DB::connection('mmb1')
                ->table('residences')
                ->where('id', $residence_id)->first();

            $mmb2_visitor_setting = VisitorSetting::create([
                'residence_id' => $residence_id,
                'is_qr_active' => 1,
            ]);

            $cosClient = CloudObjectStorageService::execute();
            $mmb1_bucket = Storage::disk('cos2');
            $mmb2_bucket = Storage::disk('cos');

            $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
            $result = $cosClient->listObjects([
                'Bucket' => $bucket,
                'Prefix' => config('app.path.cos')."/residence/$residence_id/visitor-pdpa/",
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
                    $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/residence/$residence_id/visitor-pdpa/$file_name");

                    if (! $mmb2_image_exist) {
                        $url = $mmb1_bucket->url($image);
                        $mmb2_visitor_setting->addMediaFromUrl($url)->withCustomProperties(['type' => 'document'])->toMediaCollection('document');
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
