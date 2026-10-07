<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOldParkingExports extends Command
{
    protected $signature = 'export:clean-old-parking-exports';

    protected $description = 'Clean up visitor parking export files older than 7 days';

    public function handle()
    {
        $this->info('Starting cleanup of old visitor parking exports...');

        $directory = 'exports/visitor-parkings';

        if (!Storage::disk('public')->exists($directory)) {
            $this->info('No exports directory found. Nothing to clean.');
            return 0;
        }

        $files = Storage::disk('public')->files($directory);
        $deletedCount = 0;
        $sevenDaysAgo = now()->subDays(7)->timestamp;

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);

            if ($lastModified < $sevenDaysAgo) {
                Storage::disk('public')->delete($file);
                $deletedCount++;
                $this->info("Deleted: {$file}");
            }
        }

        $this->info("Cleanup completed. Deleted {$deletedCount} file(s).");

        return 0;
    }
}
