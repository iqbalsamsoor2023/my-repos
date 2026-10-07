<?php

namespace App\Console\Commands\Vms;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanupUncheckoutVisitorLogs extends Command
{
    /**
     * Examples:
     *
     * Dry run:
     * php artisan visitors:cleanup-uncheckout-logs --dry-run
     *
     * Dry run with batch:
     * php artisan visitors:cleanup-uncheckout-logs --dry-run --batch=10000
     *
     * Delete:
     * php artisan visitors:cleanup-uncheckout-logs --batch=10000
     *
     * Delete with limit:
     * php artisan visitors:cleanup-uncheckout-logs --batch=10000 --limit=50000
     *
     * Delete without confirmation:
     * php artisan visitors:cleanup-uncheckout-logs --batch=10000 --force
     *
     * Start after specific log ID:
     * php artisan visitors:cleanup-uncheckout-logs --start-id=1000000
     *
     * Change retention:
     * php artisan visitors:cleanup-uncheckout-logs --years=1
     */
    protected $signature = 'visitors:cleanup-uncheckout-logs
        {--dry-run : Only report candidates. Nothing will be deleted}
        {--batch=10000 : Number of logs processed per batch}
        {--limit=0 : Maximum number of logs to delete. 0 means unlimited}
        {--sleep=100 : Sleep between batches in milliseconds}
        {--force : Skip deletion confirmation}
        {--start-id= : Start after this visitor_logs.id}
        {--years=1 : Delete logs older than this number of years}
        {--show-sample=20 : Number of sample log IDs to show during dry-run}';

    protected $description =
        'Permanently delete visitor logs that have not checked out for more than one year';

    /**
     * Demo residence activation status IDs.
     *
     * 1 = Inactive - Demo
     * 6 = Active - For Demo Only
     *
     * These are processed FIRST.
     */
    private array $demoActivationStatusIds = [
        1,
        6,
    ];

    /**
     * Cancelled residence activation status IDs.
     *
     * 2 = Inactive - Cancelled Service
     *
     * These are also processed FIRST.
     */
    private array $cancelledActivationStatusIds = [
        2,
    ];

    /**
     * Main visitor log table.
     */
    private string $logTable = 'visitor_logs';

    /**
     * Residence table.
     */
    private string $residenceTable = 'residences';

    /**
     * Primary key of visitor_logs.
     */
    private string $logPrimaryKey = 'id';

    /**
     * Residence foreign key in visitor_logs.
     */
    private string $residenceForeignKey = 'residence_id';

    /**
     * Residence primary key.
     */
    private string $residencePrimaryKey = 'id';

    /**
     * Residence name column.
     */
    private string $residenceNameColumn = 'name';

    /**
     * Residence activation status column.
     */
    private string $activationStatusColumn =
        'residence_activation_status_id';

    /**
     * Leave/check-out column.
     */
    private string $leaveTimeColumn = 'leave_time';

    /**
     * Arrival/check-in column.
     */
    private string $arrivalTimeColumn = 'arrival_time';

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

        $years = max(
            1,
            (int) $this->option('years')
        );

        $showSample = max(
            0,
            (int) $this->option('show-sample')
        );

        $startId = $this->option('start-id');

        $cutoffDate = now()->subYears($years);

        $this->displayHeader(
            $dryRun,
            $batchSize,
            $limit,
            $sleep,
            $years,
            $cutoffDate,
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
                'Uncheckout visitor logs validation failed',
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
                $cutoffDate,
                $startId,
                $showSample
            );
        }

        // ---------------------------------------------------------
        // DELETE
        // ---------------------------------------------------------

        return $this->runDelete(
            $cutoffDate,
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
        int $years,
        Carbon $cutoffDate,
        ?string $startId
    ): void {
        $this->newLine();

        $this->line(
            '======================================================'
        );

        $this->line(
            '        UNCLOSED VISITOR LOG CLEANUP'
        );

        $this->line(
            '======================================================'
        );

        $this->line(
            'Mode              : ' .
            ($dryRun ? 'DRY RUN' : 'DELETE')
        );

        $this->line(
            'Target table      : visitor_logs'
        );

        $this->line(
            'Target condition  : leave_time IS NULL'
        );

        $this->line(
            'Retention         : ' .
            $years .
            ' year(s)'
        );

        $this->line(
            'Cutoff date       : ' .
            $cutoffDate->format('Y-m-d H:i:s')
        );

        $this->line(
            'Batch size        : ' .
            number_format($batchSize)
        );

        $this->line(
            'Delete limit      : ' .
            (
                $limit > 0
                    ? number_format($limit)
                    : 'UNLIMITED'
            )
        );

        $this->line(
            'Sleep             : ' .
            $sleep .
            ' ms'
        );

        $this->line(
            'Start ID          : ' .
            (
                $startId !== null
                    ? $startId
                    : 'BEGINNING'
            )
        );

        $this->line(
            'Demo status IDs   : 1, 6'
        );

        $this->line(
            'Cancelled status  : 2'
        );

        $this->line(
            'Priority          : DEMO / CANCELLED FIRST'
        );

        $this->line(
            'Delete type       : PERMANENT DELETE'
        );

        $this->line(
            '======================================================'
        );

        $this->newLine();
    }

    /**
     * Validate database structure and important indexes.
     */
    private function validateDatabase(): void
    {
        $schema = DB::getSchemaBuilder();

        // ---------------------------------------------------------
        // Tables
        // ---------------------------------------------------------

        if (! $schema->hasTable($this->logTable)) {
            throw new \RuntimeException(
                "Required table does not exist: {$this->logTable}"
            );
        }

        if (! $schema->hasTable($this->residenceTable)) {
            throw new \RuntimeException(
                "Required table does not exist: {$this->residenceTable}"
            );
        }

        $this->info('Database tables: OK');

        // ---------------------------------------------------------
        // visitor_logs columns
        // ---------------------------------------------------------

        $logColumns = $schema->getColumnListing(
            $this->logTable
        );

        $requiredLogColumns = [
            $this->logPrimaryKey,
            $this->residenceForeignKey,
            $this->leaveTimeColumn,
            $this->arrivalTimeColumn,
        ];

        foreach ($requiredLogColumns as $column) {
            if (! in_array(
                $column,
                $logColumns,
                true
            )) {
                throw new \RuntimeException(
                    "Column {$this->logTable}.{$column} does not exist."
                );
            }
        }

        $this->info(
            'visitor_logs required columns: OK'
        );

        // ---------------------------------------------------------
        // residences columns
        // ---------------------------------------------------------

        $residenceColumns = $schema->getColumnListing(
            $this->residenceTable
        );

        $requiredResidenceColumns = [
            $this->residencePrimaryKey,
            $this->residenceNameColumn,
            $this->activationStatusColumn,
        ];

        foreach ($requiredResidenceColumns as $column) {
            if (! in_array(
                $column,
                $residenceColumns,
                true
            )) {
                throw new \RuntimeException(
                    "Column {$this->residenceTable}.{$column} does not exist."
                );
            }
        }

        $this->info(
            'residences required columns: OK'
        );

        // ---------------------------------------------------------
        // Index validation
        // ---------------------------------------------------------

        $this->validateRequiredIndexes();

        $this->info(
            'Required indexes: OK'
        );

        $this->newLine();
    }

    /**
     * Validate indexes required for large-scale cleanup.
     */
    private function validateRequiredIndexes(): void
    {
        // ---------------------------------------------------------
        // visitor_logs.id
        // ---------------------------------------------------------

        if (! $this->hasIndexContainingColumn(
            $this->logTable,
            $this->logPrimaryKey
        )) {
            throw new \RuntimeException(
                'Missing index on visitor_logs.id.'
            );
        }

        // ---------------------------------------------------------
        // visitor_logs.residence_id
        // ---------------------------------------------------------

        if (! $this->hasIndexContainingColumn(
            $this->logTable,
            $this->residenceForeignKey
        )) {
            throw new \RuntimeException(
                'Missing index on visitor_logs.residence_id.'
            );
        }

        // ---------------------------------------------------------
        // residences.id
        // ---------------------------------------------------------

        if (! $this->hasIndexContainingColumn(
            $this->residenceTable,
            $this->residencePrimaryKey
        )) {
            throw new \RuntimeException(
                'Missing index on residences.id.'
            );
        }

        // ---------------------------------------------------------
        // visitor_logs(leave_time, arrival_time)
        // ---------------------------------------------------------

        if (! $this->hasCompositeIndex(
            $this->logTable,
            [
                $this->leaveTimeColumn,
                $this->arrivalTimeColumn,
            ]
        )) {
            throw new \RuntimeException(
                'Missing recommended composite index on ' .
                'visitor_logs(leave_time, arrival_time). ' .
                'Create it before running large cleanup.'
            );
        }
    }

    /**
     * Check whether a table has an index containing a column.
     */
    private function hasIndexContainingColumn(
        string $table,
        string $column
    ): bool {
        $indexes = DB::select(
            "SHOW INDEX FROM `{$table}`"
        );

        foreach ($indexes as $index) {
            if (
                isset($index->Column_name) &&
                $index->Column_name === $column
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether a composite index exists with the
     * specified columns in the specified order.
     */
    private function hasCompositeIndex(
        string $table,
        array $columns
    ): bool {
        $indexes = DB::select(
            "SHOW INDEX FROM `{$table}`"
        );

        $grouped = [];

        foreach ($indexes as $index) {
            if (
                ! isset($index->Key_name) ||
                ! isset($index->Seq_in_index) ||
                ! isset($index->Column_name)
            ) {
                continue;
            }

            $keyName = $index->Key_name;

            $grouped[$keyName][
                (int) $index->Seq_in_index
            ] = $index->Column_name;
        }

        foreach ($grouped as $indexColumns) {
            ksort($indexColumns);

            $indexColumns = array_values(
                $indexColumns
            );

            if (
                array_slice(
                    $indexColumns,
                    0,
                    count($columns)
                ) === $columns
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * DRY RUN.
     */
    private function runDryRun(
        Carbon $cutoffDate,
        ?string $startId,
        int $showSample
    ): int {
        $this->info('Running dry-run...');
        $this->newLine();

        $startedAt = microtime(true);

        // ---------------------------------------------------------
        // Total candidates
        // ---------------------------------------------------------

        $this->info(
            'Counting candidate visitor logs...'
        );

        $total = $this->buildCandidateQuery(
            $cutoffDate,
            $startId,
            null
        )->count('l.id');

        // ---------------------------------------------------------
        // Demo candidates
        // ---------------------------------------------------------

        $this->info(
            'Counting demo visitor logs...'
        );

        $demoCount = $this->buildCandidateQuery(
            $cutoffDate,
            $startId,
            'demo'
        )->count('l.id');

        // ---------------------------------------------------------
        // Cancelled candidates
        // ---------------------------------------------------------

        $this->info(
            'Counting cancelled visitor logs...'
        );

        $cancelledCount = $this->buildCandidateQuery(
            $cutoffDate,
            $startId,
            'cancelled'
        )->count('l.id');

        // ---------------------------------------------------------
        // Non-demo / non-cancelled candidates
        // ---------------------------------------------------------

        $nonDemoCount = $this->buildCandidateQuery(
            $cutoffDate,
            $startId,
            'non_demo'
        )->count('l.id');

        $elapsed = round(
            microtime(true) - $startedAt,
            2
        );

        $this->newLine();

        $this->line(
            '======================================================'
        );

        $this->info(
            'DRY RUN RESULT'
        );

        $this->line(
            '======================================================'
        );

        $this->line(
            'Total records     : ' .
            number_format($total)
        );

        $this->line(
            'Demo records      : ' .
            number_format($demoCount)
        );

        $this->line(
            'Cancelled records : ' .
            number_format($cancelledCount)
        );

        $this->line(
            'Non-demo records  : ' .
            number_format($nonDemoCount)
        );

        $this->line(
            'Count time           : ' .
            $elapsed .
            ' seconds'
        );

        $this->line(
            '======================================================'
        );

        // ---------------------------------------------------------
        // Sample IDs
        // ---------------------------------------------------------

        if (
            $total > 0 &&
            $showSample > 0
        ) {
            $this->newLine();

            $this->info(
                "Sample of {$showSample} candidate log IDs:"
            );

            $sample = $this->buildCandidateQuery(
                $cutoffDate,
                $startId,
                null
            )
                ->select([
                    'l.id',
                    'l.residence_id',
                    'r.name as residence_name',
                    'l.arrival_time',
                    'l.leave_time',
                    'r.residence_activation_status_id',
                ])
                ->orderBy('l.id')
                ->limit($showSample)
                ->get();

            foreach ($sample as $row) {
                $statusId = (int) $row->residence_activation_status_id;

                $type = $this->getStatusType($statusId);

                $this->line(
                    sprintf(
                        '  ID: %s | Residence: %s - %s | Status: %s (%s) | Arrival: %s',
                        $row->id,
                        $row->residence_id ?? 'NULL',
                        $row->residence_name ?? 'NULL',
                        $row->residence_activation_status_id ?? 'NULL',
                        $type,
                        $row->arrival_time
                    )
                );
            }
        }

        $this->newLine();

        if ($total === 0) {
            $this->info(
                'No uncheckout visitor logs found.'
            );
        } else {
            $this->warn(
                'DRY RUN ONLY - NO DATA WAS DELETED.'
            );

            $this->warn(
                'DEMO and CANCELLED records will be deleted FIRST during actual cleanup.'
            );
        }

        return self::SUCCESS;
    }

    /**
     * Convert activation status ID to readable type.
     */
    private function getStatusType(int $statusId): string
    {
        if (
            in_array(
                $statusId,
                $this->demoActivationStatusIds,
                true
            )
        ) {
            return 'DEMO';
        }

        if (
            in_array(
                $statusId,
                $this->cancelledActivationStatusIds,
                true
            )
        ) {
            return 'CANCELLED';
        }

        return 'NON-DEMO';
    }

    /**
     * Production deletion.
     *
     * Priority:
     *
     * 1. DEMO
     * 2. CANCELLED
     * 3. NON-DEMO
     */
    private function runDelete(
        Carbon $cutoffDate,
        int $batchSize,
        int $limit,
        int $sleep,
        ?string $startId
    ): int {
        // ---------------------------------------------------------
        // Initial counts
        // ---------------------------------------------------------

        $this->info(
            'Checking candidate counts...'
        );

        $countStartedAt = microtime(true);

        $totalCandidates = $this->buildCandidateQuery(
            $cutoffDate,
            $startId,
            null
        )->count('l.id');

        $demoCandidates = $this->buildCandidateQuery(
            $cutoffDate,
            $startId,
            'demo'
        )->count('l.id');

        $cancelledCandidates = $this->buildCandidateQuery(
            $cutoffDate,
            $startId,
            'cancelled'
        )->count('l.id');

        $nonDemoCandidates = $this->buildCandidateQuery(
            $cutoffDate,
            $startId,
            'non_demo'
        )->count('l.id');

        $countTime = round(
            microtime(true) - $countStartedAt,
            2
        );

        $this->newLine();

        $this->info(
            'Total records     : ' .
            number_format($totalCandidates)
        );

        $this->info(
            'Demo records      : ' .
            number_format($demoCandidates)
        );

        $this->info(
            'Cancelled records : ' .
            number_format($cancelledCandidates)
        );

        $this->info(
            'Non-demo records  : ' .
            number_format($nonDemoCandidates)
        );

        $this->info(
            'Count time           : ' .
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
        // Maximum
        // ---------------------------------------------------------

        $maximumToDelete = $limit > 0
            ? min(
                $limit,
                $totalCandidates
            )
            : $totalCandidates;

        $this->newLine();

        $this->warn(
            'This operation will PERMANENTLY DELETE up to ' .
            number_format($maximumToDelete) .
            ' visitor_logs records.'
        );

        $this->warn(
            'Deleted records CANNOT be restored by Laravel soft deletes.'
        );

        $this->warn(
            'Priority: DEMO → CANCELLED → NON-DEMO'
        );

        $this->newLine();

        // ---------------------------------------------------------
        // Confirmation
        // ---------------------------------------------------------

        if (! $this->option('force')) {
            if (! $this->confirm(
                'Are you sure you want to permanently delete these records?'
            )) {
                $this->warn(
                    'Operation cancelled.'
                );

                return self::SUCCESS;
            }
        }

        // ---------------------------------------------------------
        // Start deletion
        // ---------------------------------------------------------

        $this->newLine();

        $this->info(
            'Starting deletion...'
        );

        $this->newLine();

        $startedAt = microtime(true);

        $deletedTotal = 0;

        $demoDeleted = 0;

        $cancelledDeleted = 0;

        $nonDemoDeleted = 0;

        // ---------------------------------------------------------
        // PHASE 1
        //
        // DEMO FIRST
        // ---------------------------------------------------------

        $this->line(
            '======================================================'
        );

        $this->info(
            'PHASE 1: DEMO'
        );

        $this->line(
            'Activation status IDs: 1, 6'
        );

        $this->line(
            '======================================================'
        );

        $demoResult = $this->deletePhase(
            $cutoffDate,
            $batchSize,
            $limit,
            $sleep,
            $startId,
            'demo',
            $startedAt,
            $deletedTotal
        );

        if ($demoResult === null) {
            return self::FAILURE;
        }

        $demoDeleted = $demoResult['deleted'];

        $deletedTotal = $demoResult['deleted_total'];

        // ---------------------------------------------------------
        // Stop if global limit reached
        // ---------------------------------------------------------

        if (
            $limit > 0 &&
            $deletedTotal >= $limit
        ) {
            return $this->finishCleanup(
                $deletedTotal,
                $demoDeleted,
                $cancelledDeleted,
                $nonDemoDeleted,
                $startedAt
            );
        }

        // ---------------------------------------------------------
        // PHASE 2
        //
        // CANCELLED
        // ---------------------------------------------------------

        $this->newLine();

        $this->line(
            '======================================================'
        );

        $this->info(
            'PHASE 2: CANCELLED'
        );

        $this->line(
            'Activation status ID: 2'
        );

        $this->line(
            '======================================================'
        );

        $cancelledResult = $this->deletePhase(
            $cutoffDate,
            $batchSize,
            $limit,
            $sleep,
            $startId,
            'cancelled',
            $startedAt,
            $deletedTotal
        );

        if ($cancelledResult === null) {
            return self::FAILURE;
        }

        $cancelledDeleted =
            $cancelledResult['deleted'];

        $deletedTotal =
            $cancelledResult['deleted_total'];

        // ---------------------------------------------------------
        // Stop if global limit reached
        // ---------------------------------------------------------

        if (
            $limit > 0 &&
            $deletedTotal >= $limit
        ) {
            return $this->finishCleanup(
                $deletedTotal,
                $demoDeleted,
                $cancelledDeleted,
                $nonDemoDeleted,
                $startedAt
            );
        }

        // ---------------------------------------------------------
        // PHASE 3
        //
        // NON-DEMO / NON-CANCELLED
        // ---------------------------------------------------------

        $this->newLine();

        $this->line(
            '======================================================'
        );

        $this->info(
            'PHASE 3: NON-DEMO / NON-CANCELLED'
        );

        $this->line(
            '======================================================'
        );

        $nonDemoResult = $this->deletePhase(
            $cutoffDate,
            $batchSize,
            $limit,
            $sleep,
            $startId,
            'non_demo',
            $startedAt,
            $deletedTotal
        );

        if ($nonDemoResult === null) {
            return self::FAILURE;
        }

        $nonDemoDeleted =
            $nonDemoResult['deleted'];

        $deletedTotal =
            $nonDemoResult['deleted_total'];

        // ---------------------------------------------------------
        // Finish
        // ---------------------------------------------------------

        return $this->finishCleanup(
            $deletedTotal,
            $demoDeleted,
            $cancelledDeleted,
            $nonDemoDeleted,
            $startedAt
        );
    }

    /**
     * Delete one phase.
     *
     * $phase:
     *
     * demo       = statuses 1, 6
     * cancelled  = status 2
     * non_demo   = everything except 1, 2, 6
     */
    private function deletePhase(
        Carbon $cutoffDate,
        int $batchSize,
        int $limit,
        int $sleep,
        ?string $startId,
        string $phase,
        float $startedAt,
        int $deletedTotal
    ): ?array {
        $lastId = $startId;

        $phaseDeleted = 0;

        $batchNumber = 0;

        while (true) {
            // -----------------------------------------------------
            // Global limit
            // -----------------------------------------------------

            if (
                $limit > 0 &&
                $deletedTotal >= $limit
            ) {
                break;
            }

            // -----------------------------------------------------
            // Calculate batch size
            // -----------------------------------------------------

            if ($limit > 0) {
                $remaining =
                    $limit - $deletedTotal;

                $currentBatchSize = min(
                    $batchSize,
                    $remaining
                );
            } else {
                $currentBatchSize = $batchSize;
            }

            if ($currentBatchSize <= 0) {
                break;
            }

            // -----------------------------------------------------
            // Find candidates
            // -----------------------------------------------------

            $ids = $this->buildCandidateQuery(
                $cutoffDate,
                $lastId,
                $phase
            )
                ->select('l.id')
                ->orderBy('l.id')
                ->limit($currentBatchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            // -----------------------------------------------------
            // Save last ID BEFORE DELETE
            // -----------------------------------------------------

            $lastId = $ids->last();

            // -----------------------------------------------------
            // Delete
            // -----------------------------------------------------

            $batchDeleted = 0;

            try {
                DB::transaction(
                    function () use (
                        $ids,
                        $cutoffDate,
                        $phase,
                        &$batchDeleted
                    ) {
                        /*
                         * Re-check everything immediately before
                         * permanent deletion.
                         *
                         * This protects against a visitor checking
                         * out between SELECT and DELETE.
                         */
                        $query = DB::table(
                            'visitor_logs as l'
                        )
                            ->join(
                                'residences as r',
                                'r.id',
                                '=',
                                'l.residence_id'
                            )
                            ->whereIn(
                                'l.id',
                                $ids->all()
                            )
                            ->whereNull(
                                'l.leave_time'
                            )
                            ->where(
                                'l.arrival_time',
                                '<',
                                $cutoffDate
                            );

                        // -------------------------------------------------
                        // Phase filtering
                        // -------------------------------------------------

                        if ($phase === 'demo') {
                            $query->whereIn(
                                'r.residence_activation_status_id',
                                $this->demoActivationStatusIds
                            );
                        }

                        if ($phase === 'cancelled') {
                            $query->whereIn(
                                'r.residence_activation_status_id',
                                $this->cancelledActivationStatusIds
                            );
                        }

                        if ($phase === 'non_demo') {
                            $excludedStatuses = array_unique(
                                array_merge(
                                    $this->demoActivationStatusIds,
                                    $this->cancelledActivationStatusIds
                                )
                            );

                            $query->whereNotIn(
                                'r.residence_activation_status_id',
                                $excludedStatuses
                            );
                        }

                        /*
                         * IMPORTANT:
                         *
                         * Laravel query builder generates a physical
                         * DELETE statement here.
                         *
                         * It does NOT update deleted_at.
                         *
                         * Therefore this is a PERMANENT DELETE.
                         */
                        $batchDeleted = $query->delete();
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
                    'Uncheckout visitor logs cleanup failed',
                    [
                        'phase' =>
                            $phase,
                        'batch' =>
                            $batchNumber + 1,
                        'last_id' =>
                            $lastId,
                        'selected_count' =>
                            $ids->count(),
                        'phase_deleted' =>
                            $phaseDeleted,
                        'deleted_total' =>
                            $deletedTotal,
                        'exception' =>
                            $e,
                    ]
                );

                return null;
            }

            $phaseDeleted += $batchDeleted;

            $deletedTotal += $batchDeleted;

            $batchNumber++;

            // -----------------------------------------------------
            // Progress
            // -----------------------------------------------------

            $elapsed = max(
                microtime(true) - $startedAt,
                0.001
            );

            $rate = $deletedTotal > 0
                ? round(
                    $deletedTotal / $elapsed
                )
                : 0;

            $phaseName = match ($phase) {
                'demo' => 'DEMO',
                'cancelled' => 'CANCELLED',
                default => 'NON-DEMO',
            };

            $this->line(
                sprintf(
                    '[%s][Batch %d] Selected: %s | Batch deleted: %s | Total deleted: %s | Rate: %s/sec | Last ID: %s',
                    $phaseName,
                    $batchNumber,
                    number_format($ids->count()),
                    number_format($batchDeleted),
                    number_format($deletedTotal),
                    number_format($rate),
                    $lastId
                )
            );

            // -----------------------------------------------------
            // Log
            // -----------------------------------------------------

            Log::info(
                'Uncheckout visitor logs cleanup batch',
                [
                    'phase' =>
                        $phase,
                    'batch' =>
                        $batchNumber,
                    'selected_count' =>
                        $ids->count(),
                    'batch_deleted' =>
                        $batchDeleted,
                    'phase_deleted' =>
                        $phaseDeleted,
                    'deleted_total' =>
                        $deletedTotal,
                    'last_id' =>
                        $lastId,
                ]
            );

            // -----------------------------------------------------
            // Sleep
            // -----------------------------------------------------

            if ($sleep > 0) {
                usleep(
                    $sleep * 1000
                );
            }
        }

        return [
            'deleted' =>
                $phaseDeleted,

            'deleted_total' =>
                $deletedTotal,

            'last_id' =>
                $lastId,
        ];
    }

    /**
     * Build candidate query.
     *
     * $phase:
     *
     * null       = all
     * demo       = statuses 1, 6
     * cancelled  = status 2
     * non_demo   = everything except 1, 2, 6
     */
    private function buildCandidateQuery(
        Carbon $cutoffDate,
        ?string $afterId = null,
        ?string $phase = null
    ) {
        $query = DB::table(
            'visitor_logs as l'
        )
            ->join(
                'residences as r',
                'r.id',
                '=',
                'l.residence_id'
            )
            ->whereNull(
                'l.leave_time'
            )
            ->where(
                'l.arrival_time',
                '<',
                $cutoffDate
            );

        // ---------------------------------------------------------
        // Keyset pagination
        // ---------------------------------------------------------

        if ($afterId !== null) {
            $query->where(
                'l.id',
                '>',
                $afterId
            );
        }

        // ---------------------------------------------------------
        // Status filtering
        // ---------------------------------------------------------

        if ($phase === 'demo') {
            $query->whereIn(
                'r.residence_activation_status_id',
                $this->demoActivationStatusIds
            );
        }

        if ($phase === 'cancelled') {
            $query->whereIn(
                'r.residence_activation_status_id',
                $this->cancelledActivationStatusIds
            );
        }

        if ($phase === 'non_demo') {
            $excludedStatuses = array_unique(
                array_merge(
                    $this->demoActivationStatusIds,
                    $this->cancelledActivationStatusIds
                )
            );

            $query->whereNotIn(
                'r.residence_activation_status_id',
                $excludedStatuses
            );
        }

        return $query;
    }

    /**
     * Final cleanup output.
     *
     * Named finishCleanup() instead of complete()
     * because Laravel's Command class already has a
     * complete() method.
     */
    private function finishCleanup(
        int $deletedTotal,
        int $demoDeleted,
        int $cancelledDeleted,
        int $nonDemoDeleted,
        float $startedAt
    ): int {
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
            'CLEANUP COMPLETED'
        );

        $this->line(
            '======================================================'
        );

        $this->info(
            'Total deleted      : ' .
            number_format($deletedTotal)
        );

        $this->info(
            'Demo deleted       : ' .
            number_format($demoDeleted)
        );

        $this->info(
            'Cancelled deleted  : ' .
            number_format($cancelledDeleted)
        );

        $this->info(
            'Non-demo deleted   : ' .
            number_format($nonDemoDeleted)
        );

        $this->info(
            'Execution time     : ' .
            $elapsed .
            ' seconds'
        );

        $this->info(
            'Average rate       : ' .
            number_format($rate) .
            '/sec'
        );

        $this->line(
            'Delete type        : PERMANENT'
        );

        $this->line(
            '======================================================'
        );

        Log::info(
            'Uncheckout visitor logs cleanup completed',
            [
                'deleted_total' =>
                    $deletedTotal,

                'demo_deleted' =>
                    $demoDeleted,

                'cancelled_deleted' =>
                    $cancelledDeleted,

                'non_demo_deleted' =>
                    $nonDemoDeleted,

                'execution_time' =>
                    $elapsed,

                'rate_per_second' =>
                    $rate,
            ]
        );

        return self::SUCCESS;
    }
}
