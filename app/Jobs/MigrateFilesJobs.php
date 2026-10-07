<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MigrateFilesJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mmb2_bucket;

    protected $url;

    protected $model;

    protected $custom_properties;

    protected $media_collection;

    // // 1 hours

    // public $timeout = 3600;

    // public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($url, $model, $custom_properties = null, $media_collection = null)
    {
        $this->url = $url;

        $this->model = $model;

        $this->custom_properties = $custom_properties;

        $this->media_collection = $media_collection;
    }

    /**
     * Execute the job.

     *

     * @return void
     */
    public function handle()
    {
        if (! empty($this->custom_properties) && ! empty($this->media_collection)) {
            $this->model->addMediaFromUrl($this->url)->withCustomProperties($this->custom_properties)->toMediaCollection($this->media_collection);
        } elseif (! empty($this->custom_properties)) {
            $this->model->addMediaFromUrl($this->url)->withCustomProperties($this->custom_properties)->toMediaCollection();
        } elseif (! empty($this->media_collection)) {
            $this->model->addMediaFromUrl($this->url)->toMediaCollection($this->media_collection);
        } else {
            $this->model->addMediaFromUrl($this->url)->toMediaCollection();
        }

        return true;
    }
}
