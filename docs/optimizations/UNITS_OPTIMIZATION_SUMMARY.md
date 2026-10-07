# Units Optimization Summary

Docs hub: [README.md](./README.md)

## Purpose

The Units module is the global unit master-data page. Super Admins and Admins use it to manage units and read the header dashboard instantly, while PM and PMOC users read residence-scoped unit widgets. The current design keeps unfiltered widgets instant through `widget_aggregates` and makes filtered widgets react from a unit-level `units_stats_view` so table filters and widget counts stay aligned.

**Access**: Super Admin & Admin for the global units dashboard, Property Management & PMOC for residence-scoped widgets.

## Pages

| Page | Route | Purpose |
|------|-------|---------|
| `ListUnits` | `/admin/units` | Units table plus header dashboard widgets |
| `CreateUnit` | `/admin/units/create` | Create a unit |
| `EditUnit` | `/admin/units/{id}/edit` | Edit a unit |

## Widgets

| Widget | Role Scope | Purpose |
|--------|------------|---------|
| `SubTypeStatsOverview` | Super Admin / Admin | Unit counts per sub type |
| `HouseTypeStatsOverview` | Super Admin / Admin | Unit counts by house type and sub type |
| `UnitCreationChart` | Super Admin / Admin | Unit creation trend from `unit_created_at` buckets |
| `LivingStatusChart` | PM / PMOC | Living-status split for accessible residences |
| `HouseTypeByResidenceChart` | PM | House-type split for PM residence scope |
| `HouseTypeByResidenceStatsOverview` | PM | House-type stat cards for PM residence scope |
| `OwnerTenantByResidenceChart` | PM / PMOC | Owner vs tenant split |
| `OwnerTenantByResidenceStatsOverview` | PM / PMOC | Owner vs tenant stat cards |
| `SignUpRateChart` | PM / PMOC | Signed-up vs not-signed-up split |
| `SignUpRateStatsOverview` | PM / PMOC | Signed-up stat cards |
| `UnitStatusStatsOverview` | PM / PMOC | Status counts |

## Data Sources

| Table / Service | Purpose |
|----------------|---------|
| `units` | Source of truth for unit records and timestamps |
| `unit_user` | Source of truth for sign-up, owner, and tenant registration flags |
| `units_stats_view` | Unit-level read model, one row per unit, used by filtered widgets and fast unit prefilters |
| `widget_aggregates` | Pre-computed global payload cache keyed by `unit_global_stats` and module `unit` |
| `UnitWidgetDataService` | Shared aggregation layer for Units widgets and DDI unit widgets |

## Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `UnitWidgetDataService` | `app/Services/` | Reads from `units_stats_view`, handles cache versioning, and aggregates widget payloads |
| `UnitStatsViewSyncService` | `app/Services/` | Rebuilds one row per unit and refreshes global widget aggregates |
| `UnitStatsView` | `app/Models/` | Model for `units_stats_view` |
| `SyncUnitStatsViewJob` | `app/Jobs/` | Queue job that refreshes one residence after unit or unit-user writes |
| `UnitObserver` | `app/Observers/` | Dispatches stats sync after unit writes |
| `UnitUserObserver` | `app/Observers/` | Dispatches stats sync after `unit_user` writes |
| `UnitsTable` | `app/Filament/Resources/Units/Tables/` | Table filters plus exact unit-level prefilters for sign-up and ownership |

## Data Flow

```text
units / unit_user write
      -> observers dispatch SyncUnitStatsViewJob(residence_id) after commit on StatsSync
            -> UnitStatsViewSyncService::syncOne(residence_id)
                  -> rebuild units_stats_view rows for that residence
                  -> bump filtered widget cache version

manual full refresh
      -> unit:sync-stats-view
            -> UnitStatsViewSyncService::syncAll(chunk)
                  -> chunk active residences in small batches
                  -> chunk active units within each residence batch
                  -> aggregate unit_user registration flags in SQL per unit batch
                  -> upsert unit rows directly into units_stats_view
                  -> remove stale rows whose source units are deleted
            -> UnitStatsViewSyncService::syncWidgetAggregates()
                  -> refresh widget_aggregates[unit_global_stats]
                  -> bump filtered widget cache version

Note: `UnitStatsViewSyncService::syncAll()` performs batched upserts into `units_stats_view` (it upserts unit rows per chunk). The per-residence helper `syncOne($residenceId)` deletes existing rows for that residence first and then rebuilds just that residence's rows.

scheduled aggregate refresh
      -> unit:sync-widget-aggregates
            -> UnitStatsViewSyncService::syncWidgetAggregates()
                  -> refresh widget_aggregates[unit_global_stats]
                  -> bump filtered widget cache version

ListUnits without filters
      -> UnitWidgetDataService::getAllData([])
            -> widget_aggregates instant payload

ListUnits with filters
      -> UnitWidgetDataService::getAllData($tableFilters)
            -> Cache::remember(versioned key, 60s)
            -> aggregate directly from units_stats_view

PM / PMOC widgets
      -> UnitWidgetDataService::getByResidenceIds([...])

DDI widgets
      -> UnitWidgetDataService::getBySubdistrictIds([...])
```

## Scheduling & Queue

```php
$schedule->command('unit:sync-stats-view')
      ->dailyAt('05:10')
      ->withoutOverlapping(60);

$schedule->command('unit:sync-widget-aggregates')
      ->everyFiveMinutes()
      ->withoutOverlapping(10);
```

Note: `unit:sync-stats-view` is a nightly reconciliation job. Observer-driven `syncOne(residence_id)` jobs remain the primary freshness path during the day.

- Queue: `StatsSync`
- Worker: `php artisan queue:work --queue=StatsSync`
- Manual refresh: `php artisan unit:sync-stats-view --chunk=1`
- Aggregate-only refresh: `php artisan unit:sync-widget-aggregates`
- Observer sync jobs are dispatched `afterCommit()` so the read model only sees committed unit and unit_user writes.

## `units_stats_view` Schema

| Column | Type | Purpose |
|--------|------|---------|
| `unit_id` | `bigint` PK | One row per unit |
| `residence_id` | `bigint` | Residence scope for PM/PMOC widgets and location filtering |
| `mooban_type`, `residence_activation_status_id` | `smallint` | Residence-level filter pushdown |
| `subdistrict_id`, `district_id`, `province_id` | `bigint` | Location filter pushdown without nested residence joins |
| `unit_number` | `string` | Unit-number widget and table filtering |
| `sub_type`, `house_type`, `status` | integers | Unit-level widget and table filtering |
| `is_signed_up` | `bool` | At least one active `unit_user` row exists |
| `is_registered_owner` | `bool` | At least one active owner registration exists |
| `is_registered_tenant` | `bool` | At least one active tenant registration exists |
| `unit_created_at`, `unit_updated_at` | `timestamp` | Source timestamps for date filters and chart buckets |
| `synced_at` | `timestamp` | Read-model refresh time |

`UnitStatsViewSyncService` only syncs source rows where `deleted_at IS NULL` for `residences`, `units`, and `unit_user`. The Units table should use the same eligibility rule so table counts and widget counts reconcile.

Important indexes:
- `units_stats_view(province_id, mooban_type)`
- `units_stats_view(district_id, mooban_type)`
- `units_stats_view(residence_activation_status_id, mooban_type)`
- `units_stats_view(residence_id, unit_number)`
- `units_stats_view(residence_id, sub_type)`
- `units_stats_view(residence_id, house_type)`
- `units_stats_view(residence_id, status)`
- `unit_user(unit_id, deleted_at)` for observer-driven row refreshes and SQL registration-flag rollups

## UI Behavior Notes

- `ListUnits` registers one fixed, ordered header widget list. Visibility is delegated to each widget's `canView()` method, which keeps the page aligned with the standard Filament list-page pattern used elsewhere in the codebase while preserving role-specific dashboards.
- Super Admin / Admin widgets now react to both residence-level and unit-level filters: mooban, location, residence activation status, unit number, status, sign-up state, ownership state, and unit created/updated date ranges.
- Unfiltered widget loads stay instant through `widget_aggregates`; filtered widget payloads come from a versioned `Cache::remember(60)` key so syncs invalidate stale filtered results immediately.
- Observer-driven row sync keeps the read model current per residence. The nightly `unit:sync-stats-view` run is a reconciliation pass; the five-minute scheduler is only for cached global widget aggregates.
- Full read-model rebuilds batch units inside each residence chunk and aggregate registration flags in SQL to avoid materializing large `unit_user` sets in PHP.
- Table sign-up and ownership filters still use exact `EXISTS` checks against `unit_user`, but the hot-path prefilter now uses `unit_id` from `units_stats_view` instead of `residence_id`.
- `owner_count` and `tenant_count` in widget payloads come from unit living status (`StatusType::Occupied` and `StatusType::OccupiedTenant`), not from app registration roles.
- `UnitCreationChart` buckets from `unit_created_at` in `units_stats_view`; it no longer resolves residence IDs and re-queries `units` just to draw the chart.
- House-type widgets treat rows with `house_type = NULL`, `sub_type = NULL`, or invalid `house_type` to `sub_type` combinations as `Not Set` so the house-type cards always add up to the filtered total.

## Reusable Components

| Component | Purpose |
|-----------|---------|
| `UnitWidgetDataService::getAllData(array $filters)` | SA/Admin widget payload, instant when unfiltered |
| `UnitWidgetDataService::getByResidenceIds(array $ids)` | PM/PMOC residence-scoped widget payload |
| `UnitWidgetDataService::getBySubdistrictIds(?array $ids)` | DDI unit widget payload |
| `ResidenceFilterHelper::resolveSubdistrictIds(array $filters)` | Shared province/district/subdistrict resolution for read-model filters |
| `WidgetAggregate::getCached()` / `putCache()` | Module-scoped persistent cache for instant dashboards |

## Stress Test

```bash
k6 run tests/k6/stress-test-units.js
k6 run --env LOCAL_DEV=false --env BASE_URL=https://your-domain.com tests/k6/stress-test-units.js
```

The script now exercises:
- base dashboard load
- `sign_up=signed_up`
- `sign_up=not_signed_up`
- `ownership=owner`
- `mooban_type=[1,2]`
- `unit_number=A-`

The goal is to catch regressions where a filtered units page or its widget refresh path falls back to slow residence aggregation or broad scans.

## Implementation Notes for Future Enhancements

- Keep `units_stats_view` unit-level. Do not reintroduce residence-level JSON aggregates for widgets.
- Keep the manual rebuild simple. Chunk active residences, upsert rows directly into `units_stats_view`, and let observers handle incremental syncs after writes.
- Keep the 5-minute scheduler lightweight. Use it to refresh aggregate caches, not to rebuild the entire units read model.
- Keep the full `unit:sync-stats-view` rebuild off-peak. Nightly around 5am server time is the current production pattern.
- Keep observer-driven sync jobs commit-safe. Dispatch them after the write transaction commits so large imports and multi-model updates do not race the read model.
- If a new Units widget needs filtered SA/Admin data, add the field to `units_stats_view` and aggregate from `UnitWidgetDataService` instead of querying `units` live.
- If a filter is added to `UnitsTable`, mirror it in `UnitWidgetDataService::applyReadModelFilters()` so widget totals stay consistent with the table.
- If sync cadence or payload shape changes, update this doc and the k6 script in the same task.
