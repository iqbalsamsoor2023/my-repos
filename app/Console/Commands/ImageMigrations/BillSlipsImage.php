<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\Transaction;
use App\Services\CloudObjectStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BillSlipsImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('bill_reminder_slips')
            ->select('*', 'bill_reminder_slips.id as id', 'bill_reminder_slips.remark as remark', 'bill_reminder_slips.created_at as created_at', 'bill_reminder_slips.updated_at as updated_at')
            ->leftJoin('bill_reminders', 'bill_reminder_slips.bill_reminder_id', 'bill_reminders.id')
            ->leftJoin('residence_units', 'residence_units.id', 'bill_reminders.residence_unit_id')
            ->where('residence_units.residence_id', $residence_id)
            ->orderBy('bill_reminders.id', 'asc')
            ->chunk(1000, function ($datas) {
                $cosClient = CloudObjectStorageService::execute();
                $mmb1_bucket = Storage::disk('cos2');
                $mmb2_bucket = Storage::disk('cos');
                foreach ($datas as $key => $value) {
                    $mmb2_transaction = Transaction::where('ref_no', $value->receipt_no)->where('paid_amount', $value->pay_amount)->where('remark', $value->remark)->where('created_at', $value->created_at)->where('transaction_datetime', $value->payment_date)->first();

                    $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
                    $result = $cosClient->listObjects([
                        'Bucket' => $bucket,
                        'Prefix' => config('app.path.cos')."/bill-reminder-slip/$value->id/",
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
                            $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/bill-reminder-slip/$mmb2_transaction->id/$file_name");
                            if (! $mmb2_image_exist) {
                                $url = $mmb1_bucket->url($image);
                                MigrateFilesJobs::dispatch($url, $mmb2_transaction, ['type' => 'bill-reminder-slip'], null);
                            }
                        }
                    }
                }
            });
    }
}
