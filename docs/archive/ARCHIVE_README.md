# Visitor Data Archiving — Overview

Plain-English explanation of how old visitor data is kept out of the live tables.

## Why this exists

`visitor_logs` (and its children `visiting_arrangements`, `visitor_parkings`) grow
forever if nothing moves old rows out. Archiving keeps the live tables small and fast
for the app, while still preserving history in `*_archive` tables.

## The lifecycle (2 stages)

```text
LIVE table  --[Stage 1: archive]-->  ARCHIVE table  --[Stage 2: delete]-->  gone
```

| Stage | Command | What it does |
|---|---|---|
| 1. Archive | `archive:visitor-data-safely` | Copies eligible rows from `visitor_logs` (+children) into `*_archive`, then deletes them from the live table. Runs daily. |
| 2. Delete | `data-deletion:vms-archive` | Permanently deletes rows from `*_archive` once they're older than the retention window. Runs daily. Irreversible. |

A record is never deleted from `visitor_logs` until it has been safely copied into
`visitor_logs_archive` first (verified before delete — see "How it stays safe" below).

## When does a record move from live → archive? (Stage 1 rule)

A `visitor_logs` row is eligible once **either** of these is true:

| # | Condition | Example |
|---|---|---|
| 1 | It's a **completed visit** (`leave_time` is set) and it's **older than 30 days** | Visitor checked out 45 days ago → archived |
| 2 | It's **older than 1 year**, regardless of `leave_time` | Visitor never checked out, created 13 months ago → archived anyway |

Rule #2 exists because some visits never get a check-out scan (`leave_time` stays
`NULL` forever). Without it, those rows would sit in the live table permanently.

Children (`visiting_arrangements`, `visitor_parkings`) follow their parent
`visitor_logs` row — a child is archived when its parent is eligible.

Defaults: 30 days (`--retain-days`) and 1 year (`--max-age-days`). Both are
configurable per run — see [ARCHIVE_COMMAND_GUIDE.md](./ARCHIVE_COMMAND_GUIDE.md).

## When does a record get permanently deleted? (Stage 2 rule)

Once a row has been sitting in `visitor_logs_archive` for longer than the retention
window (default **12 months**, by `updated_at`), `data-deletion:vms-archive` deletes
it — along with its related media files. This is permanent.

## Stage 3: partition maintenance (housekeeping, deletes nothing)

The three archive tables are partitioned by month on `created_date`.
`vms:maintain-archive-partitions` keeps that scheme healthy. It runs on the 1st of each
month and does two things:

1. **Provisions the next 6 months of partitions.** Without this, everything past the last
   boundary piles into the `pmax` catch-all, partitioning stops pruning anything, and the
   table can never be maintained.
2. **Drops partitions that are expired *and already empty*** — reclaiming disk that a
   `DELETE` never returns to the OS.

> **It never deletes personal data.** A visitor row also owns COS objects (ID photos,
> e-signature images, parking vouchers) and `media` rows that live *outside* these
> tables. `data-deletion:vms-archive` purges those **before** the database row, on
> purpose. Dropping a partition would remove the rows while orphaning the files — a PDPA
> breach. So this command only ever drops a partition that the Stage 2 purge has
> **already emptied**, which by definition cannot take data with it.

## How it stays safe

- **No duplicates, no data loss**: each batch does *insert into archive* → *verify the
  insert count* → *delete from live*, all in one database transaction. If anything
  fails, nothing changes and the batch is simply retried on the next run.
- **Rerunning is always safe**: every command only touches rows that still match its
  criteria, so running it twice never double-archives or double-deletes.
- **Nothing is deleted from live until it's confirmed in archive.**
- **Only one archive run at a time.** The command takes a MySQL advisory lock. Two
  concurrent runs used to race each other's deletes and abort with a confusing
  "N rows selected but only 0 present" error — see the gotchas below.

## Commands you'll actually use

```bash
# Preview what would be archived (no changes)
php artisan archive:visitor-data-safely --dry-run

# Run the real archive (also runs automatically every day at 02:00)
php artisan archive:visitor-data-safely --force

# Preview what would be permanently deleted from the archive (no changes)
php artisan data-deletion:vms-archive --dry-run

# Run the real cleanup (also runs automatically every day at 04:30)
php artisan data-deletion:vms-archive --force

# Partition housekeeping (runs automatically on the 1st of each month)
php artisan vms:maintain-archive-partitions --dry-run --drop-empty
```

## Monitoring: one log file, one line per run

All three commands write to `storage/logs/vms-<date>.log` (channel `vms`, `config/logging.php`,
daily rotation, 14-day retention) — nowhere else. Deliberately minimal:

- **One line on success** — the final totals, not a line per batch/chunk/day.
- **One line if a run is skipped** because another one is already holding its lock.
- **Errors and warnings** — a failed batch, a COS cleanup failure, a real exception.

```text
[2026-07-14 02:00:03] local.INFO: archive:visitor-data-safely completed {"stats":{"visiting_arrangements":11734,"visitor_logs":10000},"total":21734}
[2026-07-14 04:30:12] local.INFO: data-deletion:vms-archive completed {"deleted":48213,"matched":48213}
[2026-08-01 01:00:05] local.INFO: vms:maintain-archive-partitions completed {"tables":3,"partitions_added":3,"partitions_dropped":1}
```

`--dry-run` never writes here — it changes nothing, so there's nothing to track.

## Gotchas that will bite you

**Never run two archives at once.** The scheduler's `withoutOverlapping()` expires after
its window, and a manual run bypasses it entirely. Concurrently, `INSERT ... SELECT`
reads the source with *locking* reads (latest committed) while plain `SELECT`s in the
same transaction use the older *snapshot* — so the copy finds nothing to insert while
the verification still sees the rows, and the batch fails with a contradictory error.
The advisory lock now prevents this; don't remove it.

**Don't raise `--batch-size`.** Bigger is *slower*, not faster. Past ~4,000 ids MySQL
abandons the primary key for the `id IN (...)` list and full-scans the table:

| ids in the batch | plan | time |
|---|---|---|
| 4,000 | `PRIMARY` | 221ms |
| 5,000 | full scan (4.4M rows) | 5,447ms |

The command caps the batch at 2,000 for this reason.

**The first partition run is expensive.** Routine runs are near-instant because
partitions are provisioned 6 months ahead, so `pmax` stays empty. The *first* run has to
rewrite whatever `pmax` already accumulated and **blocks writes to the table while it
does** (25+ minutes per table on staging). Run that one by hand in a maintenance window,
not from the scheduler.

Full options, one-time setup, and health-check queries are in
[ARCHIVE_COMMAND_GUIDE.md](./ARCHIVE_COMMAND_GUIDE.md).

## What moves together

A `visitor_logs` row never travels alone. Both stages always carry its children:

| Live table | Archive table |
|---|---|
| `visitor_logs` | `visitor_logs_archive` |
| `visiting_arrangements` | `visiting_arrangements_archive` |
| `visitor_parkings` | `visitor_parkings_archive` |

Stage 1 archives children **before** the parent (so foreign keys never break), and a
parent is only archived once no children remain in the live tables. Stage 2 deletes
children and the row's `media` records before the parent archive row.
