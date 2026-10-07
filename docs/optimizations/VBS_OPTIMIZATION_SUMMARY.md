# VBS (Visitor by Site) Module Reference

Docs hub: [README.md](./README.md)

## Purpose

The **Visitor by Site** (VBS) dashboard gives Super Admin and Admin users a per-residence aggregated view of visitor volume, entry method, vehicle mix, purpose breakdown, and MyVisitorPass product fit across all registered sites in the platform.

Admins use it to evaluate which sites are strong MyVisitorPass (MVP) candidates based on three objective criteria: house unit count, average daily visitor volume, and food-delivery rider percentage.

**Access**: Super Admin and Admin roles only. Visible only on the `visitor-by-site` page.

**Navigation**: Big Data Management → Visitor by Site

## Pages

| Page | Route | Purpose |
|------|-------|---------|
| `VisitorBySiteDashboard` | `/visitor-by-site` | Single dashboard page; registers both widgets; guards navigation via `shouldRegisterNavigation()` |

## Widgets (render order)

| # | Widget | Purpose |
|---|--------|---------|
| 1 | `VbsFilterWidget` | Top-bar filter form: month, activation status, province, district, residence site; emits `vbs-filters-updated` |
| 2 | `VisitorBySiteTableWidget` | Paginated aggregated table: one row per site, 30+ columns across visitor counts, vehicle mix, purpose breakdown, and MVP recommendation; listens to `vbs-filters-updated` |

## Data Sources

| Table / Service | Purpose |
|----------------|---------|
| `vms_analytics_daily` | Per-residence, per-day fully denormalized visitor aggregates; the only data source queried by this dashboard |
| `residence_stats_view` | Flat site metadata (name, province, district, sub_type, units_count, activation status); joined to `vms_analytics_daily` sub-query |
| (read-model column) `public_type` | Public/PMOC/Factory/Residence/Demo flag used for filtering; stored on `residence_stats_view.public_type` |
| `ThailandLocationService` | 6-hour cached province and district option lists; shared with VMS, UnitResource, ResidenceBpos patterns |

VBS **never** queries `visitor_logs` or `visitor_logs_archive` directly.

## Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `VisitorBySiteDashboard` | `app/Filament/Pages/` | Page shell: navigation, layout, widget registration |
| `VbsFilterWidget` | `app/Filament/Widgets/Default/Vbs/` | Filter form widget; dispatches `vbs-filters-updated` event |
| `VisitorBySiteTableWidget` | `app/Filament/Widgets/Default/Vbs/` | Main aggregated table widget |
| `ThailandLocationService` | `app/Services/` | Cached province/district option loading |

## Data Flow

```
visitor_logs / visitor_logs_archive
      │
      ▼  daily batch (AggregateVmsDailySummary — StatsSync queue, 04:00)
vms_analytics_daily        ← per-residence, per-day, fully denormalized
      │
      ▼  joinSub in VisitorBySiteTableWidget::table()
residence_stats_view       ← site metadata snapshot (synced by residence:sync-stats-view)
      │
      ▼
VisitorBySiteTableWidget   ← aggregates over user-selected date window, one row per site
```

### Query Strategy

```php
// Sub-query: aggregate vms_analytics_daily per site (index on residence_id, summary_date)
$vmsAgg = DB::table('vms_analytics_daily')
    ->selectRaw('residence_id, SUM(visitors_in) AS total_visitors_in, ...')
    ->whereBetween('summary_date', [$from, $until])
    ->when(...)   // residence_ids / purpose filters applied here
    ->groupBy('residence_id');

// Main query: LEFT JOIN so ALL residences appear (no-data sites show 0 counts)
ResidenceStatsView::query()
    ->leftJoinSub($vmsAgg, 'vms_agg', fn ($join) => $join->on(...))
    ->addSelect(DB::raw('COALESCE(vms_agg.total_visitors_in, 0) AS total_visitors_in, ...'))
    ->when(...)   // status / province / district filters applied here
```

A **LEFT JOIN** is used so that residences with no `vms_analytics_daily` rows for the selected date window still appear in the table (all visitor counts COALESCE to `0`). This is intentional — admins need to see all residences by activation status even if they have had no visitor activity in the selected period.

## Scheduling & Queue

- VMS daily summary is populated by the `AggregateVmsDailySummary` job at 04:00 on the `StatsSync` queue.
- `residence_stats_view` is synced by `residence:sync-stats-view` (triggered by `ResidenceObserver`).

## Event Flow

```
VbsFilterWidget (form afterStateUpdated)
  └─ dispatches: vbs-filters-updated {
         activation_status_ids,   // int[]
         province_id,             // int|null
         district_ids,            // int[]
         residence_ids,           // int[]
         mooban_type,             // string[] (mapped to residence_stats_view.public_type)
         purposes,                // string[]
         month                    // "Y-m" | null
     }
       └─ received by: VisitorBySiteTableWidget::applyFilters()
            └─ resets table, updates dateFrom/dateUntil from month
```

## UI Behavior Notes

- **Month filter**: rolling 24-month dropdown; defaults to current month in `mount()` (sets `$this->month` before `form->fill()`). Options computed with a single `$now = Carbon::now()` per invocation. Converts `"Y-m"` → `dateFrom` / `dateUntil` via `resolveDateWindow()` in `applyFilters()`.
- **Province dropdown**: `->searchable()` + `->preload()` — all provinces load instantly on open (ThailandLocationService cached).
- **Residence site search**: `getSearchResultsUsing()` queries `residence_stats_view` by name/name_th (bilingual); `->preload()` loads first 50 results immediately on open.
- **District dropdown**: disabled until a province is selected; `->preload()` + `->searchable()` ensure all province districts remain visible after selecting one.
- **All multiselects**: de-duplicate with `array_unique` in both `afterStateUpdated` (filter widget) and `applyFilters()` (table widget).
- **Pagination**: 25 / 50 / 100 per page; default 25; striped rows.
- **Default sort**: `total_visitors_in` descending.
- **Columns toggleable**: all columns are toggleable. MVP recommendation, activation status, province, district, site name, sub-type, units, visitor in/out, courier %, food-delivery %, active days, purpose breakdown (9 columns), vehicle types (6 columns), last activity date.

## MyVisitorPass Recommendation Logic

Three criteria evaluated per site from the aggregated period data:

| # | Criterion | Column | Threshold |
|---|-----------|--------|-----------|
| 1 | House units | `residence_stats_view.units_count` | > 200 |
| 2 | Avg daily visitors | `vms_agg.avg_daily_visitors` | > 150 |
| 3 | Food-delivery % | `vms_agg.food_delivery_percentage` | > 50 % |

| All 3 pass | Only C1 passes | Otherwise |
|---|---|---|
| `mvp` → MyVisitorPass (green) | `guard_kiosk` → Guard Kiosk (amber) | `review` → Review (gray) |

`mvp_recommendation` is a virtual column (`getStateUsing`); sorting uses a `DB::raw(CASE WHEN ...)` expression.

## Reusable Components

### `VisitorBySiteTableWidget::resolveDateWindow(?string $month): array`
Private helper that converts a `"Y-m"` string to `[dateFrom, dateUntil]`. Falls back to `[startOfMonth, today]` if `$month` is null or unparseable. Used by both `mount()` and `applyFilters()`.

### `VbsFilterWidget::dispatchFilters()`
Centralized dispatch; always normalizes arrays before emitting. All `afterStateUpdated` closures call this after updating their respective public property.

### `ThailandLocationService` (shared)
- `getProvinces(): array` — 6-hour cached `[id => "EN (TH)"]` map
- `getDistrictsByProvinces(array $provinceIds): array` — 6-hour cached per-province district map
- Pattern also used by VMS, UnitResource, ResidenceBpos

## Implementation Notes for Future Enhancements

- **Do not query `visitor_logs` directly** — always use `vms_analytics_daily`.
- **Do not replace `joinSub` with `INNER JOIN`** — the query intentionally uses a `leftJoinSub` so residences with zero visitor activity in the selected period are still visible in the table (counts COALESCE to 0).
- **Purpose columns** are derived from `vms_analytics_daily.purpose_breakdown` (JSON) via `JSON_TABLE` aggregation. The bucket names must match `VmsAnalyticsQueryService::purposeBreakdown` / `VisitorPurposeNormalizer`.
- **`courier_count` backfill** is required to show historical parcel/courier data. Migration: `2026_03_10_000002_add_courier_count_to_vms_analytics_daily.php`.
- **Adding a new aggregated column**: add the `SUM(...)` expression to the `$vmsAgg` sub-query in `VisitorBySiteTableWidget::table()`, then add the corresponding `TextColumn` in `tableColumns()`.
- **Site name bilingual display** adapts to locale via the `vbs.site_label` translation key (`':en / :th'` in English, `':th / :en'` in Thai).


