<?php

namespace App\Console\Commands;

use App\Helpers\SlackNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Pulse\Facades\Pulse;
use RuntimeException;
use Throwable;

/**
 * Simple, safe archiving for visitor data with large dataset optimization
 *
 * A visitor_logs row is eligible when EITHER rule matches (see eligibilityClause):
 * 1. It is older than --max-age-days (default 365), regardless of leave_time. This
 *    sweeps out stale visits that never got a check-out scan.
 * 2. It is a completed visit (leave_time IS NOT NULL) older than --retain-days
 *    (default 30).
 *
 * Safety guarantees:
 * - Children are archived BEFORE their parent (prevents FK errors).
 * - Archive INSERT + verification + DELETE happen in one transaction (atomic).
 * - A row is only deleted from the live table after it is confirmed present in the
 *   archive, so an interrupted or overlapping run can never lose data.
 * - Re-running is always safe: eligible rows still in a live table are moved out,
 *   duplicate inserts are skipped.
 *
 * Flow:
 * Step 1: Find eligible parents (see eligibilityClause)
 * Step 2: Archive children of those parents (parent eligibility drives the child)
 * Step 3: Archive parents (only once no children remain in the live tables)
 *
 * Logging: one line per run to storage/logs/vms-<date>.log (channel `vms`, see
 * config/logging.php) — final totals on success, nothing on --dry-run, errors/skips
 * otherwise. See docs/archive/ARCHIVE_README.md.
 */
class ArchiveVisitorDataSafely extends Command
{
    private const PARENT_TABLE = 'visitor_logs';

    /**
     * Every statement in a batch drives off an `id IN (...)` list, and MySQL abandons
     * the primary key once that list gets long — measured on staging:
     *
     *     4,000 ids -> PRIMARY, 4,000 rows scanned,       221ms
     *     5,000 ids -> created_at index, 4.38M scanned, 5,447ms   (full table scan)
     *
     * Past the cliff each batch scans the whole table, which is what made a run take
     * ~17.7h and (under REPEATABLE READ) lock ~4.7M rows at a time. Batches are kept
     * well below it so the copy, the verification and the delete all stay on the PK.
     */
    private const DEFAULT_BATCH_SIZE = 1000;

    /** Hard ceiling, even if --batch-size asks for more. Keeps the plan on the PK. */
    private const MAX_BATCH_SIZE = 2000;

    private const PARENT_ID_CHUNK_SIZE = 1000;

    /** Advisory lock name; shared by scheduled and manual runs alike. */
    private const LOCK_NAME = 'archive:visitor-data-safely';

    private const MICROSECONDS_PER_SECOND = 1000000;

    protected $signature = 'archive:visitor-data-safely
        {--dry-run : Simulate archiving without moving data}
        {--force : Do not ask for confirmation}
        {--retain-days=30 : Archive completed visits (leave_time set) older than N days}
        {--max-age-days=365 : Archive ANY record older than N days, even with NULL leave_time}
        {--max-per-run=500000 : Maximum parent records to archive per run}
        {--batch-size= : Records per insert+delete transaction (default 10000)}
        {--chunk-fetch=10000 : Parent IDs to fetch per window}
        {--sleep=0.1 : Seconds to sleep between batches (prevent DB overload)}';

    protected $description = 'Archive visitor_logs and children safely (12M+ row optimized)';

    protected array $stats = [];

    /**
     * Only one archive run may touch the visitor tables at a time.
     *
     * The scheduler's `withoutOverlapping()` does not help here: it guards scheduled
     * invocations only, so a manual `php artisan archive:visitor-data-safely` run
     * happily starts alongside the scheduled one. Two concurrent runs select the same
     * oldest rows and race each other's deletes, which is what produced the
     * "5000 rows selected but only 0 present" failure. This lock covers both.
     */
    public function handle(): int
    {
        // A MySQL advisory lock rather than a cache lock, deliberately:
        //   - it lives on the database every app server shares, so it holds across
        //     servers even when the cache driver is per-server (file/array);
        //   - MySQL frees it automatically if the connection drops, so a killed run
        //     cannot leave a stale lock blocking the scheduler.
        if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS ok', [self::LOCK_NAME])->ok !== 1) {
            $this->error('Another archive run is already in progress. Refusing to start a second one.');
            $this->line("Concurrent runs race each other's deletes and corrupt the consistency check.");
            Log::channel('vms')->warning('archive:visitor-data-safely skipped: already running');

            return self::FAILURE;
        }

        try {
            return $this->runArchive();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [self::LOCK_NAME]);
        }
    }

    /**
     * Drop to READ COMMITTED, but only where MySQL allows it.
     *
     * With binary logging enabled in STATEMENT format, MySQL refuses READ COMMITTED for
     * InnoDB writes (error 1665) and the whole run would die on its first insert.
     * Staging has binlog off, but production very likely has it on for replication and
     * backups, so this must be checked rather than assumed.
     *
     * Falling back to REPEATABLE READ is safe: the run lock above is what actually
     * prevents the concurrency bug. READ COMMITTED is the optimisation that also keeps
     * the copy from taking millions of row locks.
     */
    private function useReadCommittedIfSafe(): void
    {
        $binlogOn = (int) (DB::selectOne('SELECT @@GLOBAL.log_bin AS v')->v ?? 0) === 1;
        $format = strtoupper((string) (DB::selectOne('SELECT @@GLOBAL.binlog_format AS v')->v ?? ''));

        if ($binlogOn && $format === 'STATEMENT') {
            $this->warn('Binary logging is STATEMENT-based; keeping REPEATABLE READ (READ COMMITTED is not permitted).');
            $this->warn('The copy will take more row locks. Avoid running this during peak hours.');

            return;
        }

        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    }

    private function runArchive(): int
    {
        $retainDays = max(1, (int) $this->option('retain-days'));
        $maxAgeDays = max($retainDays, (int) $this->option('max-age-days'));
        $maxPerRun = max(1, (int) $this->option('max-per-run'));
        $chunkFetch = max(1, (int) $this->option('chunk-fetch'));
        $sleep = max(0, (float) $this->option('sleep'));

        // Two retention boundaries:
        // - completedCutoff: completed visits (leave_time set) older than retain-days.
        // - maxAgeCutoff:    ANY record older than max-age-days, even if leave_time is NULL
        //   (stale visits that never checked out must still leave the live table).
        $completedCutoff = Carbon::now()->subDays($retainDays);
        $maxAgeCutoff = Carbon::now()->subDays($maxAgeDays);

        // Capped, not just defaulted: a larger batch pushes the `id IN (...)` list past
        // the point where MySQL drops the primary key and scans the whole table, which
        // makes bigger batches dramatically *slower*, not faster.
        $batchSize = $this->option('batch-size')
            ? max(1, (int) $this->option('batch-size'))
            : self::DEFAULT_BATCH_SIZE;

        if ($batchSize > self::MAX_BATCH_SIZE) {
            $this->warn("Batch size {$batchSize} would force a full table scan per batch; capping at ".self::MAX_BATCH_SIZE.'.');
            $batchSize = self::MAX_BATCH_SIZE;
        }

        DB::connection()->disableQueryLog();
        $this->stopPulseRecording();
        $this->guardAgainstSilentFatal();

        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        // Optimize MySQL session for large operations
        try {
            DB::statement('SET SESSION innodb_lock_wait_timeout = 120');
            DB::statement('SET SESSION wait_timeout = 28800');
            DB::statement('SET SESSION max_execution_time = 0');

            // Under REPEATABLE READ, `INSERT ... SELECT` reads the source table with
            // *shared next-key locks* rather than from the transaction snapshot. Two
            // things follow, and both have bitten us:
            //
            //   1. It locks every row it scans — observed at 4.8M locked rows on one
            //      batch, which stalls everything else touching visitor_logs.
            //   2. That locking read sees the latest committed data, while the plain
            //      SELECTs in the same transaction still see the older snapshot. If a
            //      second archive process commits a delete in between, the copy finds
            //      nothing to insert while the verification still sees the rows —
            //      producing a "0 rows present" failure that looks impossible.
            //
            // READ COMMITTED makes the source read consistent and non-locking, which
            // removes both problems. Rows are still only deleted after they are
            // confirmed present in the archive, so the safety guarantee is unchanged.
            $this->useReadCommittedIfSafe();
        } catch (Throwable) {
            // Silent fail if settings unavailable
        }

        $this->info('Archive Visitor Data - Optimized for Large Datasets');
        $this->line('Completed visits older than: '.$completedCutoff->toDateTimeString()." ({$retainDays} days)");
        $this->line('Any record older than:       '.$maxAgeCutoff->toDateTimeString()." ({$maxAgeDays} days, incl. NULL leave_time)");
        $this->line('Max parents per run: '.number_format($maxPerRun));
        $this->line('Batch size: '.number_format($batchSize));
        $this->line('Chunk fetch: '.number_format($chunkFetch));
        $this->newLine();

        if ($this->option('dry-run')) {
            return $this->dryRun($completedCutoff, $maxAgeCutoff);
        }

        if (! $this->option('force')) {
            if (! $this->confirm("Archive completed visits older than {$retainDays} days, and any record older than {$maxAgeDays} days?")) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            $archiveSuffix = (string) config('partitioning.archive_suffix', '_archive');
            $parentTable = self::PARENT_TABLE;
            $parentArchive = $this->archiveTableName($parentTable, $archiveSuffix);
            $childTables = [
                'visiting_arrangements' => $this->archiveTableName('visiting_arrangements', $archiveSuffix),
                'visitor_parkings' => $this->archiveTableName('visitor_parkings', $archiveSuffix),
            ];

            // Verify tables exist
            foreach (array_merge([$parentTable => $parentArchive], $childTables) as $live => $archive) {
                if (! Schema::hasTable($live) || ! Schema::hasTable($archive)) {
                    $this->error("Missing table: {$live} or {$archive}");

                    return self::FAILURE;
                }
            }

            $totalParentsArchived = 0;
            $lastParentId = 0;

            $this->info('Starting archive process...');
            $this->newLine();

            while ($totalParentsArchived < $maxPerRun) {
                // Calculate how many more parents we can fetch
                $remainingToFetch = $maxPerRun - $totalParentsArchived;
                $fetchLimit = min($chunkFetch, $remainingToFetch);

                // Fetch eligible parent IDs (see eligibilityClause).
                // We deliberately do NOT exclude ids that already exist in the archive:
                // anything still sitting in the live table must be moved out. Duplicate
                // inserts are prevented by insertViaSqlCopy(), and archiveBatch() only
                // deletes after confirming the row is present in the archive.
                $parents = DB::select(
                    "SELECT id FROM {$parentTable}
                     WHERE {$this->eligibilityClause()}
                       AND id > ?
                     ORDER BY id
                     LIMIT ?",
                    [$maxAgeCutoff, $completedCutoff, $lastParentId, $fetchLimit]
                );

                if (empty($parents)) {
                    $this->info('No more eligible records to archive.');
                    break;
                }

                $parentIds = array_column($parents, 'id');
                $lastParentId = (int) end($parentIds);

                $this->line('Processing parent window: ID '.$parentIds[0].' to '.$lastParentId.' ('.count($parentIds).' parents)');

                // Archive children first (prevents FK errors)
                foreach ($childTables as $childTable => $childArchive) {
                    $moved = $this->archiveChildren(
                        $childTable,
                        $childArchive,
                        $parentIds,
                        $completedCutoff,
                        $maxAgeCutoff,
                        $batchSize,
                        $sleep
                    );
                    $this->stats[$childTable] = ($this->stats[$childTable] ?? 0) + $moved;
                    if ($moved > 0) {
                        $this->line("  ✓ {$childTable}: ".number_format($moved).' archived');
                    }
                }

                // Archive parents (only those with no live children)
                $movedParents = $this->archiveParents(
                    $parentTable,
                    $parentArchive,
                    $parentIds,
                    $completedCutoff,
                    $maxAgeCutoff,
                    $batchSize,
                    $sleep
                );

                $this->stats['visitor_logs'] = ($this->stats['visitor_logs'] ?? 0) + $movedParents;
                $totalParentsArchived += $movedParents;

                if ($movedParents > 0) {
                    $this->line('  ✓ visitor_logs: '.number_format($movedParents).' archived');
                }

                $this->line('  Progress: '.number_format($totalParentsArchived).' / '.number_format($maxPerRun).' parents');
                $this->newLine();

                if ($totalParentsArchived >= $maxPerRun) {
                    $this->warn('Max per run limit reached. Run command again to continue.');
                    break;
                }
            }

            // Summary
            $total = array_sum($this->stats);
            $this->newLine();
            $this->info('Archive completed successfully!');
            $this->line('Summary:');
            foreach ($this->stats as $table => $count) {
                $this->line("  • {$table}: ".number_format($count));
            }
            $this->line('  • Total: '.number_format($total));

            // One line per run, regardless of whether anything moved — that absence
            // is itself the signal that the pipeline stalled.
            Log::channel('vms')->info('archive:visitor-data-safely completed', [
                'stats' => $this->stats,
                'total' => $total,
            ]);

            if ($total > 0) {
                $summary = collect($this->stats)
                    ->map(fn ($v, $k) => "{$k}=".number_format($v))
                    ->implode(', ');
                $this->notifySlack("✅ Visitor archive: {$summary}");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Archive failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            // Logged so a failed run can be diagnosed and safely resumed (re-running
            // continues from where it stopped — eligible rows not yet in the archive).
            Log::channel('vms')->error('archive:visitor-data-safely failed', [
                'last_parent_id' => $lastParentId ?? null,
                'archived_so_far' => $this->stats,
                'error' => $e->getMessage(),
            ]);

            $this->notifySlack('🚨 Visitor archive failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Archive child records for eligible parents
     *
     * IMPORTANT: Only archives children where:
     * 1. Parent leave_time IS NOT NULL (complete visit)
     * 2. Parent created_at < cutoff (old enough)
     * 3. Child not already in archive
     *
     * Returns: number of records moved (deleted from live)
     */
    private function archiveChildren(
        string $childTable,
        string $archiveTable,
        array $parentIds,
        Carbon $completedCutoff,
        Carbon $maxAgeCutoff,
        int $batchSize,
        float $sleep
    ): int {
        if (empty($parentIds)) {
            return 0;
        }

        $totalMoved = 0;

        // Split parent IDs into smaller chunks to avoid SQL placeholder limits
        // MySQL has query size limits for large IN clauses.
        foreach (array_chunk($parentIds, self::PARENT_ID_CHUNK_SIZE) as $parentChunk) {
            $placeholders = implode(',', array_fill(0, count($parentChunk), '?'));

            // Get child IDs whose parent is eligible (see eligibilityClause). Children
            // already copied to the archive are still selected — they must be removed
            // from the live table, otherwise archiveParents() would skip their parent
            // forever (it requires the parent to have no live children).
            $sql = "SELECT c.id
                    FROM {$childTable} c
                    INNER JOIN visitor_logs vl ON vl.id = c.visitor_log_id
                    WHERE c.visitor_log_id IN ({$placeholders})
                      AND {$this->eligibilityClause('vl.')}
                    ORDER BY c.id";

            $params = array_merge($parentChunk, [$maxAgeCutoff, $completedCutoff]);
            $rows = DB::select($sql, $params);
            $childIds = array_column($rows, 'id');

            if (empty($childIds)) {
                continue;
            }

            // Process in batches with transaction safety
            foreach (array_chunk($childIds, $batchSize) as $chunk) {
                $moved = $this->archiveBatch($childTable, $archiveTable, $chunk);
                $totalMoved += $moved;

                $this->sleepBetweenBatches($sleep);
            }
        }

        return $totalMoved;
    }

    /**
     * Archive parent records (visitor_logs)
     *
     * Only archives parents where:
     * 1. leave_time IS NOT NULL
     * 2. NO children in live tables
     * 3. Not already archived
     *
     * Returns: number of records moved
     */
    private function archiveParents(
        string $mainTable,
        string $archiveTable,
        array $parentIds,
        Carbon $completedCutoff,
        Carbon $maxAgeCutoff,
        int $batchSize,
        float $sleep
    ): int {
        if (empty($parentIds)) {
            return 0;
        }

        $totalMoved = 0;

        foreach (array_chunk($parentIds, self::PARENT_ID_CHUNK_SIZE) as $parentChunk) {
            $placeholders = implode(',', array_fill(0, count($parentChunk), '?'));

            // Parent is eligible (see eligibilityClause) and has NO children left in
            // the live tables (children are archived first in the same window). These
            // child checks are the FK safety gate and must stay. Presence in the
            // archive is not checked here — see the note on the parent fetch above.
            $sql = "SELECT id FROM {$mainTable} vl
                    WHERE vl.id IN ({$placeholders})
                      AND {$this->eligibilityClause('vl.')}
                      AND NOT EXISTS (
                          SELECT 1 FROM visiting_arrangements va
                          WHERE va.visitor_log_id = vl.id
                      )
                      AND NOT EXISTS (
                          SELECT 1 FROM visitor_parkings vp
                          WHERE vp.visitor_log_id = vl.id
                      )
                    ORDER BY id";

            $rows = DB::select($sql, array_merge($parentChunk, [$maxAgeCutoff, $completedCutoff]));
            $moveIds = array_column($rows, 'id');

            if (empty($moveIds)) {
                continue;
            }

            // Process in batches
            foreach (array_chunk($moveIds, $batchSize) as $chunk) {
                $moved = $this->archiveBatch($mainTable, $archiveTable, $chunk);
                $totalMoved += $moved;

                $this->sleepBetweenBatches($sleep);
            }
        }

        return $totalMoved;
    }

    /**
     * Archive a batch of records atomically
     *
     * Process (all in one transaction):
     * 1. INSERT into archive (rows already there are skipped via NOT EXISTS)
     * 2. VERIFY every id is now present in the archive
     * 3. DELETE from live (only after the verification passes)
     *
     * The gate is "is the row in the archive?", never "did we just insert it?".
     * A row that was already archived but is still in the live table (e.g. a
     * previous run was interrupted, or two runs overlapped) must still be removed
     * from live — otherwise its parent visitor_logs row can never be archived,
     * because archiveParents() requires the parent to have no live children.
     *
     * If anything fails the transaction rolls back and the batch is retried on the
     * next run. Nothing is ever deleted from live unless it exists in the archive.
     *
     * Returns: number of records deleted from live table
     */
    private function archiveBatch(string $liveTable, string $archiveTable, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        DB::beginTransaction();
        try {
            $columns = $this->getCommonColumns($liveTable, $archiveTable);

            // Step 1: Copy to archive (skip rows that are already there)
            $inserted = $this->insertViaSqlCopy($liveTable, $archiveTable, $columns, $ids);

            // Step 2: Verify — every row must exist in the archive before we delete
            $expected = count($ids);
            $archived = DB::table($archiveTable)->whereIn('id', $ids)->count();

            if ($archived !== $expected) {
                // The bare counts cannot distinguish "the copy was skipped" from "the
                // copy ran but matched nothing", which is the difference between a
                // schema problem and rows vanishing from under us. Report both.
                $alreadyThere = $archived - $inserted;

                throw new RuntimeException(sprintf(
                    'Consistency check failed for %s: %d rows selected but only %d present in %s '
                    .'(insert affected %d rows; %d were already archived; %d columns matched between the tables). %s',
                    $liveTable,
                    $expected,
                    $archived,
                    $archiveTable,
                    $inserted,
                    max(0, $alreadyThere),
                    count($columns),
                    $this->diagnoseEmptyCopy($liveTable, $archiveTable, $columns, $ids, $inserted),
                ));
            }

            // Step 3: Delete from live — safe, all rows are confirmed archived
            $deleted = DB::table($liveTable)->whereIn('id', $ids)->delete();

            DB::commit();

            return $deleted;
        } catch (Throwable $e) {
            DB::rollBack();
            // Log and re-throw
            $this->error("  Batch failed for {$liveTable}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * SQL predicate for an eligible visitor_logs row, shared by every query so the
     * archive/dry-run/children/parents logic can never drift apart.
     *
     * A row is eligible when it is older than max-age-days (regardless of leave_time),
     * OR it is a completed visit (leave_time set) older than retain-days.
     *
     * Bindings, in order, are always: [maxAgeCutoff, completedCutoff].
     *
     * @param  string  $prefix  Column prefix including the dot, e.g. 'vl.' (or '' for none)
     */
    private function eligibilityClause(string $prefix = ''): string
    {
        return "({$prefix}created_at < ? OR ({$prefix}leave_time IS NOT NULL AND {$prefix}created_at < ?))";
    }

    private function dryRun(Carbon $completedCutoff, Carbon $maxAgeCutoff): int
    {
        $this->line('[DRY RUN] Scanning eligible records...');
        $this->newLine();

        // Counts mirror the real run: every eligible row still in a live table will be
        // moved out, whether or not a copy already exists in the archive.
        $bindings = [$maxAgeCutoff, $completedCutoff];

        // Count eligible visitor_logs
        $eligibleParents = DB::selectOne("
            SELECT COUNT(*) as cnt
            FROM visitor_logs vl
            WHERE {$this->eligibilityClause('vl.')}
        ", $bindings)->cnt ?? 0;

        // Count eligible visiting_arrangements
        $eligibleArrangements = DB::selectOne("
            SELECT COUNT(*) as cnt
            FROM visiting_arrangements va
            INNER JOIN visitor_logs vl ON vl.id = va.visitor_log_id
            WHERE {$this->eligibilityClause('vl.')}
        ", $bindings)->cnt ?? 0;

        // Count eligible visitor_parkings
        $eligibleParkings = DB::selectOne("
            SELECT COUNT(*) as cnt
            FROM visitor_parkings vp
            INNER JOIN visitor_logs vl ON vl.id = vp.visitor_log_id
            WHERE {$this->eligibilityClause('vl.')}
        ", $bindings)->cnt ?? 0;

        $this->info('Eligible records to archive:');
        $this->line('  • visitor_logs: '.number_format($eligibleParents));
        $this->line('  • visiting_arrangements: '.number_format($eligibleArrangements));
        $this->line('  • visitor_parkings: '.number_format($eligibleParkings));
        $this->line('  • Total: '.number_format($eligibleParents + $eligibleArrangements + $eligibleParkings));
        $this->newLine();
        $this->line('Run without --dry-run to archive these records.');

        return self::SUCCESS;
    }

    private function getCommonColumns(string $mainTable, string $archiveTable): array
    {
        $mainCols = collect(DB::select(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ORDINAL_POSITION',
            [$mainTable],
        ))->pluck('COLUMN_NAME')->all();
        $archiveCols = collect(DB::select(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ORDINAL_POSITION',
            [$archiveTable],
        ))->pluck('COLUMN_NAME')->all();
        $common = array_values(array_intersect($mainCols, $archiveCols));

        usort($common, function ($a, $b) {
            if ($a === 'id') {
                return -1;
            }

            if ($b === 'id') {
                return 1;
            }

            return 0;
        });

        return $common;
    }

    /**
     * Explains a copy that moved nothing, so the failure names its own cause instead
     * of leaving "0 rows present" to be guessed at.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, int>  $ids
     */
    private function diagnoseEmptyCopy(
        string $liveTable,
        string $archiveTable,
        array $columns,
        array $ids,
        int $inserted
    ): string {
        if ($inserted > 0) {
            return 'The copy did insert rows, so they were removed or rolled back before the check — look for a concurrent archive/cleanup run.';
        }

        if ($columns === []) {
            return sprintf(
                'No columns matched between %s and %s, so the copy was skipped without running any SQL. '
                .'Check that %s exists in the connected database and that the DB user can see it in information_schema.',
                $liveTable,
                $archiveTable,
                $archiveTable,
            );
        }

        // The copy ran but matched no source rows. Either the ids are gone from the
        // live table, or they were all filtered out by the "already archived" guard.
        $stillLive = DB::table($liveTable)->whereIn('id', $ids)->count();

        if ($stillLive === 0) {
            return sprintf(
                'None of the %d ids are still in %s — another process deleted them between selection and copy. '
                .'Check for a concurrent run of this command or of the data-deletion commands.',
                count($ids),
                $liveTable,
            );
        }

        return sprintf(
            '%d of the %d ids are still in %s but the copy matched none of them. '
            .'This points at the INSERT..SELECT itself (column mismatch or a partition/constraint on %s).',
            $stillLive,
            count($ids),
            $liveTable,
            $archiveTable,
        );
    }

    private function insertViaSqlCopy(string $mainTable, string $archiveTable, array $columns, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        // Never skip the copy silently: a batch that copies nothing and then deletes
        // from the live table would lose data. Fail loudly instead.
        if (empty($columns)) {
            throw new RuntimeException(
                "Refusing to archive {$mainTable}: no columns matched between {$mainTable} and {$archiveTable}. "
                ."Verify {$archiveTable} exists in the connected database and is visible to the DB user."
            );
        }

        $columnList = implode(', ', array_map(
            static fn ($col): string => '`'.str_replace('`', '``', $col).'`',
            $columns
        ));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = sprintf(
            'INSERT INTO `%s` (%s)
             SELECT %s FROM `%s` mt
             WHERE mt.`id` IN (%s)
               AND NOT EXISTS (SELECT 1 FROM `%s` ar WHERE ar.`id` = mt.`id`)',
            $archiveTable,
            $columnList,
            $columnList,
            $mainTable,
            $placeholders,
            $archiveTable
        );

        try {
            return DB::affectingStatement($sql, $ids);
        } catch (Throwable $e) {
            $this->error('SQL copy failed for '.$archiveTable.': '.$e->getMessage());
            throw $e;
        }
    }

    private function archiveTableName(string $tableName, string $archiveSuffix): string
    {
        return $tableName.$archiveSuffix;
    }

    private function sleepBetweenBatches(float $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        usleep((int) round($seconds * self::MICROSECONDS_PER_SECOND));
    }

    /**
     * Pulse's SlowQueries recorder buffers every query slower than its threshold in
     * memory and only flushes them at shutdown. This command issues statements that
     * bind 10k ids at a time, so each one is both slow and many kilobytes of SQL
     * text (max_query_length is null by default). Over a large run the buffer
     * exhausts memory_limit and PHP dies during shutdown — after the work committed
     * and the success summary printed. Bulk maintenance does not belong in Pulse.
     */
    private function stopPulseRecording(): void
    {
        try {
            if (class_exists(Pulse::class)) {
                Pulse::stopRecording();
            }
        } catch (Throwable) {
            // Pulse is optional; never let telemetry break the archive.
        }
    }

    /**
     * Laravel's HandleExceptions bootstrap forces display_errors=Off, and log_errors
     * is off too, so a fatal error (typically OOM) terminates this command with exit
     * 255 and no output at all — the catch block below never runs and no Slack alert
     * is sent. Surface it instead, so a failed nightly run is never silent.
     */
    private function guardAgainstSilentFatal(): void
    {
        register_shutdown_function(function (): void {
            $error = error_get_last();

            if ($error === null || ! in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }

            // The fatal is usually memory exhaustion; give the handler room to report.
            ini_set('memory_limit', '-1');

            Log::channel('vms')->error('archive:visitor-data-safely fatal error', [
                'message' => $error['message'],
                'file' => $error['file'].':'.$error['line'],
                'archived_so_far' => $this->stats,
            ]);

            $this->notifySlack('🚨 Visitor archive fatal: '.$error['message']);
        });
    }

    private function notifySlack(string $message): void
    {
        try {
            if (! config('partitioning.safety.slack_notifications')) {
                return;
            }

            if (! class_exists(SlackNotifier::class)) {
                return;
            }

            SlackNotifier::send($message);
        } catch (Throwable) {
            // do nothing
        }
    }
}
