<?php

namespace App\Console\Commands;

use App\Jobs\ImageProcessing\ProcessParcelImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
class RetryFailedParcelUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parcel:retry-failed-images {--limit=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry uploading failed parcel images to COS';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $basePath = storage_path('app/parcel-temp');

        if (!File::exists($basePath)) {
            $this->error("Base path not found: {$basePath}");
            return self::FAILURE;
        }

        $folders = File::directories($basePath);

        $limit = (int) $this->option('limit');
        $count = 0;

        foreach ($folders as $folderPath) {

            $parcelId = basename($folderPath);

            $files = File::files($folderPath);

            if (empty($files)) {
                $this->warn("Skipping empty folder: {$parcelId}");
                continue;
            }

            foreach ($files as $file) {

                $tempPath = "temp/parcel/{$parcelId}/" . $file->getFilename();

                ProcessParcelImage::dispatch(
                    $parcelId,
                    $tempPath,
                    'parcel',
                    'parcel_images',
                    $file->getFilename()
                )->onQueue('ParcelImageProcessing');

                $this->info("Requeued parcel {$parcelId} file {$file->getFilename()}");

                $count++;

                if ($limit > 0 && $count >= $limit) {
                    $this->info("Limit reached: {$limit}");
                    return self::SUCCESS;
                }
            }
        }

        $this->info("Done. Total requeued: {$count}");

        return self::SUCCESS;
    }
}
