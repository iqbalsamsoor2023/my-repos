<?php

namespace App\Console\Commands\DataDeletions;

use App\Models\VisitorLog;
use App\Models\VisitorLogArchive;
use App\Models\VisitorParking;
use App\Models\VisitorParkingArchive;
use App\Services\CloudObjectStorageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Pulse\Facades\Pulse;
use Throwable;

class DataDeletionVmsArchive extends Command
{
    /**
     * Usage examples:
     *   php artisan data-deletion:vms-archive --dry-run
     *   php artisan data-deletion:vms-archive --force
     *   php artisan data-deletion:vms-archive --residence=3019
     *   php artisan data-deletion:vms-archive --from=2024-01-01 --to=2025-01-01
     *   php artisan data-deletion:vms-archive --residence=3019 --dry-run
     *   php artisan data-deletion:vms-archive --force --max-runtime=3600  (scheduler)
     *   php artisan data-deletion:vms-archive --force --skip-cos          (local only)
     *
     * Logging: one line per run to storage/logs/vms-<date>.log (channel `vms`, see
     * config/logging.php) — final deleted/matched totals on success, nothing on
     * --dry-run, errors otherwise. See docs/archive/ARCHIVE_README.md.
     */
    protected $signature = 'data-deletion:vms-archive
        {--R|residence= : Filter by residence ID}
        {--from= : Only delete records last updated on or after this date (Y-m-d)}
        {--to= : Delete records last updated before this date (Y-m-d, defaults to 1 year ago)}
        {--retention-months=12 : Retention period in months when --to is not specified}
        {--chunk=500 : Records per deletion batch}
        {--skip-cos : Delete DB rows only; do not touch COS cloud storage files}
        {--max-runtime=0 : Stop gracefully after N seconds (0 = no limit). Safe to resume next run.}
        {--force : Skip confirmation prompts (use for scheduler)}
        {--dry-run : Preview matching record count without deleting}';

    protected $description = 'Delete VMS archive records older than 1 year with related data and cloud storage cleanup.';

    /**
     * COS caps a batch delete (DeleteObjects) at 1000 keys per request.
     */
    private const COS_DELETE_BATCH = 1000;

    private int $deletedCount = 0;

    private float $startedAt = 0.0;

    private bool $stoppedOnTimeLimit = false;

    public function handle(): int
    {
        DB::connection()->disableQueryLog();
        $this->stopPulseRecording();

        $this->startedAt = microtime(true);

        $cutoffDate = $this->resolveCutoffDate();
        $fromDate = $this->option('from') !== null ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $residenceId = $this->option('residence') !== null ? (int) $this->option('residence') : null;
        $chunkSize = max(100, (int) $this->option('chunk'));
        $maxRuntime = max(0, (int) $this->option('max-runtime'));
        $isDryRun = (bool) $this->option('dry-run');
        $isForced = (bool) $this->option('force');
        $skipCos = (bool) $this->option('skip-cos');

        $validationError = $this->validateOptions($fromDate, $cutoffDate);

        if ($validationError !== null) {
            $this->error($validationError);

            return self::FAILURE;
        }

        $this->printHeader($cutoffDate, $fromDate, $residenceId, $isDryRun);

        $total = $this->countMatchingRecords($cutoffDate, $fromDate, $residenceId);

        if ($total === 0) {
            $this->info('No records found matching the criteria.');
            Log::channel('vms')->info('data-deletion:vms-archive completed', ['deleted' => 0, 'matched' => 0]);

            return self::SUCCESS;
        }

        $this->line("Records to process: {$total}");
        $this->newLine();

        if ($skipCos) {
            $this->warn('--skip-cos: COS cloud storage files will NOT be deleted (DB rows only).');
        }

        if ($maxRuntime > 0) {
            $this->line("Runtime limit: {$maxRuntime}s (stops at a chunk boundary; resume by running again).");
        }

        if ($isDryRun) {
            $this->warn("[DRY RUN] {$total} records would be deleted. No changes made.");

            return self::SUCCESS;
        }

        if (! $isForced && ! $this->confirm("Delete {$total} archive records? This cannot be undone.")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $this->performDeletionDayByDay($cutoffDate, $fromDate, $residenceId, $chunkSize, $total, $skipCos, $maxRuntime);

        $this->newLine();

        if ($this->stoppedOnTimeLimit) {
            $remaining = max(0, $total - $this->deletedCount);
            $this->warn(sprintf(
                'Stopped after the %ds runtime limit. Deleted %d of %d records; %d remain.',
                $maxRuntime,
                $this->deletedCount,
                $total,
                $remaining
            ));
            $this->line('This is not an error — the next run resumes from the oldest remaining record.');

            Log::channel('vms')->info('data-deletion:vms-archive stopped (runtime limit)', [
                'deleted' => $this->deletedCount,
                'matched' => $total,
                'remaining' => $remaining,
            ]);

            return self::SUCCESS;
        }

        $this->info("Completed. Deleted: {$this->deletedCount} records.");

        Log::channel('vms')->info('data-deletion:vms-archive completed', [
            'deleted' => $this->deletedCount,
            'matched' => $total,
        ]);

        return self::SUCCESS;
    }

    /**
     * True once the run has exceeded its runtime budget. Checked only at chunk
     * boundaries (after a commit), so stopping never leaves a half-deleted record.
     */
    private function runtimeExceeded(int $maxRuntime): bool
    {
        return $maxRuntime > 0 && (microtime(true) - $this->startedAt) >= $maxRuntime;
    }

    /**
     * Pulse's SlowQueries recorder buffers slow queries in memory (full SQL text, no
     * length cap) and only flushes at shutdown. This command issues large whereIn
     * deletes across millions of archive rows, so the buffer grows until PHP dies of
     * memory exhaustion — silently, because Laravel forces display_errors=Off. Bulk
     * maintenance does not belong in Pulse.
     */
    private function stopPulseRecording(): void
    {
        try {
            if (class_exists(Pulse::class)) {
                Pulse::stopRecording();
            }
        } catch (Throwable) {
            // Pulse is optional; never let telemetry break the deletion run.
        }
    }

    private function validateOptions(?Carbon $fromDate, Carbon $cutoffDate): ?string
    {
        if ($fromDate !== null && $fromDate->gte($cutoffDate)) {
            return sprintf(
                'Invalid date range: --from (%s) must be earlier than cutoff date (%s).',
                $fromDate->toDateString(),
                $cutoffDate->toDateString()
            );
        }

        return null;
    }

    private function countMatchingRecords(Carbon $cutoffDate, ?Carbon $fromDate, ?int $residenceId): int
    {
        $sql = 'SELECT COUNT(*) AS aggregate FROM visitor_logs_archive WHERE updated_at < ?';
        $bindings = [$cutoffDate->toDateTimeString()];

        if ($fromDate !== null) {
            $sql .= ' AND updated_at >= ?';
            $bindings[] = $fromDate->toDateTimeString();
        }

        if ($residenceId !== null) {
            $sql .= ' AND residence_id = ?';
            $bindings[] = $residenceId;
        }

        return (int) (DB::selectOne($sql, $bindings)->aggregate ?? 0);
    }

    private function resolveCutoffDate(): Carbon
    {
        if ($to = $this->option('to')) {
            return Carbon::parse($to)->startOfDay();
        }

        $retentionMonths = max(1, (int) $this->option('retention-months'));

        return now()->subMonths($retentionMonths)->startOfDay();
    }

    private function performDeletionDayByDay(
        Carbon $cutoffDate,
        ?Carbon $fromDate,
        ?int $residenceId,
        int $chunkSize,
        int $totalToProcess,
        bool $skipCos = false,
        int $maxRuntime = 0
    ): void {
        $startDate = $fromDate ?? $this->resolveOldestMatchingDate($cutoffDate, $residenceId);

        if ($startDate === null) {
            return;
        }

        // Only touch COS when we actually intend to delete cloud files.
        $disk = $skipCos ? null : Storage::disk('cos');
        $cosClient = $skipCos ? null : CloudObjectStorageService::execute();
        $bucket = $skipCos ? '' : config('filesystems.disks.cos.bucket').'-'.config('filesystems.disks.cos.app_id');

        // app.path.cos may legitimately be empty (files then live at "visitor/{id}/").
        // Trim it so the prefix we hand to listObjects/deleteObjects matches exactly
        // where Flysystem wrote the objects — a leading slash silently matches nothing.
        $cosPath = trim((string) config('app.path.cos'), '/');

        $bar = $this->output->createProgressBar($totalToProcess);
        $bar->start();

        $dayCursor = $startDate->copy()->startOfDay();
        $endExclusive = $cutoffDate->copy()->startOfDay();

        while ($dayCursor->lt($endExclusive)) {
            $nextDay = $dayCursor->copy()->addDay();
            $dayDeletedCount = 0;

            DB::table('visitor_logs_archive')
                ->select('id')
                ->where('updated_at', '>=', $dayCursor)
                ->where('updated_at', '<', $nextDay)
                ->when($residenceId !== null, fn ($query) => $query->where('residence_id', $residenceId))
                ->chunkById($chunkSize, function (Collection $chunk) use ($disk, $cosClient, $bucket, $cosPath, $skipCos, $maxRuntime, $bar, &$dayDeletedCount): bool {
                    $archiveIds = $chunk->pluck('id')->all();

                    $this->deleteChunk($archiveIds, $disk, $cosClient, $bucket, $cosPath, $skipCos);

                    $count = count($archiveIds);
                    $dayDeletedCount += $count;
                    $bar->advance($count);

                    // Checked only here: the chunk's transaction has committed, so
                    // stopping now can never leave a record half-deleted.
                    if ($this->runtimeExceeded($maxRuntime)) {
                        $this->stoppedOnTimeLimit = true;

                        return false; // halts chunkById
                    }

                    return true;
                });

            if ($dayDeletedCount > 0) {
                $this->line('');
                $this->line("Deleted {$dayDeletedCount} records for {$dayCursor->toDateString()}");
            }

            if ($this->stoppedOnTimeLimit) {
                break;
            }

            $dayCursor = $nextDay;
        }

        // finish() snaps the bar to 100%, which would misreport a partial run.
        if (! $this->stoppedOnTimeLimit) {
            $bar->finish();
        }

        $this->newLine();
    }

    private function resolveOldestMatchingDate(Carbon $cutoffDate, ?int $residenceId): ?Carbon
    {
        $sql = 'SELECT MIN(updated_at) AS oldest_updated_at FROM visitor_logs_archive WHERE updated_at < ?';
        $bindings = [$cutoffDate->toDateTimeString()];

        if ($residenceId !== null) {
            $sql .= ' AND residence_id = ?';
            $bindings[] = $residenceId;
        }

        $result = DB::selectOne($sql, $bindings);

        if (! $result || ! $result->oldest_updated_at) {
            return null;
        }

        return Carbon::parse((string) $result->oldest_updated_at)->startOfDay();
    }

    /**
     * @param  list<int>  $archiveIds
     */
    private function deleteChunk(
        array $archiveIds,
        mixed $disk,
        mixed $cosClient,
        string $bucket,
        string $cosPath,
        bool $skipCos = false
    ): void {
        // Parking IDs are needed both for COS paths and for their media rows, and
        // they are gone once the archive row is deleted — so resolve them up front.
        $parkingIds = DB::table('visitor_parkings_archive')
            ->whereIn('visitor_log_id', $archiveIds)
            ->pluck('id')
            ->all();

        // COS cleanup runs BEFORE the DB delete on purpose. If it ran after and then
        // crashed, the objects would be unreachable forever (their keys are derived
        // from row IDs that no longer exist). Doing it first means a crash leaves the
        // row in place with missing files, and the next run simply deletes it again.
        if (! $skipCos) {
            $this->purgeCosObjects($disk, $cosClient, $bucket, $cosPath, $archiveIds, $parkingIds);
        }

        DB::beginTransaction();

        try {
            // Bulk delete related records — one query per table, not per record
            DB::table('visiting_arrangements_archive')->whereIn('visitor_log_id', $archiveIds)->delete();
            DB::table('visitor_parkings_archive')->whereIn('visitor_log_id', $archiveIds)->delete();

            // Media is attached while the row still lives in visitor_logs, and the
            // archive command never rewrites media.model_type (see
            // VisitorLogArchive::originalMedias()). Matching only the archive class
            // would delete nothing and leave orphaned media rows behind, so match
            // both the live and archive model types.
            DB::table('media')
                ->whereIn('model_type', [VisitorLog::class, VisitorLogArchive::class])
                ->whereIn('model_id', $archiveIds)
                ->delete();

            // Parking vouchers are attached to the parking row, not the log.
            if ($parkingIds !== []) {
                DB::table('media')
                    ->whereIn('model_type', [VisitorParking::class, VisitorParkingArchive::class])
                    ->whereIn('model_id', $parkingIds)
                    ->delete();
            }

            DB::table('visitor_logs_archive')->whereIn('id', $archiveIds)->delete();

            DB::commit();

            $this->deletedCount += count($archiveIds);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::channel('vms')->error('VMS archive deletion failed', [
                'id_range' => [reset($archiveIds), end($archiveIds)],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete every COS object belonging to this chunk.
     *
     * Only records that actually have media own COS objects, and the media row tells
     * us exactly which directory the path generator used (see CustomPathGenerator).
     * So we never touch COS for a record with no media — which is where the old
     * implementation burned ~9 round trips per record listing folders that were
     * always empty.
     *
     * @param  list<int>  $archiveIds
     * @param  list<int>  $parkingIds
     */
    private function purgeCosObjects(
        mixed $disk,
        mixed $cosClient,
        string $bucket,
        string $cosPath,
        array $archiveIds,
        array $parkingIds
    ): void {
        foreach ($this->resolveCosDirectories($cosPath, $archiveIds, $parkingIds) as $directory) {
            $this->purgeCosDirectory($disk, $cosClient, $bucket, $directory);
        }
    }

    /**
     * Directories that may hold objects for this chunk, derived from the media rows.
     *
     * CustomPathGenerator maps VisitorLog media to:
     *   vehicle_image -> {cosPath}/visitor/vehicle/{id}
     *   pdpa_esign    -> {cosPath}/visitor/{id}/pdpa-sign   (nested, covered below)
     *   otherwise     -> {cosPath}/visitor/{id}
     * and VisitorParking voucher_image to {cosPath}/parking-fees/{parkingId}.
     *
     * `{cosPath}/visitor/{id}` is listed recursively, so it already covers the nested
     * pdpa-sign directory — no separate prefix is needed for it.
     *
     * @param  list<int>  $archiveIds
     * @param  list<int>  $parkingIds
     * @return list<string>
     */
    private function resolveCosDirectories(string $cosPath, array $archiveIds, array $parkingIds): array
    {
        $prefix = $cosPath === '' ? '' : $cosPath.'/';
        $directories = [];

        $logMedia = DB::table('media')
            ->whereIn('model_type', [VisitorLog::class, VisitorLogArchive::class])
            ->whereIn('model_id', $archiveIds)
            ->selectRaw("model_id, JSON_UNQUOTE(JSON_EXTRACT(custom_properties, '$.type')) AS media_type")
            ->get();

        foreach ($logMedia as $media) {
            $directories[] = $media->media_type === 'vehicle_image'
                ? "{$prefix}visitor/vehicle/{$media->model_id}"
                : "{$prefix}visitor/{$media->model_id}";
        }

        if ($parkingIds !== []) {
            $parkingMediaIds = DB::table('media')
                ->whereIn('model_type', [VisitorParking::class, VisitorParkingArchive::class])
                ->whereIn('model_id', $parkingIds)
                ->distinct()
                ->pluck('model_id');

            foreach ($parkingMediaIds as $parkingId) {
                $directories[] = "{$prefix}parking-fees/{$parkingId}";
            }
        }

        return array_values(array_unique($directories));
    }

    /**
     * Recursively remove one directory: a single LIST, then batched DeleteObjects
     * (COS caps a batch at 1000 keys). Listing recursively guarantees conversions and
     * responsive images go too, so no orphan objects are left behind.
     */
    private function purgeCosDirectory(mixed $disk, mixed $cosClient, string $bucket, string $directory): void
    {
        $directory = trim($directory, '/');

        // A blank or single-segment prefix would match a huge slice of the bucket.
        // Never issue a delete we cannot prove is scoped to one record.
        if ($directory === '' || substr_count($directory, '/') < 1) {
            Log::channel('vms')->warning('COS cleanup skipped: refusing an unsafe prefix', ['directory' => $directory]);

            return;
        }

        try {
            $files = $disk->allFiles($directory); // one recursive LIST

            foreach (array_chunk($files, self::COS_DELETE_BATCH) as $batch) {
                $cosClient->deleteObjects([
                    'Bucket' => $bucket,
                    'Objects' => array_map(static fn (string $key): array => ['Key' => $key], $batch),
                    'Quiet' => true,
                ]);
            }

            // Drop the directory placeholder object the driver may have created.
            $cosClient->deleteObject(['Bucket' => $bucket, 'Key' => $directory.'/']);
        } catch (Throwable $e) {
            Log::channel('vms')->warning('COS folder cleanup failed', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function printHeader(Carbon $cutoffDate, ?Carbon $fromDate, ?int $residenceId, bool $isDryRun): void
    {
        $this->info('VMS Archive Deletion');
        $this->line('Mode: day-by-day deletion (by updated_at)');
        $this->line('Delete records last updated before: '.$cutoffDate->toDateString());

        if ($fromDate !== null) {
            $this->line('From: '.$fromDate->toDateString());
        }

        if ($residenceId !== null) {
            $this->line("Residence: {$residenceId}");
        }

        if ($isDryRun) {
            $this->warn('Dry run mode — no data will be deleted.');
        }

        $this->newLine();
    }
}
