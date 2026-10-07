<?php

namespace App\Console\Commands\DataDeletions;

use App\Models\VisitorLog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DataDeletionVisitorLogNotCheckoutAfterOneYear extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'data-deletion:vms
                            {--R|residence= : Only delete visitor logs belonging to this residence}
                            {--chunk=200 : Number of records to process per batch}
                            {--dry-run : Show what would be deleted without deleting anything}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete VMS visitor logs that have not been updated for more than one year.';

    private int $processed = 0;
    private int $deleted = 0;
    private int $failed = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiryDate = CarbonImmutable::now()->subYear();
        $residenceId = $this->option('residence');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = $this->buildQuery($expiryDate, $residenceId);

        $totalData = (clone $query)->count();

        $this->displaySummary(
            $totalData,
            $expiryDate,
            $residenceId,
            $chunkSize,
            $dryRun
        );

        if ($totalData === 0) {
            $this->info('No visitor logs found for deletion.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('Dry-run mode enabled. No data will be deleted.');

            return self::SUCCESS;
        }

        if (!$force && !$this->confirm(
            "Data to be deleted: {$totalData}. Do you wish to continue?"
        )) {
            $this->warn('Deletion canceled.');

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($totalData);
        $progressBar->start();

        $query->chunkById(
            $chunkSize,
            function ($visitorLogs) use ($progressBar) {
                foreach ($visitorLogs as $visitorLog) {
                    $this->processed++;

                    if ($this->deleteVisitorLog($visitorLog)) {
                        $this->deleted++;
                    } else {
                        $this->failed++;
                    }

                    $progressBar->advance();
                }
            },
            'id'
        );

        $progressBar->finish();

        $this->displayResult();

        return $this->failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Build the visitor log deletion query.
     */
    private function buildQuery(CarbonImmutable $expiryDate, ?string $residenceId): Builder 
    {
        $query = VisitorLog::query()
            ->where('updated_at', '<', $expiryDate);

        if (!empty($residenceId)) {
            $query->whereHas(
                'visitingArrangements',
                function (Builder $subQuery) use ($residenceId) {
                    $subQuery->where('residence_id', $residenceId);
                }
            );
        }

        return $query->orderBy('id');
    }

    /**
     * Delete a single visitor log and its related data.
     */
    private function deleteVisitorLog(VisitorLog $visitorLog): bool
    {
        $visitorLogId = $visitorLog->getKey();

        try {
            DB::transaction(function () use ($visitorLog) {
                /*
                 * Delete related database records first.
                 *
                 * forceDelete() is intentional because this command is
                 * performing permanent data deletion.
                 */
                $visitorLog->visitingArrangements()->forceDelete();
                $visitorLog->visitorParking()->forceDelete();

                /*
                 * Delete the visitor log itself.
                 */
                $visitorLog->forceDelete();
            });

            /*
             * Media deletion is intentionally performed AFTER the DB
             * transaction commits.
             *
             * File-system/object-storage operations cannot participate
             * in the database transaction.
             */
            $this->deleteMedia($visitorLog);

            Log::info('VMS visitor log deleted successfully.', [
                'visitor_log_id' => $visitorLogId,
            ]);

            return true;
        } catch (Throwable $exception) {
            $this->logDeletionFailure($visitorLog, $exception);

            return false;
        }
    }

    /**
     * Delete all known media associated with the visitor log.
     */
    private function deleteMedia(VisitorLog $visitorLog): void
    {
        foreach ($this->mediaCollections() as $collection) {
            try {
                $visitorLog->clearMediaCollection($collection);
            } catch (Throwable $exception) {
                /*
                 * The database record has already been deleted at this
                 * point, so media cleanup failure must be logged clearly.
                 *
                 * This should ideally be handled by a retryable cleanup
                 * mechanism in a larger system.
                 */
                Log::error('Failed to delete VMS visitor log media.', [
                    'visitor_log_id' => $visitorLog->getKey(),
                    'collection' => $collection,
                    'exception' => $exception,
                ]);

                $this->warn(
                    "Media cleanup failed for visitor log ID {$visitorLog->getKey()} "
                    . "({$collection})."
                );
            }
        }
    }

    /**
     * Media collections associated with visitor logs.
     *
     * @return array<int, string>
     */
    private function mediaCollections(): array
    {
        return [
            'id_image',
            'visitor_image',
            'vehicle_image',
            'esign_image',
        ];
    }

    /**
     * Log deletion failure with useful context.
     */
    private function logDeletionFailure(
        VisitorLog $visitorLog,
        Throwable $exception
    ): void {
        Log::error('Failed to delete VMS visitor log.', [
            'visitor_log_id' => $visitorLog->getKey(),
            'residence_id' => $this->option('residence'),
            'exception' => $exception,
        ]);

        $this->error(
            "Failed to delete visitor log ID {$visitorLog->getKey()}: "
            . $exception->getMessage()
        );
    }

    /**
     * Display deletion criteria before execution.
     */
    private function displaySummary(
        int $totalData,
        CarbonImmutable $expiryDate,
        ?string $residenceId,
        int $chunkSize,
        bool $dryRun
    ): void {
        $this->newLine();

        $this->table(
            ['Item', 'Value'],
            [
                ['Records found', $totalData],
                ['Expiry date', $expiryDate->toDateTimeString()],
                ['Residence', $residenceId ?: 'All residences'],
                ['Chunk size', $chunkSize],
                ['Mode', $dryRun ? 'DRY RUN' : 'DELETE'],
            ]
        );

        $this->newLine();
    }

    /**
     * Display final execution statistics.
     */
    private function displayResult(): void
    {
        $this->newLine(2);

        $this->table(
            ['Result', 'Count'],
            [
                ['Processed', $this->processed],
                ['Deleted', $this->deleted],
                ['Failed', $this->failed],
            ]
        );

        if ($this->failed > 0) {
            $this->error(
                "Deletion completed with {$this->failed} failed record(s). "
                . 'Please review the application logs.'
            );

            return;
        }

        $this->info(
            "Deletion complete. {$this->deleted} visitor log(s) deleted."
        );
    }
}

