# <Module Name> Optimization Summary

Docs hub: [README.md](./README.md)

## Documentation Rule

- Keep this document current-state only.
- Do not include migration history, removal history, or comparison timelines.

## Current State (Verified)

- Describe current architecture in 4-6 bullets.
- Mention sync cadence, queue, and data source behavior.
- Avoid historical narrative unless needed for operations.

## Architecture

- Source tables:
- Read model table:
- Aggregate/cache table:
- Main query service/class:
- Main sync service/class:

## Scheduling & Queue

- Scheduler command/job:
- Frequency:
- Queue name:
- Worker command:

## Commands

- Active commands for this module.
- Include at least one usage example.

```bash
# Example
php artisan <module:command> --option=value
```

## Storage & Idempotency Safety

- Unique keys/indexes:
- Upsert behavior:
- Force rebuild behavior:
- Duplicate prevention strategy:

## UI Behavior Notes

- Table behavior:
- Widget behavior:
- Filter behavior:
- Alert/notification behavior:

## Reusable Components

- Class/trait/service:
  - Purpose:
  - Reusable method(s):

- Class/trait/service:
  - Purpose:
  - Reusable method(s):

## Module Improvement Checklist

1. Define read model schema.
2. Implement sync service with chunked upsert.
3. Add aggregate cache keys by module.
4. Wire widget queries to summary/cache path.
5. Add scheduler + backfill path.
6. Validate with load test.

## Operational Notes

- What to keep updated in this doc:
  - queue names
  - schedule frequency
  - command signatures
  - schema/index changes
