<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SetupArchiveTables extends Command
{
    protected $signature = 'archive:setup-tables
        {--table= : Specific table to setup (optional)}
        {--dry-run : Show SQL commands without executing}
        {--force : Skip confirmation prompts}
        {--recreate : Drop and recreate archive tables if they exist}
        {--skip-partitioning : Only setup table structure, skip partitioning}';

    protected $description = 'Setup archive tables with proper partitioning structure';

    /** @var array<string, mixed> */
    protected array $config = [];

    protected bool $hasErrors = false;

    public function handle(): int
    {
        $this->config = config('partitioning', []);

        if (empty($this->config['tables'])) {
            $this->error('No tables configured in partitioning config.');

            return self::FAILURE;
        }

        try {
            $tablesToSetup = $this->resolveTargetTables();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("🚀 Setting up archive tables with partitioning...\n");

        foreach ($tablesToSetup as $mainTable => $dateColumn) {
            try {
                $this->setupArchiveTable($mainTable, $dateColumn);
            } catch (RuntimeException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        if ($this->hasErrors) {
            $this->warn("\n⚠️  Archive table setup finished with warnings. Review output above.");

            return self::FAILURE;
        }

        $this->info("\n✅ Archive table setup completed!");

        return self::SUCCESS;
    }

    /** @return array<string, string> */
    private function resolveTargetTables(): array
    {
        $specificTable = (string) $this->option('table');

        if ($specificTable !== '') {
            if (! isset($this->config['tables'][$specificTable])) {
                throw new InvalidArgumentException("Table '{$specificTable}' not found in configuration.");
            }

            return [$specificTable => $this->config['tables'][$specificTable]];
        }

        return $this->config['tables'];
    }

    private function setupArchiveTable(string $mainTable, string $dateColumn): void
    {
        $archiveTable = $mainTable.$this->config['archive_suffix'];

        $this->info("📋 Setting up archive table: {$mainTable} -> {$archiveTable}");

        // Check if main table exists
        if (! Schema::hasTable($mainTable)) {
            $this->error("  ❌ Main table {$mainTable} does not exist");
            $this->hasErrors = true;

            return;
        }

        // Step 1: Create archive table if it doesn't exist
        if (! Schema::hasTable($archiveTable)) {
            $this->createArchiveTable($mainTable, $archiveTable);
        } elseif ($this->option('recreate')) {
            $this->recreateArchiveTable($mainTable, $archiveTable);
        } else {
            $this->info("  ✅ Archive table {$archiveTable} already exists");
        }

        // Step 2: Add partition column if needed
        $this->addPartitionColumn($archiveTable);

        // Step 3: Setup primary key with partition column
        $this->setupPrimaryKey($archiveTable);

        // Step 4: Handle unique indexes
        $this->handleUniqueIndexes($archiveTable);

        // Step 5: Setup performance indexes for filtering
        $this->setupPerformanceIndexes($archiveTable, $mainTable);

        // Step 6: Setup partitioning
        if (! $this->option('skip-partitioning')) {
            $this->setupPartitioning($archiveTable, $mainTable, $dateColumn);
        } else {
            $this->info('  ⏭️  Skipping partitioning setup');
        }

        $this->info("  ✅ {$archiveTable} setup completed\n");
    }

    private function createArchiveTable(string $mainTable, string $archiveTable): void
    {
        $sql = "CREATE TABLE `{$archiveTable}` LIKE `{$mainTable}`";

        $this->info('  📝 Creating archive table...');
        $this->executeSql($sql, "create archive table {$archiveTable}");
    }

    private function addPartitionColumn(string $archiveTable): void
    {
        if (Schema::hasColumn($archiveTable, 'created_date')) {
            $this->info("  ✅ Partition column 'created_date' already exists");

            return;
        }

        $sql = "ALTER TABLE `{$archiveTable}`
                ADD COLUMN created_date DATE GENERATED ALWAYS AS (DATE(created_at)) STORED";

        $this->info("  📝 Adding partition column 'created_date'...");
        $this->executeSql($sql, "add partition column to {$archiveTable}");
    }

    private function setupPrimaryKey(string $archiveTable): void
    {
        // Check current primary key
        $primaryKey = $this->getCurrentPrimaryKey($archiveTable);

        if ($primaryKey && in_array('created_date', $primaryKey)) {
            $this->info('  ✅ Primary key already includes partition column');

            return;
        }

        $this->info('  📝 Setting up composite primary key for partitioning...');

        try {
            // Remove auto_increment and setup composite primary key for partitioning
            $modifySql = "ALTER TABLE `{$archiveTable}`
                         MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL,
                         DROP PRIMARY KEY,
                         ADD PRIMARY KEY (id, created_date)";

            $this->executeSql($modifySql, "setup composite primary key for {$archiveTable}");
            $this->info('  ✅ Composite primary key configured for partitioning');

        } catch (Throwable $e) {
            $this->error('  ❌ Failed to setup primary key: '.$e->getMessage());

            // Try step-by-step approach as fallback
            try {
                $this->info('  🔄 Trying step-by-step approach...');

                $this->executeSql("ALTER TABLE `{$archiveTable}` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL",
                    'remove auto_increment');
                $this->executeSql("ALTER TABLE `{$archiveTable}` DROP PRIMARY KEY",
                    'drop primary key');
                $this->executeSql("ALTER TABLE `{$archiveTable}` ADD PRIMARY KEY (id, created_date)",
                    'add composite primary key');

                $this->info('  ✅ Composite primary key configured successfully');
            } catch (Throwable $e2) {
                $this->error('  ❌ Step-by-step approach also failed: '.$e2->getMessage());
                $this->hasErrors = true;
            }
        }
    }

    private function getCurrentPrimaryKey(string $archiveTable): ?array
    {
        $result = DB::select("
            SELECT COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND CONSTRAINT_NAME = 'PRIMARY'
            ORDER BY ORDINAL_POSITION
        ", [$archiveTable]);

        return empty($result) ? null : array_column($result, 'COLUMN_NAME');
    }

    private function handleUniqueIndexes(string $archiveTable): void
    {
        // Get unique indexes that don't include the partition column
        $uniqueIndexes = collect(DB::select("
            SELECT DISTINCT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
            FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND NON_UNIQUE = 0
              AND INDEX_NAME != 'PRIMARY'
            GROUP BY INDEX_NAME
            HAVING FIND_IN_SET('created_date', columns) = 0
        ", [$archiveTable]));

        if ($uniqueIndexes->isEmpty()) {
            $this->info('  ✅ No unique indexes need modification');

            return;
        }

        $this->info('  📝 Converting unique indexes to regular indexes for partitioning...');

        foreach ($uniqueIndexes as $index) {
            $indexName = $index->INDEX_NAME;
            $columns = $index->columns;

            try {
                // Convert unique index to regular index (required for partitioning)
                $sql = "ALTER TABLE `{$archiveTable}`
                       DROP INDEX `{$indexName}`,
                       ADD INDEX `{$indexName}` ({$columns})";

                $this->executeSql($sql, "convert unique index {$indexName} to regular index");
            } catch (Throwable $e) {
                $this->warn("  ⚠️  Could not convert index {$indexName}: ".$e->getMessage());
            }
        }
    }

    private function setupPartitioning(string $archiveTable, string $mainTable, string $dateColumn): void
    {
        // Check if table is already partitioned
        $isPartitioned = DB::selectOne("
            SELECT COUNT(*) as partitioned
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND create_options LIKE '%partitioned%'
        ", [$archiveTable]);

        if ($isPartitioned && $isPartitioned->partitioned) {
            $this->info("  ✅ Table {$archiveTable} is already partitioned");

            return;
        }

        $startMonth = $this->resolvePartitioningStartMonth($mainTable, $archiveTable, $dateColumn);
        $futurePartitions = max(0, (int) data_get($this->config, 'partitions.future_partitions', 2));
        $horizonExclusive = Carbon::now()->startOfMonth()->addMonthsNoOverflow($futurePartitions + 1);

        if (! $startMonth->lt($horizonExclusive)) {
            $startMonth = Carbon::now()->startOfMonth();
        }

        $partitionDefinitions = [];
        $cursor = $startMonth->copy();

        while ($cursor->lt($horizonExclusive)) {
            $nextBoundary = $cursor->copy()->addMonthNoOverflow();
            $partitionDefinitions[] = sprintf(
                "PARTITION `p%s` VALUES LESS THAN ('%s')",
                $cursor->format('Ym'),
                $nextBoundary->format('Y-m-d')
            );

            $cursor = $nextBoundary;
        }

        $partitionDefinitions[] = 'PARTITION pmax VALUES LESS THAN (MAXVALUE)';

        $sql = "ALTER TABLE `{$archiveTable}`
                PARTITION BY RANGE COLUMNS (created_date) (
                    ".implode(",\n                    ", $partitionDefinitions).'
                )';

        $this->info('  📝 Setting up partitioning...');
        $this->executeSql($sql, "setup partitioning on {$archiveTable}");
    }

    private function resolvePartitioningStartMonth(string $mainTable, string $archiveTable, string $dateColumn): Carbon
    {
        $archiveColumn = Schema::hasColumn($archiveTable, 'created_date') ? 'created_date' : $dateColumn;
        $sources = [
            [$archiveTable, $archiveColumn],
            [$mainTable, $dateColumn],
        ];

        foreach ($sources as [$table, $column]) {
            try {
                $result = DB::selectOne("SELECT MIN(`{$column}`) AS oldest_date FROM `{$table}`");

                if ($result && $result->oldest_date) {
                    return Carbon::parse($result->oldest_date)->startOfMonth();
                }
            } catch (Throwable) {
                continue;
            }
        }

        return Carbon::now()->startOfMonth();
    }

    private function setupPerformanceIndexes(string $archiveTable, string $mainTable): void
    {
        $this->info('  📝 Setting up performance indexes for filtering...');

        $tableIndexes = $this->getTableSpecificIndexes($mainTable);

        if (empty($tableIndexes)) {
            $this->info("    ℹ️  No specific performance indexes configured for {$mainTable}");

            return;
        }

        foreach ($tableIndexes as $indexName => $columns) {
            try {
                $missingColumns = [];
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($archiveTable, $column)) {
                        $missingColumns[] = $column;
                    }
                }

                if (! empty($missingColumns)) {
                    $this->warn("    ⏭️  Skipping index {$indexName} - missing columns: ".implode(', ', $missingColumns));

                    continue;
                }

                $indexExists = DB::selectOne('
                    SELECT COUNT(*) as exists_count
                    FROM information_schema.STATISTICS
                    WHERE table_schema = DATABASE()
                      AND table_name = ?
                      AND index_name = ?
                ', [$archiveTable, $indexName]);

                if ($indexExists && $indexExists->exists_count > 0) {
                    $this->info("    ✅ Index {$indexName} already exists");

                    continue;
                }

                $columnsList = implode(', ', array_map(fn ($col) => "`{$col}`", $columns));
                $sql = "ALTER TABLE `{$archiveTable}` ADD INDEX `{$indexName}` ({$columnsList})";

                $this->executeSql($sql, "create index {$indexName}");
                $this->info("    ✅ Created index: {$indexName}");

            } catch (Throwable $e) {
                $this->warn("    ⚠️  Could not create index {$indexName}: ".$e->getMessage());
            }
        }
    }

    private function getTableSpecificIndexes(string $mainTable): array
    {
        $indexMap = [
            'visitor_logs' => [
                'idx_created_at' => ['created_at'],
                'idx_arrival_leave' => ['arrival_time', 'leave_time'],
                'idx_created_allowed' => ['created_at', 'is_allowed'],
            ],
            'visiting_arrangements' => [
                'idx_created_at' => ['created_at'],
                'idx_residence_id' => ['residence_id'],
                'idx_unit_id' => ['unit_id'],
            ],
            'visitor_parkings' => [
                'idx_created_at' => ['created_at'],
                'idx_residence_id' => ['residence_id'],
            ],
        ];

        return $indexMap[$mainTable] ?? [];
    }

    private function recreateArchiveTable(string $mainTable, string $archiveTable): void
    {
        $this->warn('  ⚠️  Recreating archive table (existing data will be lost)...');

        if (! $this->option('force')) {
            if (! $this->confirm("    Are you sure you want to drop and recreate {$archiveTable}?", false)) {
                $this->info("    Skipped recreation of {$archiveTable}");

                return;
            }
        }

        // Drop existing table
        $dropSql = "DROP TABLE IF EXISTS `{$archiveTable}`";
        $this->executeSql($dropSql, "drop existing archive table {$archiveTable}");

        // Create new table
        $this->createArchiveTable($mainTable, $archiveTable);
    }

    private function executeSql(string $sql, string $operation): bool
    {
        if ($this->option('dry-run')) {
            $this->line("  [DRY RUN] SQL: {$sql}");

            return true;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("    Execute: {$operation}?", true)) {
                $this->info("    Skipped: {$operation}");

                return false;
            }
        }

        try {
            DB::statement($sql);
            $this->info("    ✅ Completed: {$operation}");

            return true;
        } catch (Throwable $e) {
            $this->error("    ❌ Failed: {$operation} - ".$e->getMessage());
            $this->hasErrors = true;

            if (! $this->option('force') && ! $this->confirm('    Continue despite error?', false)) {
                throw new RuntimeException('Archive setup stopped after SQL error.');
            }

            return false;
        }
    }
}
