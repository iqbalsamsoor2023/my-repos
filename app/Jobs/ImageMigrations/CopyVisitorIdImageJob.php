<?php

namespace App\Jobs\ImageMigrations;

use App\Models\VisitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CopyVisitorIdImageJob implements ShouldQueue
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

        $mmb2_visitor = VisitorLog::withTrashed()->where('visitor_generated_no', $value->run_visitor_no)
            ->where('visitor_code', $value->code)->first();

        $path = config('app.path.cos')."/visitor/$value->id/$value->id_image";

        // foreach ($images as $image) {
        // Job
        if ($mmb1_bucket->has($path)) {
            $file_name = basename($path);
            $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/visitor/$mmb2_visitor->id/$file_name");
            if (! $mmb2_image_exist) {
                $url = $mmb1_bucket->url($path);
                $mmb2_visitor->addMediaFromUrl($url)->withCustomProperties(['type' => 'id_image'])->toMediaCollection('id_image');
            }
        }
        // }
    }
}
