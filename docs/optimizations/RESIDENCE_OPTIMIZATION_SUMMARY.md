# Residence Module Reference

Docs hub: [README.md](./README.md)

## Purpose

The Residence module is the **core master-data management page** for all registered residential communities (mooban). Super Admins use it to create, edit, and audit mooban records including operational details, module subscriptions, and amenities. It also exposes a header dashboard showing aggregate stats — mooban type breakdown, activation status distribution, house-type distribution, age distribution, and creation trends — that update live as the table is filtered.

**Access**: Super Admin and Admin roles only.

## Pages

| Page | Route | Purpose |
|------|-------|---------|
| `ListResidences` | `/residences` | Paginated table of all mooban + header analytics widgets |
| `CreateResidence` | `/residences/create` | Wizard form to onboard a new mooban |
| `EditResidence` | `/residences/{id}/edit` | Full edit form for an existing mooban |

## Header Widgets (ListResidences — render order)

| Widget | Purpose |
|--------|---------|
| `PublicTypeChart` | Donut chart: house type (sub-type) distribution for public mooban |
| `PublicTypeStatsOverview` | Stat cards matching `PublicTypeChart` values |
| `MoobanActivationStatusOverview` | Donut chart: count per activation status |
| `MoobanActivationStatusStatsOverview` | Stat cards matching the activation status chart |
| `MoobanTypesOverview` | Bar chart: public vs. private vs. other mooban-type split |
| `MoobanAgeChart` | Bar chart: mooban age buckets (years since completion) |
| `MoobanCreationChart` | Line/bar chart: mooban creation trend (daily / last 7 days / monthly) |

All header widgets use `InteractsWithPageTable` and `ResidenceWidgetFilters` so that applying table filters (e.g., province, activation status) refreshes widget data automatically.

## Data Sources

| Table / Service | Purpose |
|----------------|---------|
| `residences` | Source of truth for all mooban records |
| `residence_stats_view` | Denormalized read model for widget aggregates (counts, status, sub-type, age, creation trend) |
| `widget_aggregates` | Pre-computed global (unfiltered) payload cache, keyed `residence_global_stats` |
| `ResidenceWidgetDataService` | Shared data layer for all header widgets; reads from `residence_stats_view` with short-lived per-filter cache |

## Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `ResidenceResource` | `app/Filament/Resources/Residences/` | Filament resource definition, navigation, role guards |
| `ResidencesTable` | `app/Filament/Resources/Residences/Tables/` | Full table column/filter/action configuration |
| `ResidenceWidgetDataService` | `app/Services/` | Aggregates widget data from `residence_stats_view`; handles cache fallback (`widget_aggregates` → short-lived runtime cache) |
| `ResidenceStatsViewSyncService` | `app/Services/` | Builds/refreshes `residence_stats_view` via chunked upsert |
| `SyncResidenceStatsViewJob` | `app/Jobs/` | Queue job triggered by `ResidenceObserver` on create/update/delete |
| `ThailandLocationService` | `app/Services/` | Shared cached province/district/subdistrict/main-road option source reused by Residence, VMS, and UnitUser filters |
| `BpoSoftwareSupplierFilterHelper` | `app/Support/` | Shared JSON filter for `bpo_software_suppliers` column |

## Data Flow

```
residences (write)  ──► ResidenceObserver
                              └─ dispatches: SyncResidenceStatsViewJob (StatsSync queue)
                                    └─ ResidenceStatsViewSyncService::sync()
                                          └─ upserts: residence_stats_view
                                                └─ precomputes: widget_aggregates[residence_global_stats]

Scheduler (every 5 min)
  └─ residence:sync-stats-view
        └─ ResidenceStatsViewSyncService::sync()

ListResidences table filter change
  └─ InteractsWithPageTable → widgets refresh
        └─ ResidenceWidgetDataService::getAllData($tableFilters)
              ├─ unfiltered → widget_aggregates cache (instant)
              └─ filtered   → Cache::remember(60s) → residence_stats_view query
```

## Scheduling & Queue

```php
// app/Console/Kernel.php
$schedule->command('residence:sync-stats-view')
      ->dailyAt('05:00')
      ->withoutOverlapping(60);
```

Note: `residence:sync-stats-view` is a nightly reconciliation job. Observer-driven `syncOne(residence_id)` jobs remain the primary freshness path for normal create/update flows.

- Queue: `StatsSync`
- Worker: `php artisan queue:work --queue=StatsSync`

```bash
php artisan residence:sync-stats-view
php artisan residence:sync-stats-view --chunk=200
```

## UI Behavior Notes

- **Table filters**: province, district, activation status, mooban type, house type, BPO suppliers, property management type, date ranges, and more.
- **Header widgets** react to table filter state via `InteractsWithPageTable` — filtering the table updates all charts and stat cards above it.
- **Location filter options**: province, district, subdistrict, and main-road option lists are now loaded from `ThailandLocationService` with preload enabled, so Residence and other optimized modules share one cached source for those selectors.
- **Create action**: Multi-step wizard (Mooban Details → Operations → Modules & Features Subscription → Amenities).
- **Import action**: Excel upload for bulk residence import.
- **Export bulk action**: available in the table.
- **Polling**: disabled (`pollingInterval = null`) — data is kept fresh via the read-model sync cadence.
- **Sync cadence**: daytime freshness comes from observer-dispatched `StatsSync` jobs; the nightly 5am full sync is a backstop for reconciliation and stale-row cleanup.

## Reusable Components

### `ResidenceWidgetDataService::getAllData(array $filters): array`
- Returns a single merged array with keys: `activation_status`, `sub_types`, `mooban_types`, `lane_types`, `entry_counts`, `age_buckets`, `creation_trend`.
- Unfiltered: served from `widget_aggregates` cache (pre-computed, fast).
- Filtered: `Cache::remember(60s)` over `residence_stats_view` query.
- **Reuse pattern**: any new widget on the Residences list page should call this once and read its key, rather than running its own query.

### `WidgetAggregate::getCached($key, $ttl, $module)` / `putCache(...)`
- Module-scoped key/value aggregate cache shared across all dashboards.
- Use `module` param to avoid key collisions (e.g. `residence`, `visitors`, etc.).

## Implementation Notes for Future Enhancements

- **Adding a new widget** to the Residences list header: implement the widget using `InteractsWithPageTable` + `ResidenceWidgetFilters`, call `ResidenceWidgetDataService::getAllData($this->tableFilters ?? [])`, and add to `getHeaderWidgets()` in `ListResidences`.
- **Adding a new aggregate stat**: add the aggregation in `ResidenceWidgetDataService::computeFromReadModel()` and ensure `ResidenceStatsViewSyncService` precomputes it into `widget_aggregates`.
- **Residence stats view schema change**: update the migration, then update `ResidenceStatsViewSyncService` for the new column, and update any widgets that read the column.
- **Performance**: global (unfiltered) widget reads are instant via `widget_aggregates`. Filtered reads hit `residence_stats_view` — add indexes there, not on `residences`, for filter columns.
