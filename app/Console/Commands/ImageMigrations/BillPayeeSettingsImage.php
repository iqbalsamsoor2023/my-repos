<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\BillPayeeSetting;
use App\Services\CloudObjectStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BillPayeeSettingsImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1_bill_reminder_setting = DB::connection('mmb1')
            ->table('bill_reminder_settings')
            ->where('residence_id', $residence_id)
            ->first();

        if (isset($mmb1_bill_reminder_setting)) {
            $cosClient = CloudObjectStorageService::execute();
            $mmb1_bucket = Storage::disk('cos2');
            $mmb2_bucket = Storage::disk('cos');
            $mmb2_bill_payee_setting = BillPayeeSetting::where('residence_id', $residence_id)->first();

            $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
            $result = $cosClient->listObjects([
                'Bucket' => $bucket,
                'Prefix' => config('app.path.cos')."/bill-reminder/qr/$mmb1_bill_reminder_setting->id/",
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
                    $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/bill-reminder/qr/$mmb1_bill_reminder_setting->id/$file_name");

                    if (! $mmb2_image_exist) {
                        $url = $mmb1_bucket->url($image);
                        MigrateFilesJobs::dispatch($url, $mmb2_bill_payee_setting, null, 'qr');
                    }
                }
            }
        }
    }
}
