<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\ImageMigrations\CopyAnnouncementImageJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnouncementsImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('announcements')
            ->orderBy('announcements.id')
            ->whereJsonContains('residence_id', (int) $residence_id)
            ->chunk(1000, function ($datas) {
                $mmb1_bucket = Storage::disk('cos2');
                $mmb2_bucket = Storage::disk('cos');
                foreach ($datas as $key => $value) {
                    CopyAnnouncementImageJob::dispatch($value);
                }
            });
    }
}
