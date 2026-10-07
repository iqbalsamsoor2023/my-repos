<?php

namespace App\Console\Commands\Vms;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CleanupOrphanVisitorsTable extends Command
{
    protected $signature = 'visitors:cleanup-orphans-visitor-table
        {--dry-run : Only report orphan visitors. Nothing will be deleted}
        {--batch=10000 : Number of visitors processed per batch}
        {--limit=0 : Maximum number of visitors to delete. 0 means unlimited}
        {--sleep=100 : Sleep between batches in milliseconds}
        {--force : Skip deletion confirmation}
        {--start-id= : Start after this visitors.id}
        {--show-sample=20 : Number of orphan visitor IDs to show during dry-run}';

    protected $description = 'Delete visitors whose ID does not exist in any visitor reference table';

    /**
     * Email that receives the cleanup report.
     */
    private string $reportEmail = 'zawanah.saifudin@mymooban.co.th';

    /**
     * Tables that reference visitors.id using visitor_id.
     */
    private array $referenceTables = [
        'blacklisted_visitors',
        'preregister_visitors',
        'visitor_logs',
        'visitor_logs_archive',
    ];

    /**
     * Target table.
     */
    private string $visitorTable = 'visitors';

    /**
     * Target primary key.
     */
    private string $visitorPrimaryKey = 'id';

    /**
     * Reference column.
     */
    private string $referenceColumn = 'visitor_id';

    /**
     * CSV file handle.
     *
     * This prevents keeping millions of IDs in PHP memory.
     */
    private $deletedIdsFileHandle = null;

    /**
     * CSV file path.
     */
    private ?string $deletedIdsFilePath = null;

    /**
     * Number of IDs written to CSV.
     */
    private int $deletedIdsWritten = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $batchSize = max(
            1,
            (int) $this->option('batch')
        );

        $limit = max(
            0,
            (int) $this->option('limit')
        );

        $sleep = max(
            0,
            (int) $this->option('sleep')
        );

        $showSample = max(
            0,
            (int) $this->option('show-sample')
        );

        $startId = $this->option('start-id');

        $this->displayHeader(
            $dryRun,
            $batchSize,
            $limit,
            $sleep,
            $startId
        );

        // ---------------------------------------------------------
        // Validate database structure
        // ---------------------------------------------------------

        try {
            $this->validateDatabase();
        } catch (Throwable $e) {
            $this->error('Database validation failed.');
            $this->error($e->getMessage());

            Log::error(
                'Orphan visitor cleanup validation failed',
                [
                    'exception' => $e,
                ]
            );

            return self::FAILURE;
        }

        // ---------------------------------------------------------
        // DRY RUN
        // ---------------------------------------------------------

        if ($dryRun) {
            return $this->runDryRun(
                $showSample,
                $startId
            );
        }

        // ---------------------------------------------------------
        // DELETE
        // ---------------------------------------------------------

        return $this->runDelete(
            $batchSize,
            $limit,
            $sleep,
            $startId
        );
    }

    /**
     * Display command configuration.
     */
    private function displayHeader(
        bool $dryRun,
        int $batchSize,
        int $limit,
        int $sleep,
        ?string $startId
    ): void {
        $this->newLine();

        $this->line(
            '======================================================'
        );

        $this->line(
            '        ORPHAN VISITOR CLEANUP'
        );

        $this->line(
            '======================================================'
        );

        $this->line(
            'Mode           : ' .
            ($dryRun ? 'DRY RUN' : 'DELETE')
        );

        $this->line(
            'Target table   : visitors'
        );

        $this->line(
            'Target column  : id'
        );

        $this->line(
            'Batch size     : ' .
            number_format($batchSize)
        );

        $this->line(
            'Delete limit   : ' .
            (
                $limit > 0
                    ? number_format($limit)
                    : 'UNLIMITED'
            )
        );

        $this->line(
            'Sleep          : ' .
            $sleep .
            ' ms'
        );

        $this->line(
            'Start ID       : ' .
            (
                $startId !== null
                    ? $startId
                    : 'BEGINNING'
            )
        );

        $this->line(
            'Reference      : ' .
            implode(', ', $this->referenceTables)
        );

        $this->line(
            'Report email   : ' .
            $this->reportEmail
        );

        $this->line(
            '======================================================'
        );

        $this->newLine();
    }

    /**
     * Validate tables and required indexes.
     */
    private function validateDatabase(): void
    {
        $schema = DB::getSchemaBuilder();

        // ---------------------------------------------------------
        // Validate tables
        // ---------------------------------------------------------

        $tables = array_merge(
            [$this->visitorTable],
            $this->referenceTables
        );

        foreach ($tables as $table) {
            if (! $schema->hasTable($table)) {
                throw new \RuntimeException(
                    "Required table does not exist: {$table}"
                );
            }
        }

        $this->info(
            'Database tables: OK'
        );

        // ---------------------------------------------------------
        // Validate visitors.id
        // ---------------------------------------------------------

        $visitorColumns = $schema->getColumnListing(
            $this->visitorTable
        );

        if (! in_array(
            $this->visitorPrimaryKey,
            $visitorColumns,
            true
        )) {
            throw new \RuntimeException(
                'Column visitors.id does not exist.'
            );
        }

        $this->info(
            'Column visitors.id: OK'
        );

        // ---------------------------------------------------------
        // Validate reference visitor_id columns
        // ---------------------------------------------------------

        foreach ($this->referenceTables as $table) {
            $columns = $schema->getColumnListing(
                $table
            );

            if (! in_array(
                $this->referenceColumn,
                $columns,
                true
            )) {
                throw new \RuntimeException(
                    "Column {$table}.{$this->referenceColumn} does not exist."
                );
            }
        }

        $this->info(
            'Reference visitor_id columns: OK'
        );

        // ---------------------------------------------------------
        // Validate indexes
        // ---------------------------------------------------------

        $this->validateVisitorIdIndexes();

        $this->info(
            'Required visitor_id indexes: OK'
        );

        $this->newLine();
    }

    /**
     * Validate indexes.
     */
    private function validateVisitorIdIndexes(): void
    {
        $tables = array_merge(
            [$this->visitorTable],
            $this->referenceTables
        );

        foreach ($tables as $table) {
            $indexes = DB::select(
                "SHOW INDEX FROM `{$table}`"
            );

            $requiredColumn =
                $table === $this->visitorTable
                    ? $this->visitorPrimaryKey
                    : $this->referenceColumn;

            $hasIndex = false;

            foreach ($indexes as $index) {
                /*
                 * We require the column to be the FIRST
                 * column in the index.
                 */
                if (
                    isset($index->Column_name) &&
                    isset($index->Seq_in_index) &&
                    $index->Column_name === $requiredColumn &&
                    (int) $index->Seq_in_index === 1
                ) {
                    $hasIndex = true;

                    break;
                }
            }

            if (! $hasIndex) {
                throw new \RuntimeException(
                    "Missing index beginning with {$table}.{$requiredColumn}. " .
                    "Create the index before running cleanup."
                );
            }
        }
    }

    /**
     * DRY RUN.
     */
    private function runDryRun(
        int $showSample,
        ?string $startId
    ): int {
        $this->info(
            'Running dry-run...'
        );

        $this->newLine();

        $startedAt = microtime(true);

        // ---------------------------------------------------------
        // Count orphan visitors
        // ---------------------------------------------------------

        $count = $this->buildOrphanQuery(
            $startId
        )->count('v.id');

        $elapsed = round(
            microtime(true) - $startedAt,
            2
        );

        $this->info(
            'Orphan visitors found: ' .
            number_format($count)
        );

        $this->info(
            'Count query time: ' .
            $elapsed .
            ' seconds'
        );

        // ---------------------------------------------------------
        // Show sample
        // ---------------------------------------------------------

        if (
            $count > 0 &&
            $showSample > 0
        ) {
            $this->newLine();

            $this->info(
                "Sample of {$showSample} orphan visitor IDs:"
            );

            $sample = $this->buildOrphanQuery(
                $startId
            )
                ->select('v.id')
                ->orderBy('v.id')
                ->limit($showSample)
                ->pluck('id');

            foreach ($sample as $visitorId) {
                $this->line(
                    '  ' . $visitorId
                );
            }
        }

        $this->newLine();

        if ($count === 0) {
            $this->info(
                'No orphan visitors found.'
            );
        } else {
            $this->warn(
                'DRY RUN ONLY - NO DATA WAS DELETED.'
            );

            $this->warn(
                'No email report was sent because nothing was deleted.'
            );
        }

        return self::SUCCESS;
    }

    /**
     * Production deletion.
     */
    private function runDelete(
        int $batchSize,
        int $limit,
        int $sleep,
        ?string $startId
    ): int {
        // ---------------------------------------------------------
        // Initial count
        // ---------------------------------------------------------

        $this->info(
            'Checking orphan visitor count...'
        );

        $countStartedAt = microtime(true);

        $totalCandidates = $this->buildOrphanQuery(
            $startId
        )->count('v.id');

        $countTime = round(
            microtime(true) - $countStartedAt,
            2
        );

        $this->info(
            'Orphan visitors currently found: ' .
            number_format($totalCandidates)
        );

        $this->info(
            'Count query time: ' .
            $countTime .
            ' seconds'
        );

        if ($totalCandidates === 0) {
            $this->info(
                'Nothing to delete.'
            );

            return self::SUCCESS;
        }

        // ---------------------------------------------------------
        // Calculate maximum
        // ---------------------------------------------------------

        $maximumToDelete = $limit > 0
            ? min(
                $limit,
                $totalCandidates
            )
            : $totalCandidates;

        $this->newLine();

        $this->warn(
            'This operation can DELETE up to ' .
            number_format($maximumToDelete) .
            ' records from visitors.'
        );

        $this->warn(
            'A visitor is deleted ONLY when its visitors.id does ' .
            'not exist in any of the four reference tables.'
        );

        $this->newLine();

        // ---------------------------------------------------------
        // Confirmation
        // ---------------------------------------------------------

        if (! $this->option('force')) {
            if (! $this->confirm(
                'Are you sure you want to continue?'
            )) {
                $this->warn(
                    'Operation cancelled.'
                );

                return self::SUCCESS;
            }
        }

        // ---------------------------------------------------------
        // Prepare deletion report CSV
        // ---------------------------------------------------------

        try {
            $this->initializeDeletedIdsFile();
        } catch (Throwable $e) {
            $this->error(
                'Unable to create deletion report file.'
            );

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }

        // ---------------------------------------------------------
        // Begin deletion
        // ---------------------------------------------------------

        $this->newLine();

        $this->info(
            'Starting deletion...'
        );

        $this->info(
            'Deleted IDs will be written to CSV incrementally.'
        );

        $this->newLine();

        $startedAt = microtime(true);

        $deletedTotal = 0;

        $batchNumber = 0;

        $lastVisitorId = $startId;

        $status = self::SUCCESS;

        try {
            while (true) {
                // -------------------------------------------------
                // Stop when limit reached
                // -------------------------------------------------

                if (
                    $limit > 0 &&
                    $deletedTotal >= $limit
                ) {
                    break;
                }

                // -------------------------------------------------
                // Calculate batch size
                // -------------------------------------------------

                if ($limit > 0) {
                    $remaining =
                        $limit - $deletedTotal;

                    $currentBatchSize = min(
                        $batchSize,
                        $remaining
                    );
                } else {
                    $currentBatchSize =
                        $batchSize;
                }

                if ($currentBatchSize <= 0) {
                    break;
                }

                // -------------------------------------------------
                // Find orphan IDs
                // -------------------------------------------------

                $ids = $this->buildOrphanQuery(
                    $lastVisitorId
                )
                    ->select('v.id')
                    ->orderBy('v.id')
                    ->limit($currentBatchSize)
                    ->pluck('id');

                if ($ids->isEmpty()) {
                    break;
                }

                /*
                 * Important:
                 *
                 * Keep the last ID even if some IDs become
                 * referenced between SELECT and DELETE.
                 */
                $lastVisitorId = $ids->last();

                // -------------------------------------------------
                // Delete batch
                // -------------------------------------------------

                $batchDeletedIds = [];

                try {
                    DB::transaction(
                        function () use (
                            $ids,
                            &$batchDeletedIds
                        ) {
                            /*
                             * Get the IDs that still qualify
                             * immediately before deletion.
                             */
                            $batchDeletedIds = DB::table(
                                'visitors as v'
                            )
                                ->whereIn(
                                    'v.id',
                                    $ids->all()
                                )

                                ->whereNotExists(
                                    function ($query) {
                                        $query
                                            ->select(
                                                DB::raw(1)
                                            )
                                            ->from(
                                                'blacklisted_visitors as b'
                                            )
                                            ->whereColumn(
                                                'b.visitor_id',
                                                'v.id'
                                            );
                                    }
                                )

                                ->whereNotExists(
                                    function ($query) {
                                        $query
                                            ->select(
                                                DB::raw(1)
                                            )
                                            ->from(
                                                'preregister_visitors as p'
                                            )
                                            ->whereColumn(
                                                'p.visitor_id',
                                                'v.id'
                                            );
                                    }
                                )

                                ->whereNotExists(
                                    function ($query) {
                                        $query
                                            ->select(
                                                DB::raw(1)
                                            )
                                            ->from(
                                                'visitor_logs as l'
                                            )
                                            ->whereColumn(
                                                'l.visitor_id',
                                                'v.id'
                                            );
                                    }
                                )

                                ->whereNotExists(
                                    function ($query) {
                                        $query
                                            ->select(
                                                DB::raw(1)
                                            )
                                            ->from(
                                                'visitor_logs_archive as a'
                                            )
                                            ->whereColumn(
                                                'a.visitor_id',
                                                'v.id'
                                            );
                                    }
                                )
                                ->pluck('v.id');

                            if ($batchDeletedIds->isEmpty()) {
                                return;
                            }

                            /*
                             * Delete exactly the IDs we just
                             * verified.
                             */
                            DB::table(
                                'visitors'
                            )
                                ->whereIn(
                                    'id',
                                    $batchDeletedIds->all()
                                )
                                ->delete();
                        }
                    );
                } catch (Throwable $e) {
                    $this->error(
                        'Batch deletion failed.'
                    );

                    $this->error(
                        $e->getMessage()
                    );

                    Log::error(
                        'Orphan visitor cleanup failed',
                        [
                            'batch' =>
                                $batchNumber + 1,

                            'last_visitor_id' =>
                                $lastVisitorId,

                            'selected_count' =>
                                $ids->count(),

                            'deleted_total' =>
                                $deletedTotal,

                            'exception' =>
                                $e,
                        ]
                    );

                    $status = self::FAILURE;

                    break;
                }

                $batchDeleted =
                    $batchDeletedIds->count();

                $deletedTotal +=
                    $batchDeleted;

                // -------------------------------------------------
                // Write deleted IDs to CSV
                // -------------------------------------------------

                foreach ($batchDeletedIds as $deletedId) {
                    $this->writeDeletedIdToCsv(
                        $deletedId
                    );
                }

                $batchNumber++;

                // -------------------------------------------------
                // Progress
                // -------------------------------------------------

                $elapsed = max(
                    microtime(true) - $startedAt,
                    0.001
                );

                $rate = $deletedTotal > 0
                    ? round(
                        $deletedTotal / $elapsed
                    )
                    : 0;

                $percent =
                    $maximumToDelete > 0
                        ? min(
                            100,
                            round(
                                (
                                    $deletedTotal /
                                    $maximumToDelete
                                ) * 100,
                                2
                            )
                        )
                        : 100;

                $this->line(
                    sprintf(
                        '[Batch %d] Selected: %s | Deleted: %s | Progress: %s%% | Rate: %s/sec | Last ID: %s',
                        $batchNumber,
                        number_format(
                            $ids->count()
                        ),
                        number_format(
                            $deletedTotal
                        ),
                        $percent,
                        number_format(
                            $rate
                        ),
                        $lastVisitorId
                    )
                );

                // -------------------------------------------------
                // Log batch
                // -------------------------------------------------

                Log::info(
                    'Orphan visitor cleanup batch',
                    [
                        'batch' =>
                            $batchNumber,

                        'selected_count' =>
                            $ids->count(),

                        'batch_deleted' =>
                            $batchDeleted,

                        'deleted_total' =>
                            $deletedTotal,

                        'last_visitor_id' =>
                            $lastVisitorId,

                        'rate_per_second' =>
                            $rate,
                    ]
                );

                // -------------------------------------------------
                // Sleep
                // -------------------------------------------------

                if ($sleep > 0) {
                    usleep(
                        $sleep * 1000
                    );
                }
            }
        } finally {
            $this->closeDeletedIdsFile();
        }

        // ---------------------------------------------------------
        // Final statistics
        // ---------------------------------------------------------

        $elapsed = round(
            microtime(true) - $startedAt,
            2
        );

        $rate = $elapsed > 0
            ? round(
                $deletedTotal / $elapsed
            )
            : 0;

        $this->newLine();

        $this->line(
            '======================================================'
        );

        $this->info(
            $status === self::SUCCESS
                ? 'CLEANUP COMPLETED'
                : 'CLEANUP FAILED'
        );

        $this->line(
            '======================================================'
        );

        $this->info(
            'Deleted          : ' .
            number_format($deletedTotal)
        );

        $this->info(
            'Execution time   : ' .
            $elapsed .
            ' seconds'
        );

        $this->info(
            'Average rate     : ' .
            number_format($rate) .
            '/sec'
        );

        $this->line(
            'Last visitor ID  : ' .
            (
                $lastVisitorId !== null
                    ? $lastVisitorId
                    : 'N/A'
            )
        );

        $this->line(
            'Deleted IDs CSV  : ' .
            (
                $this->deletedIdsFilePath
                    ?? 'N/A'
            )
        );

        $this->line(
            '======================================================'
        );

        // ---------------------------------------------------------
        // Send email report
        // ---------------------------------------------------------

        if ($deletedTotal > 0) {
            $this->sendDeletionReportEmail(
                $deletedTotal,
                $elapsed,
                $rate,
                $lastVisitorId,
                $status === self::SUCCESS
            );
        } else {
            $this->info(
                'No visitors were deleted. No email report sent.'
            );
        }

        Log::info(
            'Orphan visitor cleanup completed',
            [
                'status' =>
                    $status === self::SUCCESS
                        ? 'success'
                        : 'failed',

                'deleted_total' =>
                    $deletedTotal,

                'execution_time' =>
                    $elapsed,

                'rate_per_second' =>
                    $rate,

                'last_visitor_id' =>
                    $lastVisitorId,

                'deleted_ids_file' =>
                    $this->deletedIdsFilePath,
            ]
        );

        return $status;
    }

    /**
     * Create CSV report.
     *
     * The file is written incrementally so millions of IDs
     * do not consume PHP memory.
     */
    private function initializeDeletedIdsFile(): void
    {
        $directory = storage_path(
            'app/visitor-cleanup'
        );

        if (! is_dir($directory)) {
            if (! mkdir(
                $directory,
                0750,
                true
            ) && ! is_dir($directory)) {
                throw new \RuntimeException(
                    "Unable to create directory: {$directory}"
                );
            }
        }

        $filename =
            'deleted-visitors-' .
            now()->format('Ymd_His') .
            '-' .
            getmypid() .
            '.csv';

        $this->deletedIdsFilePath =
            $directory .
            DIRECTORY_SEPARATOR .
            $filename;

        $this->deletedIdsFileHandle = fopen(
            $this->deletedIdsFilePath,
            'wb'
        );

        if ($this->deletedIdsFileHandle === false) {
            throw new \RuntimeException(
                "Unable to create CSV file: {$this->deletedIdsFilePath}"
            );
        }

        // CSV header
        fputcsv(
            $this->deletedIdsFileHandle,
            [
                'visitor_id',
                'deleted_at',
            ]
        );
    }

    /**
     * Write one deleted visitor ID.
     */
    private function writeDeletedIdToCsv(
        mixed $visitorId
    ): void {
        if (
            ! is_resource(
                $this->deletedIdsFileHandle
            )
        ) {
            throw new \RuntimeException(
                'Deleted visitor CSV file is not open.'
            );
        }

        fputcsv(
            $this->deletedIdsFileHandle,
            [
                $visitorId,
                now()->toDateTimeString(),
            ]
        );

        $this->deletedIdsWritten++;

        /*
         * Flush periodically instead of keeping data
         * in PHP's filesystem buffer for too long.
         */
        if (
            $this->deletedIdsWritten % 1000 === 0
        ) {
            fflush(
                $this->deletedIdsFileHandle
            );
        }
    }

    /**
     * Close CSV file.
     */
    private function closeDeletedIdsFile(): void
    {
        if (
            is_resource(
                $this->deletedIdsFileHandle
            )
        ) {
            fflush(
                $this->deletedIdsFileHandle
            );

            fclose(
                $this->deletedIdsFileHandle
            );
        }

        $this->deletedIdsFileHandle = null;
    }

    /**
     * Send cleanup email.
     */
    private function sendDeletionReportEmail(
        int $deletedTotal,
        float $elapsed,
        int $rate,
        mixed $lastVisitorId,
        bool $completedSuccessfully
    ): void {
        if (
            $this->deletedIdsFilePath === null ||
            ! is_file(
                $this->deletedIdsFilePath
            )
        ) {
            $this->warn(
                'Deleted IDs CSV does not exist. Email was not sent.'
            );

            return;
        }

        $this->info(
            'Sending deletion report email...'
        );

        try {
            Mail::to(
                $this->reportEmail
            )->send(
                new \App\Mail\OrphanVisitorCleanupReport(
                    deletedTotal: $deletedTotal,
                    executionTime: $elapsed,
                    rate: $rate,
                    lastVisitorId: $lastVisitorId,
                    csvPath: $this->deletedIdsFilePath,
                    completedSuccessfully:
                        $completedSuccessfully,
                )
            );

            $this->info(
                'Deletion report email sent to ' .
                $this->reportEmail
            );
        } catch (Throwable $e) {
            $this->error(
                'Cleanup completed, but email could not be sent.'
            );

            $this->error(
                $e->getMessage()
            );

            Log::error(
                'Orphan visitor cleanup email failed',
                [
                    'email' =>
                        $this->reportEmail,

                    'deleted_total' =>
                        $deletedTotal,

                    'csv_path' =>
                        $this->deletedIdsFilePath,

                    'exception' =>
                        $e,
                ]
            );
        }
    }

    /**
     * Build orphan visitor query.
     */
    private function buildOrphanQuery(
        ?string $afterId = null
    ) {
        $query = DB::table(
            'visitors as v'
        );

        // ---------------------------------------------------------
        // Keyset pagination
        // ---------------------------------------------------------

        if ($afterId !== null) {
            $query->where(
                'v.id',
                '>',
                $afterId
            );
        }

        // ---------------------------------------------------------
        // blacklisted_visitors
        // ---------------------------------------------------------

        $query->whereNotExists(
            function ($query) {
                $query
                    ->select(
                        DB::raw(1)
                    )
                    ->from(
                        'blacklisted_visitors as b'
                    )
                    ->whereColumn(
                        'b.visitor_id',
                        'v.id'
                    );
            }
        );

        // ---------------------------------------------------------
        // preregister_visitors
        // ---------------------------------------------------------

        $query->whereNotExists(
            function ($query) {
                $query
                    ->select(
                        DB::raw(1)
                    )
                    ->from(
                        'preregister_visitors as p'
                    )
                    ->whereColumn(
                        'p.visitor_id',
                        'v.id'
                    );
            }
        );

        // ---------------------------------------------------------
        // visitor_logs
        // ---------------------------------------------------------

        $query->whereNotExists(
            function ($query) {
                $query
                    ->select(
                        DB::raw(1)
                    )
                    ->from(
                        'visitor_logs as l'
                    )
                    ->whereColumn(
                        'l.visitor_id',
                        'v.id'
                    );
            }
        );

        // ---------------------------------------------------------
        // visitor_logs_archive
        // ---------------------------------------------------------

        $query->whereNotExists(
            function ($query) {
                $query
                    ->select(
                        DB::raw(1)
                    )
                    ->from(
                        'visitor_logs_archive as a'
                    )
                    ->whereColumn(
                        'a.visitor_id',
                        'v.id'
                    );
            }
        );

        return $query;
    }
}
