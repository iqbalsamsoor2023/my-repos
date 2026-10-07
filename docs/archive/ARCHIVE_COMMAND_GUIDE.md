# Archive Command Guide

Operational runbook for the visitor data archive + cleanup commands. For the
plain-English overview of *why* and *when* records move, read
[ARCHIVE_README.md](./ARCHIVE_README.md) first.

> Only commands that actually exist in this codebase are listed here. Run
> `php artisan list archive` / `php artisan list data-deletion`
> to see the live command set at any time.

## Commands

| Command | Purpose |
|---|---|
| `archive:setup-tables` | One-time: creates the `*_archive` tables and monthly partitions. |
| `archive:verify-setup` | Checks archive table/partition health. Non-zero exit on failure — safe for CI. |
| `archive:visitor-data-safely` | **Stage 1.** Moves eligible rows from live → archive. |
| `data-deletion:vms-archive` | **Stage 2.** Permanently deletes old rows from the archive (+ related media). |

## 1. One-time setup (new environment)

```bash
php artisan archive:setup-tables --dry-run
php artisan archive:setup-tables --force

php artisan archive:verify-setup --check-data
```

## 2. Stage 1 — Archive (live → archive)

```bash
# Preview
php artisan archive:visitor-data-safely --dry-run --retain-days=30 --max-age-days=365

# Run
php artisan archive:visitor-data-safely --force --retain-days=30 --max-age-days=365
```

Options:

| Option | Default | Meaning |
|---|---|---|
| `--retain-days` | 30 | Archive **completed** visits (`leave_time` set) older than this. |
| `--max-age-days` | 365 | Archive **any** row older than this, even if `leave_time` is still `NULL`. |
| `--max-per-run` | 500000 | Safety cap on how many parent rows one run will move. |
| `--batch-size` | 10000 | Rows per insert+delete transaction. |
| `--chunk-fetch` | 10000 | How many candidate IDs are fetched per window. |
| `--sleep` | 0.1 | Seconds to pause between batches (keeps load off the DB). |

**Scheduled:** daily at `02:00` (see `app/Console/Kernel.php`).

**Large backlog (e.g. after changing `--max-age-days`, or first run on an
environment that never archived):** one run may hit `--max-per-run` before the
backlog is empty. That's expected — just run the command again; it always resumes
from where it left off (it only ever touches rows still matching the criteria, so
re-running is safe). Keep running until it reports "No more eligible records to
archive."

## 3. Stage 2 — Cleanup (archive → deleted, permanent)

```bash
# Preview
php artisan data-deletion:vms-archive --dry-run

# Run
php artisan data-deletion:vms-archive --force
```

Options:

| Option | Default | Meaning |
|---|---|---|
| `--retention-months` | 12 | Delete archive rows older than this (by `updated_at`). Ignored if `--to` is set. |
| `--to` | — | Explicit cutoff date instead of `--retention-months`. |
| `--from` | — | Only delete rows last updated on/after this date (bounds the range). |
| `--residence` | — | Limit to one residence. |
| `--chunk` | 500 | Rows per deletion batch. Prefer 2000 over larger values — bigger batches mean longer locks and fatter binlog events. |
| `--skip-cos` | off | Delete DB rows only; leave cloud-storage (COS) files untouched. |
| `--max-runtime` | 0 | Stop gracefully after N seconds (0 = no limit). Stops at a chunk boundary *after* the commit, exits 0, and the next run resumes oldest-first. |

### Cloud storage (COS)

COS objects are removed **before** the database rows, on purpose: the object keys are
derived from the row IDs, so deleting the rows first would make any leftover files
unreachable forever. If the process dies in between, the row survives with missing
files and the next run simply deletes it again.

Only records that actually have `media` rows own COS objects, so records without media
cost zero COS calls. For each real directory the command issues one recursive `LIST`
and then batched `DeleteObjects` (1000 keys per request), which also sweeps up
conversions and responsive images.

**Never run without `--skip-cos` from a local machine** — the `cos` disk points at the
shared bucket, so you would delete production images.

**First production run has a backlog.** COS calls dominate the runtime, so the nightly
`--max-runtime=3600` cap will take a few weeks to drain the initial backlog before
settling into a ~10–20 min nightly run. That is expected: each run stops cleanly and
resumes, and nothing is ever left half-deleted.

**Scheduled:** daily at `04:30` (see `app/Console/Kernel.php`).

This also deletes the row's related media files (id/visitor/vehicle images,
e-sign) from cloud storage. **This step is irreversible** — always `--dry-run`
first when running manually.

## How to check things are healthy

```sql
-- Stage 1 backlog: rows still in live that should already be archived (either rule)
SELECT COUNT(*) FROM visitor_logs
WHERE created_at < NOW() - INTERVAL 365 DAY
   OR (leave_time IS NOT NULL AND created_at < NOW() - INTERVAL 30 DAY);

-- Duplicates between live and archive (should always be 0).
-- If this is ever non-zero the archive command self-heals it: the live copy is
-- re-verified against the archive and then removed.
SELECT COUNT(*) FROM visitor_logs vl
JOIN visitor_logs_archive a ON a.id = vl.id;

-- Archive size
SELECT COUNT(*) FROM visitor_logs_archive;

-- Stage 2 backlog: archive rows past the deletion cutoff.
-- NOTE: deletion keys on updated_at, not created_at.
SELECT COUNT(*) AS records_to_be_removed
FROM visitor_logs_archive
WHERE updated_at < NOW() - INTERVAL 12 MONTH;

-- Orphaned media (should be 0 after a Stage 2 run)
SELECT COUNT(*) FROM media m
WHERE m.model_type IN ('App\\Models\\VisitorLog', 'App\\Models\\VisitorLogArchive')
  AND NOT EXISTS (SELECT 1 FROM visitor_logs vl WHERE vl.id = m.model_id)
  AND NOT EXISTS (SELECT 1 FROM visitor_logs_archive a WHERE a.id = m.model_id);
```

## Monitoring

`archive:visitor-data-safely` sends a Slack notification when records are moved
(config: `partitioning.safety.slack_notifications`). Failures are also written to
the application log (`storage/logs/laravel.log`) with the last processed ID, so a
failed run can be diagnosed and safely resumed by just running the command again.

Both commands are scheduled `withoutOverlapping`, so a long run will never be
started twice. Re-running either command by hand is always safe.
