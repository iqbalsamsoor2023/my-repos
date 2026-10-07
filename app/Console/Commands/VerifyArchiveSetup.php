<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class VerifyArchiveSetup extends Command
{
    protected $signature = 'archive:verify-setup
        {--table= : Verify specific table only}
        {--show-partitions : Show partition details}
        {--check-data : Check for data distribution}';

    protected $description = 'Verify archive tables are properly configured';

    /** @var array<string, mixed> */
    protected array $config = [];

    public function handle(): int
    {
        $this->info("🔍 Verifying archive system setup...\n");

        $this->config = config('partitioning', []);
        $tables = $this->resolveTablesToVerify();

        if ($tables === []) {
            return self::FAILURE;
        }

        $allGood = true;

        foreach ($tables as $mainTable => $dateColumn) {
            try {
                $status = $this->verifyTable($mainTable, $dateColumn);
            } catch (Throwable $exception) {
                $status = false;
                $this->error("  ❌ Verification failed for {$mainTable}: {$exception->getMessage()}");
            }

            if (! $status) {
                $allGood = false;
            }
        }

        $this->info("\n".($allGood ? '✅ All archive tables are properly configured!' : '⚠️  Some issues found - see details above'));

        return $allGood ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, string> */
    private function resolveTablesToVerify(): array
    {
        $tables = (array) data_get($this->config, 'tables', []);

        if ($tables === []) {
            $this->error('No tables configured in partitioning config.');

            return [];
        }

        $specificTable = (string) $this->option('table');
        if ($specificTable === '') {
            return $tables;
        }

        if (! array_key_exists($specificTable, $tables)) {
            $this->error("Table '{$specificTable}' not found in partitioning config.");

            return [];
        }

        return [$specificTable => (string) $tables[$specificTable]];
    }

    private function verifyTable(string $mainTable, string $dateColumn): bool
    {
        $archiveTable = $mainTable.(string) data_get($this->config, 'archive_suffix', '_archive');
        $this->info("📋 Verifying: {$mainTable} -> {$archiveTable}");

        $allGood = true;
        $checks = [
            'Main table exists' => Schema::hasTable($mainTable),
            'Archive table exists' => Schema::hasTable($archiveTable),
            'Archive has created_date column' => Schema::hasColumn($archiveTable, 'created_date'),
            'Archive is partitioned' => $this->isTablePartitioned($archiveTable),
            'Archive partition layout is monthly' => $this->hasMonthlyPartitionLayout($archiveTable),
            'Primary key includes partition column' => $this->hasCorrectPrimaryKey($archiveTable),
        ];

        foreach ($checks as $check => $result) {
            $status = $result ? '✅' : '❌';
            $this->line("  {$status} {$check}");
            if (! $result) {
                $allGood = false;
            }
        }

        // Show partition information if requested
        if ($this->option('show-partitions') && Schema::hasTable($archiveTable)) {
            $this->showPartitionInfo($archiveTable);
        }

        // Show data distribution if requested
        if ($this->option('check-data') && Schema::hasTable($mainTable) && Schema::hasTable($archiveTable)) {
            $this->showDataDistribution($mainTable, $archiveTable, $dateColumn);
        }

        $this->line('');

        return $allGood;
    }

    private function isTablePartitioned(string $table): bool
    {
        $result = DB::selectOne("
            SELECT COUNT(*) as partitioned
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND create_options LIKE '%partitioned%'
        ", [$table]);

        return $result && $result->partitioned > 0;
    }

    private function hasCorrectPrimaryKey(string $table): bool
    {
        $primaryKeys = DB::select("
            SELECT COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND CONSTRAINT_NAME = 'PRIMARY'
            ORDER BY ORDINAL_POSITION
        ", [$table]);

        $columns = array_column($primaryKeys, 'COLUMN_NAME');

        return in_array('created_date', $columns, true) && in_array('id', $columns, true);
    }

    private function hasMonthlyPartitionLayout(string $archiveTable): bool
    {
        $partitions = collect(DB::select(
            "
            SELECT
                PARTITION_NAME,
                PARTITION_DESCRIPTION,
                PARTITION_ORDINAL_POSITION
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND PARTITION_NAME IS NOT NULL
              AND PARTITION_DESCRIPTION != 'MAXVALUE'
            ORDER BY PARTITION_ORDINAL_POSITION
            ",
            [$archiveTable]
        ));

        if ($partitions->isEmpty()) {
            return false;
        }

        $boundaries = [];

        foreach ($partitions as $partition) {
            $raw = trim((string) $partition->PARTITION_DESCRIPTION, "'");

            try {
                $boundaries[] = Carbon::parse($raw)->startOfDay();
            } catch (Throwable) {
                return false;
            }
        }

        for ($index = 1; $index < count($boundaries); $index++) {
            $expected = $boundaries[$index - 1]->copy()->addMonthNoOverflow();

            if (! $boundaries[$index]->equalTo($expected)) {
                return false;
            }
        }

        if (Schema::hasColumn($archiveTable, 'created_date')) {
            $oldestDate = DB::table($archiveTable)->min('created_date');

            if ($oldestDate) {
                $firstPartitionMonth = $boundaries[0]->copy()->subMonthNoOverflow()->startOfMonth();
                $oldestMonth = Carbon::parse($oldestDate)->startOfMonth();

                if ($oldestMonth->lt($firstPartitionMonth)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function showPartitionInfo(string $archiveTable): void
    {
        $partitions = DB::select('
            SELECT
                partition_name,
                partition_description,
                table_rows
            FROM information_schema.partitions
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND partition_name IS NOT NULL
            ORDER BY partition_ordinal_position
        ', [$archiveTable]);

        if (! empty($partitions)) {
            $this->line('    📊 Partition Details:');
            foreach ($partitions as $partition) {
                $partitionName = $partition->partition_name ?? $partition->PARTITION_NAME ?? 'unknown';
                $partitionDescription = $partition->partition_description ?? $partition->PARTITION_DESCRIPTION ?? 'UNKNOWN';
                $rows = number_format($partition->table_rows ?? $partition->TABLE_ROWS ?? 0);

                $boundary = $partitionDescription === 'MAXVALUE'
                    ? 'MAXVALUE'
                    : "< {$partitionDescription}";
                $this->line("      - {$partitionName}: {$rows} rows ({$boundary})");
            }
        }
    }

    private function showDataDistribution(string $mainTable, string $archiveTable, string $dateColumn): void
    {
        $safeMainTable = $this->quoteIdentifier($mainTable);
        $safeArchiveTable = $this->quoteIdentifier($archiveTable);
        $safeDateColumn = $this->quoteIdentifier($dateColumn);

        // Check data in main table
        $mainStats = DB::selectOne(sprintf(
            'SELECT COUNT(*) as total_records, MIN(%s) as oldest_date, MAX(%s) as newest_date FROM %s',
            $safeDateColumn,
            $safeDateColumn,
            $safeMainTable,
        ));

        // Check data in archive table (using partition column if available)
        $partitionColumn = Schema::hasColumn($archiveTable, 'created_date') ? 'created_date' : 'created_at';
        $safePartitionColumn = $this->quoteIdentifier($partitionColumn);
        $archiveStats = DB::selectOne(sprintf(
            'SELECT COUNT(*) as total_records, MIN(%s) as oldest_date, MAX(%s) as newest_date FROM %s',
            $safePartitionColumn,
            $safePartitionColumn,
            $safeArchiveTable,
        ));

        $this->line('    📈 Data Distribution:');
        $this->line('      Main table: '.number_format($mainStats->total_records ?? 0).' records');
        if ($mainStats->oldest_date) {
            $this->line("        Date range: {$mainStats->oldest_date} to {$mainStats->newest_date}");
        }

        $this->line('      Archive table: '.number_format($archiveStats->total_records ?? 0).' records');
        if ($archiveStats->oldest_date) {
            $this->line("        Date range: {$archiveStats->oldest_date} to {$archiveStats->newest_date}");
        }

        if ($mainStats->oldest_date && $archiveStats->newest_date) {
            $mainOldest = Carbon::parse($mainStats->oldest_date);
            $archiveNewest = Carbon::parse($archiveStats->newest_date);

            if ($mainOldest->lte($archiveNewest)) {
                $this->line('      ⚠️  Potential date overlap detected');
            } else {
                $this->line('      ✅ No date overlap');
            }
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException("Invalid identifier: {$identifier}");
        }

        return "`{$identifier}`";
    }
}
