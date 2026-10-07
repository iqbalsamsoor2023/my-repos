<?php

namespace App\Console\Commands\Vms;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Keeps the VMS archive tables partitioned by month.
 *
 * Why this exists
 * ---------------
 * The three archive tables are RANGE-partitioned on `created_date`, but nothing ever
 * created new partitions. Everything past the last boundary fell into the `pmax`
 * catch-all, which had already grown to ~1.9M rows. Two consequences:
 *
 *   1. `pmax` grows without bound, so partitioning stops buying any pruning.
 *   2. Expired data can only be removed row by row. `DROP PARTITION` removes a whole
 *      month instantly *and* returns the disk to the OS, which a `DELETE` never does.
 *
 * What this command does NOT do
 * -----------------------------
 * It never deletes personal data. `data-deletion:vms-archive` remains the only thing
 * that purges records, because a row also owns COS objects (visitor images, parking
 * vouchers) and `media` rows that live outside these tables — dropping a partition
 * would leave those behind, which would breach PDPA.
 *
 * This command only drops partitions that are ALREADY EMPTY, so it reclaims the disk
 * the purge left fragmented and keeps the partition list bounded. An empty partition
 * cannot, by definition, take data with it.
 *
 * Safety
 * ------
 * - Never drops `pmax`; without a catch-all, an insert past the last boundary fails.
 * - Never drops a partition that still holds rows, even if it is past retention.
 * - Only considers a partition expired when its whole range sits before the cutoff.
 * - Every DDL statement is printed; `--dry-run` prints without executing.
 *
 * Logging: one line per run to storage/logs/vms-<date>.log (channel `vms`, see
 * config/logging.php) — partitions added/dropped on success, nothing on --dry-run,
 * errors/skips otherwise. See docs/archive/ARCHIVE_README.md.
 */
class MaintainArchivePartitions extends Command
{
    protected $signature = 'vms:maintain-archive-partitions
        {--ensure-months=6 : Ensure monthly partitions exist this many months ahead}
        {--retention-months=12 : Must match data-deletion:vms-archive; a partition is only droppable once entirely older than this}
        {--drop-empty : Drop expired partitions that the purge has already emptied}
        {--dry-run : Print the DDL without running it}';

    protected $description = 'Provision monthly partitions on the VMS archive tables and reclaim empty expired ones';

    /** All three share the same `created_date` partition key and must stay aligned. */
    private const TABLES = [
        'visitor_logs_archive',
        'visiting_arrangements_archive',
        'visitor_parkings_archive',
    ];

    private const CATCH_ALL = 'pmax';

    /**
     * REORGANIZE rewrites the partition and blocks writes to the table while it runs —
     * on the first pass that is tens of minutes. Nothing stops a second invocation
     * queueing another rewrite of the same table behind the first, so runs are
     * serialised on a MySQL advisory lock: it spans app servers and MySQL frees it
     * automatically if the process dies.
     */
    private const LOCK_NAME = 'vms:maintain-archive-partitions';

    private bool $dryRun = false;

    /** Tallied across all three tables for the single completion log line. */
    private int $partitionsAdded = 0;

    private int $partitionsDropped = 0;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $ensureMonths = max(1, (int) $this->option('ensure-months'));
        $retentionMonths = max(1, (int) $this->option('retention-months'));

        if ($this->dryRun) {
            $this->warn('DRY RUN — no DDL will be executed.');
        }

        if (! $this->dryRun && ! $this->acquireLock()) {
            $this->error('Partition maintenance is already running. Refusing to queue a second rewrite.');
            Log::channel('vms')->warning('vms:maintain-archive-partitions skipped: already running');

            return self::FAILURE;
        }

        try {
            return $this->maintain($ensureMonths, $retentionMonths);
        } finally {
            if (! $this->dryRun) {
                DB::selectOne('SELECT RELEASE_LOCK(?) AS r', [self::LOCK_NAME]);
            }
        }
    }

    private function acquireLock(): bool
    {
        return (int) DB::selectOne('SELECT GET_LOCK(?, 0) AS ok', [self::LOCK_NAME])->ok === 1;
    }

    private function maintain(int $ensureMonths, int $retentionMonths): int
    {
        foreach (self::TABLES as $table) {
            $this->newLine();
            $this->info("── {$table}");

            if (! $this->isPartitioned($table)) {
                $this->warn('  not partitioned — skipping.');

                continue;
            }

            try {
                $this->provisionMonths($table, $ensureMonths);

                if ($this->option('drop-empty')) {
                    $this->dropEmptyExpired($table, $retentionMonths);
                }
            } catch (Throwable $e) {
                $this->error("  FAILED: {$e->getMessage()}");
                Log::channel('vms')->error('vms:maintain-archive-partitions failed', ['table' => $table, 'error' => $e->getMessage()]);

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Done.');

        if (! $this->dryRun) {
            Log::channel('vms')->info('vms:maintain-archive-partitions completed', [
                'tables' => count(self::TABLES),
                'partitions_added' => $this->partitionsAdded,
                'partitions_dropped' => $this->partitionsDropped,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Split the catch-all so every month up to `now + $ahead` has its own partition.
     *
     * The first run also has to absorb whatever `pmax` already accumulated, so it
     * rewrites those rows and can take minutes. Afterwards `pmax` is empty and every
     * later run is near-instant.
     */
    private function provisionMonths(string $table, int $ahead): void
    {
        $existing = $this->partitions($table);

        if (! array_key_exists(self::CATCH_ALL, $existing)) {
            $this->warn('  no '.self::CATCH_ALL.' catch-all — cannot extend safely. Skipping.');

            return;
        }

        // Start at the highest existing boundary: re-covering a month that already has a
        // partition would make REORGANIZE reject the range as overlapping.
        $month = $this->highestBoundary($existing);
        $target = CarbonImmutable::today()->addMonths($ahead)->startOfMonth();

        $boundaries = [];

        while ($month->lte($target)) {
            $boundaries[$this->partitionName($month)] = $month->addMonth()->toDateString();
            $month = $month->addMonth();
        }

        if ($boundaries === []) {
            $this->line("  already provisioned {$ahead} months ahead.");

            return;
        }

        $rowsInCatchAll = $this->rowsIn($table, self::CATCH_ALL);

        if ($rowsInCatchAll > 0) {
            $this->warn(sprintf(
                '  %s holds %s rows; they get rewritten into monthly partitions (one-off, can take minutes).',
                self::CATCH_ALL,
                number_format($rowsInCatchAll),
            ));
        }

        $parts = [];

        foreach ($boundaries as $name => $lessThan) {
            $parts[] = "PARTITION {$name} VALUES LESS THAN ('{$lessThan}')";
        }

        $parts[] = 'PARTITION '.self::CATCH_ALL.' VALUES LESS THAN (MAXVALUE)';

        $this->line('  + '.implode(', ', array_keys($boundaries)));

        $this->applyDdl(sprintf(
            'ALTER TABLE `%s` REORGANIZE PARTITION %s INTO (%s)',
            $table,
            self::CATCH_ALL,
            implode(', ', $parts),
        ));

        if (! $this->dryRun) {
            $this->partitionsAdded += count($boundaries);
        }
    }

    /**
     * Drop expired partitions, but only the ones the purge has already emptied.
     *
     * A partition counts as expired only when its upper boundary is at or before the
     * cutoff, which means no row inside it can still be within retention.
     */
    private function dropEmptyExpired(string $table, int $retentionMonths): void
    {
        $cutoff = CarbonImmutable::today()->subMonths($retentionMonths)->startOfMonth();

        foreach ($this->partitions($table) as $name => $lessThan) {
            // The catch-all has no upper bound; dropping it would break future inserts.
            if ($name === self::CATCH_ALL || $lessThan === null) {
                continue;
            }

            if (CarbonImmutable::parse($lessThan)->gt($cutoff)) {
                continue;   // range still overlaps retained data
            }

            $rows = $this->rowsIn($table, $name);

            if ($rows > 0) {
                $this->line(sprintf(
                    '  %s expired but holds %s rows — left for data-deletion:vms-archive (COS + media must go first).',
                    $name,
                    number_format($rows),
                ));

                continue;
            }

            $this->line("  - {$name} (expired and empty)");
            $this->applyDdl("ALTER TABLE `{$table}` DROP PARTITION {$name}");
            $this->partitionsDropped++;
        }
    }

    /**
     * Exact count, not the estimate in information_schema: this decides whether the
     * partition gets dropped, so it has to be right.
     */
    private function rowsIn(string $table, string $partition): int
    {
        return (int) DB::selectOne("SELECT COUNT(*) AS c FROM `{$table}` PARTITION ({$partition})")->c;
    }

    /**
     * @return array<string, string|null> partition name => upper bound (null = MAXVALUE)
     */
    private function partitions(string $table): array
    {
        $rows = DB::select(
            'SELECT PARTITION_NAME AS name, PARTITION_DESCRIPTION AS bound
             FROM information_schema.PARTITIONS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND PARTITION_NAME IS NOT NULL
             ORDER BY PARTITION_ORDINAL_POSITION',
            [$table]
        );

        $out = [];

        foreach ($rows as $row) {
            $bound = trim((string) $row->bound, "'");
            $out[$row->name] = strtoupper($bound) === 'MAXVALUE' ? null : $bound;
        }

        return $out;
    }

    /**
     * The month the next partition must start at: the highest existing upper bound.
     *
     * @param  array<string, string|null>  $partitions
     */
    private function highestBoundary(array $partitions): CarbonImmutable
    {
        $bounds = array_filter($partitions, static fn (?string $bound): bool => $bound !== null);

        if ($bounds === []) {
            return CarbonImmutable::today()->startOfMonth();
        }

        return CarbonImmutable::parse(max($bounds))->startOfMonth();
    }

    private function partitionName(CarbonImmutable $month): string
    {
        return 'p'.$month->format('Ym');
    }

    private function isPartitioned(string $table): bool
    {
        return $this->partitions($table) !== [];
    }

    private function applyDdl(string $sql): void
    {
        $this->line('    '.$sql);

        if ($this->dryRun) {
            return;
        }

        $started = microtime(true);
        DB::statement($sql);
        $this->line(sprintf('    ok (%.1fs)', microtime(true) - $started));
    }
}
