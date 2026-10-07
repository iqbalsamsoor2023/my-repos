<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOldExportTemporaryImages extends Command
{
    protected $signature = 'export:clean-temp-images {--minutes=30}';

    protected $description = 'Delete temp export image folders older than given minutes (default: 30)';

    public function handle(): int
    {
        $tempRoot = 'temp-images';
        $disk = Storage::disk('local');

        if (! $disk->exists($tempRoot)) {
            return self::SUCCESS;
        }

        $thresholdMinutes = (int) $this->option('minutes');
        $now = Carbon::now();

        $deletedCount = 0;

        foreach ($disk->directories($tempRoot) as $dir) {
            $path = "{$dir}";
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($path));

            if ($now->diffInMinutes($lastModified) >= $thresholdMinutes) {
                $disk->deleteDirectory($path);
                $this->line("Deleted old export folder: $path");
                $deletedCount++;
            }
        }

        $this->info("Cleanup complete. Deleted $deletedCount folder(s).");

        return self::SUCCESS;
    }
}
