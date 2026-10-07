<?php

namespace App\Jobs\ImageMigrations;

use App\Models\VisitorLog;
use App\Models\VisitorParking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CopyVoucherImageJob implements ShouldQueue
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

        $mmb1_bucket = Storage::disk('cos2');
        $mmb2_bucket = Storage::disk('cos');

        $mmb2_visitor = VisitorLog::withTrashed()->where('visitor_generated_no', $value->run_visitor_no)->where('visitor_code', $value->code)->first();

        $mmb2_visitor_parking = VisitorParking::where('visitor_log_id', $mmb2_visitor->id)->first();
        $path = config('app.path.cos')."/parking-fees/$value->id/image-1.png";

        if ($mmb1_bucket->has($path)) {
            $file_name = basename($path);
            $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/parking-fees/$mmb2_visitor_parking->id/$file_name");
            if (! $mmb2_image_exist) {
                $url = $mmb1_bucket->url($path);
                $mmb2_visitor_parking->addMediaFromUrl($url)->withCustomProperties(['type' => 'voucher_image'])->toMediaCollection('voucher_image');
            }
        }
    }
}
