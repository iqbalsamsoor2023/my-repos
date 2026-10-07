<?php

namespace App\Console\Commands\DataDeletions;

use App\Services\CloudObjectStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use League\Csv\Writer;

class CleanupOrphanCloudFolders extends Command
{
    /**
     * ⚠️ CRITICAL WARNING – DESTRUCTIVE OPERATION ⚠️
     *
     * This command PERMANENTLY DELETES data from Tencent COS.
     * Deleted files and folders CANNOT be recovered.
     *
     * Supported cleanup targets:
     * - visitor           => visitor/{visitor_id}
     * - visitor_vehicle   => visitor/vehicle/{visitor_id}
     * - pgs               => patrol_checkpoint/{checkpoint_id}
     * - irs               => house_patrol/{incident_report_id}/gallery
     *
     * SAFETY CONTROLS:
     * - --test     : safe mode, limits to 5, disables deletion
     * - --dry-run  : no deletion, logs only
     * - --limit=XX : hard limit on deletions
     * - --force    : required to run in production
     *
     * FAILURE TO FOLLOW SAFETY PROCEDURE MAY RESULT IN
     * IRREVERSIBLE DATA LOSS.
     * 
     * EXAMPLES:
     * 
     * 1. Run in test mode (safe, only 5 deletions, logs only):
     *    php artisan storage:cleanup-orphan-folders visitor --test
     *
     * 2. Run with custom limit in non-production:
     *    php artisan storage:cleanup-orphan-folders pgs --limit=500
     *
     * 3. Dry-run in production (logs only, does NOT delete):
     *    php artisan storage:cleanup-orphan-folders irs --dry-run --force
     *
     * 4. Full deletion in production (must use --force):
     *    php artisan storage:cleanup-orphan-folders visitor_vehicle --limit=20000 --force
     */
    protected $signature = 'storage:cleanup-orphan-folders
        {folder : visitor | visitor_vehicle | pgs | irs}
        {--test : Safe test mode (limit 5, no deletion)}
        {--limit=20000 : Maximum number of deletions allowed}
        {--dry-run : Log only, no deletion}
        {--force : REQUIRED to run in production environment}';

    protected $description = 'Cleanup orphan folders in Tencent COS safely';

    public function handle()
    {
        // --- ENVIRONMENT GUARD ---
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('BLOCKED: Cannot run in PRODUCTION without --force.');
            $this->warn('Use --dry-run or --test first to verify the impact.');
            return Command::FAILURE;
        }

        $folder   = $this->argument('folder');
        $testMode = $this->option('test');
        $dryRun   = $this->option('dry-run');
        $limit    = (int) $this->option('limit');

        if ($testMode) {
            $limit  = 5;
            $dryRun = true;
            $this->warn('TEST MODE ENABLED — No data will be deleted.');
        }

        $this->info("Deletion limit: {$limit}");
        if ($dryRun) {
            $this->warn('DRY-RUN MODE — Logging only.');
        }

        // --- FOLDER MAP ---
        $map = [
            'visitor'         => 'visitor',
            'visitor_vehicle' => 'visitor/vehicle',
            'pgs'             => 'patrol_checkpoint',
            'irs'             => 'house_patrol',
        ];

        if (!isset($map[$folder])) {
            $this->error("Unsupported folder: {$folder}");
            return Command::FAILURE;
        }

        $folderType = $map[$folder];
        $rootPath   = config('app.path.cos') . '/' . $folderType;
        $disk       = Storage::disk('cos');
        $cosClient  = CloudObjectStorageService::execute();

        $deletedCount = 0;
        $deletedIds   = [];

        // --- AUDIT LOGGING ---
        $auditContext = [
            'command'     => $this->getName(),
            'arguments'   => $this->arguments(),
            'options'     => $this->options(),
            'environment' => app()->environment(),
            'executed_by' => get_current_user() ?: 'unknown',
            'hostname'    => gethostname(),
            'ip_address'  => gethostbyname(gethostname()), // <-- server IP
            'timestamp'   => now()->toDateTimeString(),
        ];

        Log::channel('audit')->warning('ORPHAN CLOUD CLEANUP STARTED', $auditContext);

        $bar = $this->output->createProgressBar($limit ?: null); // null if no limit
        $bar->start();

        $this->processFolders($cosClient, $rootPath, function ($folderPath, &$stopProcessing) use (
            $disk,
            $cosClient,
            $folderType,
            &$deletedCount,
            &$deletedIds,
            $limit,
            $dryRun,
            $bar
        ) {
            if ($stopProcessing) return;

            $id = basename($folderPath);

            // skip vehicle folders if visitor
            if ($folderType === 'visitor' && (str_starts_with($id, 'vehicle') || str_contains($folderPath, '/vehicle/'))) {
                $this->info("Skipping vehicle folder: {$folderPath}");
                return;
            }

            // check DB
            $exists = false;
            if (in_array($folderType, ['visitor', 'visitor/vehicle'])) {
                $exists = DB::table('visitor_logs')->where('id', $id)->exists()
                    || DB::table('visitor_logs_archive')->where('id', $id)->exists();
            } elseif ($folderType === 'patrol_checkpoint') {
                $exists = DB::connection('sgoc')->table('checkpoint_logs')->where('id', $id)->exists();
            } elseif ($folderType === 'house_patrol') {
                $exists = DB::connection('sgoc')->table('incident_reports')->where('id', $id)->exists();
            }

            if (!$exists) {
                $deletedIds[] = $id;

                if (!$dryRun) {
                    $targetPath = $folderType === 'house_patrol' ? rtrim($folderPath, '/') . '/gallery' : $folderPath;
                    $this->info("Deleting folder: {$targetPath}");
                    $this->deleteFolder($disk, $cosClient, $targetPath);
                } else {
                    $this->info("[DRY-RUN] Would delete: {$folderPath}");
                }

                $deletedCount++;
                $bar->advance();

                // stop further processing if limit reached
                if ($deletedCount >= $limit) {
                    $this->warn("Deletion limit ({$limit}) reached. Stopping.");
                    $stopProcessing = true;
                }
            } else {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        // --- CSV REPORT ---
        if (!empty($deletedIds) && !$dryRun) {
            $csv = Writer::createFromFileObject(new \SplTempFileObject());
            $csv->insertOne(['folder_type', 'deleted_id', 'timestamp']);
            foreach ($deletedIds as $id) {
                $csv->insertOne([$folderType, $id, now()->toDateTimeString()]);
            }

            $csvPath = storage_path("logs/deleted_{$folderType}_" . now()->format('Ymd_His') . ".csv");
            file_put_contents($csvPath, $csv->getContent());

            Log::info("Deleted {$folderType} IDs", $deletedIds);

            // Send production email alert if not dry-run
            if (app()->environment('production') && !$dryRun && $to = env('AUDIT_EMAIL')) {
                $idsHtml = '<ul>';
                foreach ($deletedIds as $id) {
                    $idsHtml .= "<li>{$id}</li>";
                }
                $idsHtml .= '</ul>';

                try {
                    Mail::html(
                        "<h3>Orphan Cloud Cleanup Completed</h3>
                        <p><strong>Folder Type:</strong> {$folderType}</p>
                        <p><strong>Total Folders Deleted:</strong> {$deletedCount}</p>
                        <p>The CSV report is attached for full details.</p>",
                        function ($message) use ($to, $csvPath) {
                            $message->to($to)
                                ->subject('PRODUCTION ORPHAN CLOUD CLEANUP REPORT')
                                ->attach($csvPath);
                        }
                    );
                } catch (\Exception $e) {
                    Log::error("Failed to send cleanup email: " . $e->getMessage());
                }

                // safely delete
                if (file_exists($csvPath)) {
                    unlink($csvPath);
                    Log::info("Temporary CSV report deleted: {$csvPath}");
                }
            }
        }

        $this->info("Cleanup completed. Total affected folders: {$deletedCount}");
        Log::channel('audit')->warning('ORPHAN CLOUD CLEANUP COMPLETED', [
            'deleted_count' => $deletedCount,
            'deleted_ids'   => $deletedIds,
        ]);
    }

    protected function processFolders($cosClient, string $prefix, callable $callback)
    {
        $bucket = env('COS_BUCKET') . '-' . env('COS_APP_ID');
        $marker = '';
        $stopProcessing = false;

        do {
            $result = $cosClient->listObjects([
                'Bucket'    => $bucket,
                'Prefix'    => rtrim($prefix, '/') . '/',
                'Delimiter' => '/',
                'Marker'    => $marker,
                'MaxKeys'   => 1000,
            ]);

            foreach ($result['CommonPrefixes'] ?? [] as $p) {
                $callback(rtrim($p['Prefix'], '/'), $stopProcessing);

                if ($stopProcessing) {
                    break 2; // breaks both foreach and do-while
                }
            }

            $marker = $result['NextMarker'] ?? '';
        } while ($marker);
    }

    protected function deleteFolder($disk, $cosClient, $folder)
    {
        $files = $disk->allFiles($folder);
        if ($files) {
            $disk->delete($files);
        }

        $bucket = env('COS_BUCKET') . '-' . env('COS_APP_ID');

        $objects = $cosClient->listObjects([
            'Bucket'  => $bucket,
            'Prefix'  => rtrim($folder, '/') . '/',
            'MaxKeys' => 1,
        ]);

        $isEmpty = true;

        if (!empty($objects['Contents'])) {
            foreach ($objects['Contents'] as $obj) {
                // Ignore 0-byte placeholder key
                if ($obj['Size'] > 0) {
                    $isEmpty = false;
                    break;
                }
            }
        }

        if ($isEmpty) {
            try {
                $cosClient->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => rtrim($folder, '/') . '/', // delete placeholder
                ]);
                $this->info("Deleted empty folder: $folder");
            } catch (\Exception $e) {
                $this->error("Failed to delete folder placeholder for $folder: " . $e->getMessage());
            }                   
        }
    }
}
