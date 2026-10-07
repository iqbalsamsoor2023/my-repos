# Unit Users Optimization Summary

Docs hub: [README.md](./README.md)

## Purpose

The Unit Users module is the resident master-data page. Super Admins and Admins use it as the global resident dashboard, while Property Management and PMOC users read residence-scoped resident widgets and listings. The current design keeps unfiltered widgets instant through `widget_aggregates`, pushes filtered widget and table logic into `unit_user_stats_view`, and keeps resident counts aligned with the table by filtering the same row-per-resident read model.

**Access**: Super Admin and Admin for global resident dashboard filters, Property Management and PMOC for residence-scoped listings and widgets.

## Pages

| Page | Route | Purpose |
|------|-------|---------|
| `ListUnitUsers` | `/admin/unit-users` | Resident table plus header dashboard widgets |
| `CreateUnitUser` | `/admin/unit-users/create` | Create a resident record |
| `EditUnitUser` | `/admin/unit-users/{id}/edit` | Edit a resident record |

## Widgets

| Widget | Role Scope | Purpose |
|--------|------------|---------|
| `ResidentsAllActionCancelledServiceChart` | Super Admin / Admin | Active vs cancelled-service split from residence activation state |
| `ResidentsGenderChart` | Super Admin / Admin / PM / PMOC | Gender split |
| `ResidentsGenderStatsOverview` | Super Admin / Admin / PM / PMOC | Gender stat cards |
| `ResidentsOwnerTenantChart` | Super Admin / Admin / PM / PMOC | Owner vs tenant split |
| `ResidentsOwnerTenantStatsOverview` | Super Admin / Admin / PM / PMOC | Owner vs tenant stat cards |
| `ResidentsByCountryChart` | Super Admin / Admin / PM / PMOC | Top nationality countries |
| `ResidentsNationalityStatsOverview` | Super Admin / Admin / PM / PMOC | Nationality summary cards |
| `ResidentsAgeChart` | Super Admin / Admin / PM / PMOC | Widget age-band split |
| `ResidentsAgeStatsOverview` | Super Admin / Admin / PM / PMOC | Age-group stat cards |
| `ResidentCreationChart` | Super Admin / Admin / PM / PMOC | Resident creation trend from `unit_user_created_at` buckets |

## Data Sources

| Table / Service | Purpose |
|----------------|---------|
| `unit_user` | Source of truth for resident membership, ownership flags, and soft deletes |
| `users` | Source of truth for resident demographics, email verification, and soft deletes |
| `units` | Source of truth for unit scope and residence linkage |
| `residences` | Source of truth for mooban, location, and activation status metadata |
| `unit_user_stats_view` | Resident-level read model, one row per active related resident record, used by widgets and table prefilters |
| `widget_aggregates` | Pre-computed global payload cache keyed by `unit_user_global_stats` and module `unit_user` |
| `UnitUserWidgetDataService` | Shared aggregation layer for resident widgets and trend data |

## Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `UnitUserWidgetDataService` | `app/Services/` | Reads from `unit_user_stats_view`, handles cache versioning, and returns widget payloads |
| `UnitUserStatsViewSyncService` | `app/Services/` | Rebuilds resident read-model rows and refreshes resident widget aggregates |
| `UnitUserStatsView` | `app/Models/` | Model for `unit_user_stats_view` |
| `SyncUnitUserStatsViewJob` | `app/Jobs/` | Queue job that refreshes one residence after resident, user, unit, or residence writes |
| `UnitUserObserver` | `app/Observers/` | Dispatches resident read-model sync after `unit_user` writes |
| `UserObserver` | `app/Observers/` | Dispatches resident read-model sync when demographic or verification fields change |
| `UnitObserver` | `app/Observers/` | Dispatches resident read-model sync when unit scope changes |
| `ResidenceObserver` | `app/Observers/` | Dispatches resident read-model sync when residence metadata changes |
| `ThailandLocationService` | `app/Services/` | Shared cached province/district/subdistrict/main-road option source and residence-id resolver for location filters |
| `UnitUsersTable` | `app/Filament/Resources/UnitUsers/Tables/` | Table filters plus `unit_user_stats_view` prefilters |
| `UnitUserResource` | `app/Filament/Resources/UnitUsers/` | Base listing query using active relation joins instead of nested `whereHas` |

## Data Flow

```text
unit_user / users / units / residences write
      -> observers dispatch SyncUnitUserStatsViewJob(residence_id) after commit on StatsSync
            -> UnitUserStatsViewSyncService::syncOne(residence_id)
                  -> rebuild unit_user_stats_view rows for that residence
                  -> bump filtered widget cache version

manual full refresh
      -> unit-user:sync-stats-view
            -> UnitUserStatsViewSyncService::syncAll(chunk)
                  -> chunk active residences in small batches
                  -> join active unit_user + users + units + residences
                  -> derive demographic and filter columns in SQL
                  -> upsert resident rows directly into unit_user_stats_view
                  -> remove stale rows whose source relations are deleted
            -> UnitUserStatsViewSyncService::syncWidgetAggregates()
                  -> refresh widget_aggregates[unit_user_global_stats]
                  -> bump filtered widget cache version

scheduled aggregate refresh
      -> unit-user:sync-widget-aggregates
            -> UnitUserStatsViewSyncService::syncWidgetAggregates()
                  -> refresh widget_aggregates[unit_user_global_stats]

ListUnitUsers without filters
      -> UnitUserWidgetDataService::getAllData([])
            -> widget_aggregates instant payload

ListUnitUsers with filters
      -> UnitUserWidgetDataService::getAllData($tableFilters)
            -> Cache::remember(versioned key, 60s)
            -> aggregate directly from unit_user_stats_view

PM / PMOC widgets
      -> UnitUserWidgetDataService::getByResidenceIds([...])

ListUnitUsers table filters
      -> UnitUsersTable::applyStatsViewPrefilter(...)
            -> select matching unit_user_id rows from unit_user_stats_view
            -> constrain the base UnitUser query by id
```

## Scheduling & Queue

```php
$schedule->command('unit-user:sync-stats-view')
      ->dailyAt('05:20')
      ->withoutOverlapping(60);

$schedule->command('unit-user:sync-widget-aggregates')
      ->everyFiveMinutes()
      ->withoutOverlapping(10);
```

Note: `unit-user:sync-stats-view` is a nightly reconciliation job. Observer-driven `syncOne(residence_id)` jobs keep resident read-model rows fresh during the day.

- Queue: `StatsSync`
- Worker: `php artisan queue:work --queue=StatsSync`
- Manual refresh: `php artisan unit-user:sync-stats-view --chunk=1`
- Aggregate-only refresh: `php artisan unit-user:sync-widget-aggregates`
- Observer sync jobs are dispatched `afterCommit()` so the read model only sees committed writes.

## `unit_user_stats_view` Schema

| Column | Type | Purpose |
|--------|------|---------|
| `unit_user_id` | `bigint` PK | One row per resident record |
| `unit_id`, `user_id`, `residence_id` | `bigint` | Row identity and residence scope |
| `mooban_type`, `sub_type`, `residence_activation_status_id` | integers | Residence-level filter pushdown |
| `subdistrict_id`, `district_id`, `province_id` | `bigint` | Location filter pushdown without nested joins |
| `unit_number`, `email` | `string` | Resident table and widget text filters |
| `country_id`, `country_name`, `gender`, `user_date_of_birth` | mixed | Demographic filtering and chart labels |
| `widget_age_group` | `string` | Widget age buckets |
| `table_age_group` | `string` | Table filter age buckets |
| `is_owner`, `is_main_owner`, `is_main_tenant` | `bool` | Ownership flags for charts and listing columns |
| `approval_status`, `is_email_verified` | mixed | Resident status and verification signals |
| `unit_user_created_at`, `unit_user_updated_at`, `unit_user_deleted_at` | `timestamp` | Trend buckets, date filters, and trashed-record compatibility |
| `synced_at` | `timestamp` | Read-model refresh time |

`UnitUserStatsViewSyncService` only loads rows where related `users`, `units`, and `residences` are not soft deleted. It keeps `unit_user_deleted_at` in the read model so table behavior can still reconcile with trashed resident records while widgets stay active-only through `whereNull('unit_user_deleted_at')`.

Important indexes:
- `unit_user_stats_view(province_id, mooban_type)`
- `unit_user_stats_view(district_id, mooban_type)`
- `unit_user_stats_view(residence_activation_status_id, mooban_type)`
- `unit_user_stats_view(residence_id, unit_user_created_at)`
- `unit_user_stats_view(residence_id, country_id)`
- `unit_user_stats_view(residence_id, gender)`
- `unit_user_stats_view(residence_id, is_owner)`

## Event Flow

```text
ListUnitUsers uses ExposesTableToWidgets
      -> table filter state is exposed to header widgets
      -> widgets call UnitUserWidgetDataService with $this->tableFilters
      -> table and widget filters stay aligned on the same read-model fields
```

## UI Behavior Notes

- Super Admin and Admin widgets react to mooban, location, residence activation status, resident unit/email, created/updated date, nationality, gender, and age-group filters.
- Resident gender verification should use active relation joins across `unit_user`, `users`, `units`, and `residences`. A `LEFT JOIN` plus only `deleted_at IS NULL` checks can overcount orphaned rows, while the widgets and `unit_user_stats_view` intentionally follow the stricter active-join rule.
- Unfiltered widget loads stay instant through `widget_aggregates`; filtered widget payloads come from a versioned `Cache::remember(60)` key so observer syncs invalidate stale filtered results quickly.
- The nightly `unit-user:sync-stats-view` run is a reconciliation pass for missed jobs or out-of-band data changes; it is not the primary freshness path.
- PM and PMOC widgets never scan all residents. They resolve accessible residence ids once, then aggregate from the resident read model within that scope.
- Resident widgets default to active rows and now respect the table `TrashedFilter` state when it is present in the exposed page-table filters.
- Province, district, subdistrict, and main-road selects now use the shared cached option loaders in `ThailandLocationService` with Filament preload enabled, matching the fast location-filter boot pattern used by VMS.
- Resident location filtering now resolves residence ids through `ThailandLocationService::getResidenceIdsByLocation(...)` before constraining `unit_user_stats_view`, so the table and widgets share one location-scope path.
- `ResidentsAllActionCancelledServiceChart` stays global-admin only because it reflects residence activation status, not just resident demographics.
- `ResidentCreationChart` buckets from `unit_user_created_at` in the read model and no longer re-queries `unit_user` with deep relationship filters.
- `UnitUserResource::getEloquentQuery()` uses active relation joins for `users`, `units`, and `residences`, which avoids repeated `whereHas` chains on large resident sets.
- `UnitUsersTable` filters prefilter matching ids through `unit_user_stats_view` and then constrain the base query with `whereIn(unit_user.id, subquery)`.

## Reusable Components

| Component | Purpose |
|-----------|---------|
| `UnitUserWidgetDataService::getAllData(array $filters)` | SA/Admin widget payload, instant when unfiltered |
| `UnitUserWidgetDataService::getByResidenceIds(array $ids, array $filters = [])` | PM/PMOC residence-scoped widget payload |
| `UnitUserWidgetDataService::getScopedCreationTrendForUser($user, array $filters, string $period)` | Scoped trend payload for the resident creation chart |
| `UnitUserWidgetDataService::applyReadModelFilters($query, array $filters)` | Shared read-model filter logic mirrored by the table |
| `ThailandLocationService::getResidenceIdsByLocation(array $provinceIds, array $districtIds, array $subdistrictIds)` | Shared location resolution from filter selections to residence ids |
| `ThailandLocationService::getProvinces()` / `getDistrictsByProvinces()` / `getSubdistrictsByDistricts()` / `getMainRoadsByDistricts()` | Shared cached preload option sources for location filters |
| `WidgetAggregate::getCached()` / `putCache()` | Module-scoped persistent cache for instant dashboards |

## Stress Test

```bash
k6 run tests/k6/stress-test-unit-users.js
k6 run --env LOCAL_DEV=false --env BASE_URL=https://your-domain.com tests/k6/stress-test-unit-users.js
```

The script exercises:
- base resident dashboard load
- `residence_activation_status_id=5`
- `mooban_type=[1,2]`
- `email=@gmail.com`
- `country_id=1`
- `gender=1`

The goal is to catch regressions where the resident dashboard falls back to live relationship aggregation instead of the resident read model.

## Implementation Notes for Future Enhancements

- Keep `unit_user_stats_view` row-level. Do not replace it with residence-level JSON blobs or per-request joins.
- Keep observer-driven sync commit-safe and residence-scoped. Large imports should enqueue targeted refreshes, not global rebuilds.
- If a new resident widget needs filtered data, add the column to `unit_user_stats_view` and aggregate from `UnitUserWidgetDataService` instead of querying `unit_user` live.
- If a filter is added to `UnitUsersTable`, mirror it in `UnitUserWidgetDataService::applyReadModelFilters()` so widget totals stay aligned with the table.
- Keep soft-delete eligibility consistent across `unit_user`, `users`, `units`, and `residences`. If that rule changes, update both the read-model sync and the resource base query in the same task.
- Keep the 5-minute scheduler lightweight. Use it for aggregate refreshes, not full read-model rebuilds.
