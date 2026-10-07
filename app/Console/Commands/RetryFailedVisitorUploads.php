<?php

namespace App\Console\Commands;

use App\Jobs\ImageProcessing\ProcessFailedVisitorUploadImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RetryFailedVisitorUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitor:retry-failed-images {--limit=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry uploading failed visitor images to COS';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $basePath = storage_path('app/temp/visitor');

        if (!File::exists($basePath)) {
            $this->error("Base path not found: {$basePath}");
            return self::FAILURE;
        }

        $imageTypes = [
            'id_image',
            'vehicle_image',
            'visitor_image',
        ];

        $limit = (int) $this->option('limit');
        $count = 0;

        foreach ($imageTypes as $type) {

            $typePath = "{$basePath}/{$type}";

            if (!File::exists($typePath)) {
                $this->warn("Skipping missing type folder: {$type}");
                continue;
            }

            $visitorFolders = File::directories($typePath);

            foreach ($visitorFolders as $folderPath) {

                $visitorLogId = basename($folderPath);

                $files = File::files($folderPath);

                if (empty($files)) {
                    $this->warn("Empty folder: {$type}/{$visitorLogId}");
                    continue;
                }

                foreach ($files as $file) {

                    $tempPath = "temp/visitor/{$type}/{$visitorLogId}/{$file->getFilename()}";

                    ProcessFailedVisitorUploadImage::dispatch(
                        $visitorLogId,
                        $tempPath,
                        $type,
                        $type,
                        $file->getFilename()
                    )->onQueue('VisitorReuploadQueue');

                    $this->info("Requeued visitor {$visitorLogId} | {$type} | {$file->getFilename()}");

                    $count++;

                    if ($limit > 0 && $count >= $limit) {
                        $this->info("Limit reached: {$limit}");
                        return self::SUCCESS;
                    }
                }
            }
        }

        $this->info("Done. Total requeued: {$count}");

        return self::SUCCESS;
    }
}