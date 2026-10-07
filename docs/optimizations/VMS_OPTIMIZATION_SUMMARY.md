# VMS (Visitor Management System) Module Reference

Docs hub: [README.md](./README.md)

## Purpose

The Visitor Management System (VMS) module provides real-time and historical visitor tracking for residential communities. Admins (Super Admin) and Property Managers use it to monitor who is entering and leaving mooban, review visit statistics, download reports, and manage visitor settings (cards, purposes, remarks, blacklist).

Analytically, the VMS dashboard surfaces visit volume trends, visitor type breakdowns (walk-in / drive-in / pre-book), vehicle types, visiting purposes, food delivery, and parcel courier patterns — all scoped by date range and residence.

**Access roles**: Super Admin and Admin (all residences), Property Management (scoped to their residence), Developer (scoped to their residence).

## Pages

| Page | Route | Purpose |
|------|-------|---------|
| `ListVisitors` | `/visitors` | Live visitor table + analytics widgets header |
| `ListHistoricalVisitors` | `/visitors/historical` | Archived visitor log table + report download |
| `DailyReports` | `/visitors/daily-reports` | Daily visitor report generation widget |
| `ViewVisitor` | `/visitors/{id}` | Visitor detail infolist |

## Header Widgets (ListVisitors — render order)

| Widget | Purpose |
|--------|---------|
| `VisitorReportDownloadWidget` | Export visitor data by date range (queue-dispatched) |
| `VisitorsSummaryChart` | Line chart: visit volume trend over selected period |
| `VisitorStatsOverview` | Stat cards: total in/out/remaining/overnight, walk-in/drive-in/pre-book |
| `VisitorTypeChart` | Donut chart: visitor type distribution (walk-in, drive-in, pre-book) |
| `VisitorTypeStatsOverview` | Stat cards matching visitor type chart |
| `VisitorPurposeChart` | Donut chart: visiting purpose breakdown |
| `VisitorPurposeStatsOverview` | Stat cards matching visiting purpose chart |
| `VisitorVehicleTypeChart` | Donut chart: vehicle type distribution |
| `VisitorVehicleTypeStatsOverview` | Stat cards matching vehicle chart |
| `VisitorParcelCourierChart` | Donut chart: parcel courier breakdown *(Super Admin / Admin only)* |
| `VisitorParcelCourierStatsOverview` | Stat cards matching parcel courier chart *(Super Admin / Admin only)* |
| `VisitorFoodDeliveryChart` | Donut chart: food delivery platform breakdown *(Super Admin / Admin only)* |
| `VisitorFoodDeliveryStatsOverview` | Stat cards matching food delivery chart *(Super Admin / Admin only)* |

All widgets use `InteractsWithPageTable` + `ResolvesVmsResidence` so that applying table filters (date range, residence, province/district) refreshes widget data.

## Data Sources

| Table | Purpose |
|-------|---------|
| `visitor_logs` | Live visitor log — primary source for the live table |
| `visitor_logs_archive` | Historical visitor records (older data moved from `visitor_logs`) |
| `vms_analytics_daily` | Denormalized daily summary per residence; read model for all widget analytics |
| `widget_aggregates` | Pre-computed global (unfiltered SA) payloads; module key: `visitors` |

## Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `VisitorResource` | `app/Filament/Resources/Visitors/` | Resource definition, navigation, role guards |
| `VisitorsTable` | `app/Filament/Resources/Visitors/Tables/` | Live visitor table column/filter/action configuration |
| `HistoricalVisitorsTable` | `app/Filament/Resources/Visitors/Tables/` | Historical visitor table configuration |
| `VmsAnalyticsQueryService` | `app/Services/` | Widget data layer: dual-path reads (aggregate cache → live `vms_analytics_daily` query) |
| `VmsStatsViewSyncService` | `app/Services/` | Builds `vms_analytics_daily` rows via chunked upsert + refreshes `widget_aggregates` |
| `AggregateVmsDailySummary` | `app/Jobs/` | Queue job that aggregates one day of visitor data into `vms_analytics_daily` |
| `ThailandLocationService` | `app/Services/` | Resolves province/district/subdistrict filter selections into `residence_id` arrays for large-table `whereIn` |
| `ResolvesVmsResidence` | `app/Filament/Resources/Visitors/Widgets/Concerns/` | Trait: `pmResidenceId()` and `isPmView()` for per-widget PM scoping |

## Data Flow

```
visitor_logs (live write)
  └─ daily scheduler (04:00)
        └─ AggregateVmsDailySummary::dispatch(yesterday)  [StatsSync queue]
              └─ VmsStatsViewSyncService::syncDay()
                    ├─ upserts: vms_analytics_daily (unique: summary_date + residence_id)
                    └─ refreshes: widget_aggregates [module=visitors]

ListVisitors table filter change
  └─ InteractsWithPageTable → widgets refresh
        └─ VmsAnalyticsQueryService::globalStats($from, $until, $residenceId)
              ├─ SA, no date filter → widget_aggregates cache (instant, 0 DB queries)
              └─ SA with filter OR PM → Cache::remember(60s) → vms_analytics_daily query

Province/district filter applied
  └─ ThailandLocationService::getResidenceIdsByLocation($provinces, $districts, $subdistricts)
        └─ whereIn('residence_id', $ids) applied to visitor_logs query
           (avoids slow nested whereHas on large tables)
```

## Live Table vs Historical

- **Live table** (`ListVisitors`) queries `visitor_logs` only. Widgets use `vms_analytics_daily` to produce analytics even for older date ranges as long as daily data has been aggregated.
- **Historical table** (`ListHistoricalVisitors`) queries `visitor_logs_archive`. Shows an alert when the selected date range falls entirely within the archive window (uses `VmsAnalyticsQueryService::isArchiveRange()`).
- Alert message clarifies: the table shows archive records, but widgets may still render analytics for the selected period if `vms_analytics_daily` rows exist.

## Scheduling & Queue

```php
// app/Console/Kernel.php
$schedule->call(fn() =>
    \App\Jobs\AggregateVmsDailySummary::dispatch(now()->subDay()->toDateString(), false)
        ->onQueue('StatsSync')
)->dailyAt('04:00')->name('aggregate-vms-daily-summary')->withoutOverlapping(60);
```

- Queue: `StatsSync` (shared with other analytics jobs; separate from `VisitorQueue` which handles CRUD/API traffic)
- Worker: `php artisan queue:work --queue=StatsSync`

```bash
# Manual backfill (historical rebuild)
php artisan vms:backfill-summary --from=2026-01-01 --to=2026-03-03 --queue --force

# Options:
# --queue  dispatch day-by-day jobs to StatsSync
# --force  delete existing date rows then rebuild (idempotent)
```

## UI Behavior Notes

- **Table filters (live)**: date range (`created_from` / `created_until`), visitor type, vehicle type, province, district, subdistrict (Super Admin / Admin / PM Operation Center), residence (Super Admin / Admin), visiting purpose, arrival type, food delivery platform, parcel courier.
- **Table filters (historical)**: same province/district/subdistrict pattern via `ThailandLocationService`.
- **Province/district filter performance**: resolved to `residence_id` array first, then `whereIn` on `visitor_logs` — avoids deep nested `whereHas` which was 8–30× slower on large tables.
- **Pagination**: count cached for 300 s per query fingerprint to avoid expensive `COUNT(*)` on repeated page loads.
- **Export**: dispatched as `SendVisitorExportRequest` job; PM users can export their own residence data; Super Admin and Admin exports are disabled via the action's `hidden()` condition (both roles see all residences via a different workflow).
- **VMS settings**: managed via relation managers on the `VmsResource` edit page — visitor cards, purposes, remarks, blacklisted visitors.

## Reusable Components

### `VmsAnalyticsQueryService`
- `globalStats(?string $from, ?string $until, ?int $residenceId): array` — total visitors, vehicle types, visitor types.
- `purposeBreakdown(...)`, `parcelCourierBreakdown(...)`, `foodDeliveryBreakdown(...)` — per-category analytics.
- `summaryTrend(...)` — daily visit trend for line chart.
- `isArchiveRange(?string $from, ?string $until): bool` — detects when selected range is in archive window.
- **Pattern**: SA/no-filter → `widget_aggregates` (instant); SA-filtered or PM → `Cache::remember(60s)` on `vms_analytics_daily`.

### `ThailandLocationService`
- `getProvinces()`, `getDistrictsByProvinces(array $ids)`, `getSubdistrictsByDistricts(array $ids)` — dependent filter option loading.
- `getResidenceIdsByLocation(array $provinces, array $districts, array $subdistricts): array` — resolves location selection to residence IDs with short cache.
- **Recommended pattern for any heavy-table location filter**: resolve residence IDs here, then `whereIn('residence_id', $ids)` on the fact table.

### `ResolvesVmsResidence` trait
- `pmResidenceId(): ?int` — returns the residence ID for a PM user, null for SA.
- `isPmView(): bool` — true when the current user is a Property Manager.

### `WidgetAggregate::getCached($key, $ttl, $module)` / `putCache(...)`
- Module-scoped aggregate cache. Module key for VMS: `visitors`.

## Implementation Notes for Future Enhancements

- **Adding a new chart widget**: implement with `InteractsWithPageTable` + `ResolvesVmsResidence`, call `VmsAnalyticsQueryService`, add to `getHeaderWidgets()` in `ListVisitors`.
- **Adding a new daily aggregate metric**: add the aggregation in `VmsStatsViewSyncService::syncDay()`, add the column to `vms_analytics_daily`, and update the migration.
- **Cross-module location filters on large tables**: always use `ThailandLocationService::getResidenceIdsByLocation()` + `whereIn` — do not use nested `whereHas` on tables with millions of rows.
- **Queue isolation**: keep analytics/reporting jobs on `StatsSync`; keep visitor CRUD/API operations on `VisitorQueue`. Do not merge them.
