<?php

namespace App\Console\Commands\DataDeletions;

use App\Models\CheckpointLog;
use App\Services\CloudObjectStorageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class DataDeletionPgs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example usage:
     *  php artisan data-deletion:pgs
     *  php artisan data-deletion:pgs --residence_id=3019
     *  php artisan data-deletion:pgs -R 3019
     *
     * @var string
     */
    protected $signature = 'data-deletion:pgs {--R|residence_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run data deletion SOP for PGS module.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $expiryDate = Carbon::now()->subMonths(3)->toDateString();
        $residenceId = $this->option('residence_id');

        $query = CheckpointLog::whereDate('updated_at', '<', $expiryDate);

        // Only filter by residence if -R / --residence_id is provided
        if ($residenceId) {
            $query->whereHas('checkpoint', function ($q) use ($residenceId) {
                $q->where('mmb_residence_id', $residenceId);
            });
        }

        $totalData = $query->count();

        if ($totalData === 0) {
            $msg = $residenceId
                ? "No PGS records found for residence ID {$residenceId} before $expiryDate."
                : "No PGS records to delete before $expiryDate.";
            $this->info($msg);
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalData);

        $proceed = true;
        if ($residenceId) {
            $proceed = $this->confirm("Data to be deleted: $totalData (before $expiryDate). Do you wish to continue?");
        }

        if ($proceed) {
            $bar->start();

            $query->chunk(500, function ($checkpointLogs) use ($bar) {
                foreach ($checkpointLogs as $checkpointLog) {
                    $this->performTask($checkpointLog);
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
            $this->info("Deleted $totalData PGS records.");

            return Command::SUCCESS;
        }

        $this->info("Operation cancelled.");
        return Command::SUCCESS;
    }


    public function performTask(CheckpointLog $checkpointLog): void
    {
        if (! $checkpointLog) {
            return;
        }
        DB::beginTransaction();

        try {
            $checkpointLog->clearMediaCollection();
            $this->deleteCheckpointFolder($checkpointLog);
            $checkpointLog->forceDelete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("PGS deletion failed for ID {$checkpointLog->id}: " . $e->getMessage());
        }
    }

    protected function deleteCheckpointFolder(CheckpointLog $checkpointLog): void
    {
        try {
            $disk = Storage::disk('cos');
            $cosClient = CloudObjectStorageService::execute();

            $bucketName = env('COS_BUCKET');
            $appId = env('COS_APP_ID');
            $bucket = $bucketName . '-' . $appId;

            $folder = config('app.path.cos') . "/patrol_checkpoint/{$checkpointLog->id}";

            // Step 1: Delete all files inside the folder
            $files = $disk->allFiles($folder);

            if (!empty($files)) {
                $disk->delete($files);
            }

            // Step 2: Check if folder is empty
            $result = $cosClient->listObjects([
                'Bucket' => $bucket,
                'Prefix' => rtrim($folder, '/') . '/',
                'MaxKeys' => 1,
            ]);

            $isEmpty = true;

            if (!empty($result['Contents'])) {
                foreach ($result['Contents'] as $obj) {
                    if ($obj['Size'] > 0) {
                        $isEmpty = false;
                        break;
                    }
                }
            }

            // Step 3: Delete placeholder folder object
            if ($isEmpty) {
                $cosClient->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => rtrim($folder, '/') . '/', // delete placeholder
                ]);
            }

            Log::info("Deleted COS folder for CheckpointLog ID: {$checkpointLog->id}");
        } catch (\Exception $e) {
            Log::error("Failed deleting COS folder for CheckpointLog ID {$checkpointLog->id}: {$e->getMessage()}");
        }
    }
}
