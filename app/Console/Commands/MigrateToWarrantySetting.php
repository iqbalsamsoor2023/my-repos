<?php

namespace App\Console\Commands;

use App\Models\Residence;
use App\Models\WarrantySetting;
use App\Models\OtherAmenity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudObjectStorageService;

class MigrateToWarrantySetting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-to-warranty-setting-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $this->info('Starting data migration...');

        DB::disableQueryLog();

        $total = DB::table('other_amenities')->count();

        if ($total === 0) {
            $this->warn('No data found.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::table('other_amenities')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($bar) {
                $cosClient = CloudObjectStorageService::execute();
                // $mmb1_bucket = Storage::disk('cos2');
                $mmb2_bucket = Storage::disk('cos');

                foreach ($rows as $row) {

                    $bucket = 'mmb2-1258956757'; // Bucket name in the format of BucketName-APPID
                    $result = $cosClient->listObjects([
                        'Bucket' => $bucket,
                        'Prefix' => config('app.path.cos')."/warranty-handbook/$row->id/",
                    ]);

                    $images = [];
                    if (isset($result['Contents'])) {
                        foreach ($result['Contents'] as $rt) {
                            $images[] = $rt['Key'];
                        }
                    }

                    $mmb2_other_amenity = OtherAmenity::where('id', $row->id)->first();

                    // foreach ($images as $image) {
                    //     if ($mmb2_bucket->has($image)) {
                    //         $url = $mmb2_bucket->url($image);
                    //         dd($url);
                    //         // $mmb2_other_amenity->addMediaFromUrl($url)->toMediaCollection('document')->withCustomProperties(['attachment' => 'warranty_handbook']);
                        
                    // }
                    // }

                    $residence = Residence::withTrashed()->find($row->residence_id);

                    
                    $warranty_setting =  WarrantySetting::create([
                        'id'                         => $row->id,
                        'residence_id'               => $row->residence_id,
                        'has_other_option'           => $row->is_other_amenity,
                        'remark'                     => $row->remark,
                        'has_warranty_reminder'      => $row->is_show_warranty_reminder,
                        'reminder_day'               => $row->remind_day,
                        'has_appointment_schedule'   => $residence?->appointment_datetime_status,
                        'has_verification'           => $residence?->verify_public_claim,
                        'created_at'                 => $row->created_at,
                        'updated_at'                 => $row->updated_at,
                    ]);

                   foreach ($images as $image) {
                        if ($mmb2_bucket->has($image)) {
                            $url = $mmb2_bucket->url($image);
                            Log::info($url);
                            $warranty_setting->addMediaFromUrl($url)->withCustomProperties(['attachment' => 'warranty_handbook'])->toMediaCollection('document');
                        
                        }
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info('Migration completed successfully.');

        return Command::SUCCESS;
    }
}