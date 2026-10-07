# District Dashboard (DDI) Module Reference

Docs hub: [README.md](./README.md)

## Purpose

District Dashboard (DDI) is an admin analytics module for PUBLIC residences. It combines a location + CS Zone filter layer with summary widgets and a detailed table so operations teams can monitor activation, resident adoption, and management distribution by geography.

- Primary audience: Admin users (Super Admin, Admin)
- Route: `/admin/district-dashboard`
- Page class: `App\\Filament\\Pages\\DistrictDashboard`

## Access Control

Authorization is policy/gate driven.

- Gate: `view-district-dashboard`
- Policy: `App\\Policies\\DistrictDashboardPolicy::view(User $user): bool`
- Gate registration: `App\\Providers\\AuthServiceProvider`

Current behavior:

- `DistrictDashboard::canAccess()` checks `Gate::allows('view-district-dashboard')`.
- `DistrictDashboard::shouldRegisterNavigation()` also checks the same gate.

## Page Composition

The dashboard renders these widgets in order:

1. `DdiFilterWidget`
2. `PropertyStatsOverviewWidget`
3. `PropertyStatusGridCardsWidget`
4. `PropertyManagementTypeChart`
5. `PropertyManagementTypeStatsOverview`
6. `HouseTypeByResidenceStatsOverviewWidget`
7. `MarketShareProgressWidget`
8. `ResidentMarketShareChartWidget`
9. `ResidentPotentialStatsOverviewWidget`
10. `DistrictDataSummaryWidget`

## Filters

DDI currently applies filters directly in each widget and query service using normalized multi-select arrays.

Input keys emitted by `DdiFilterWidget`:

- `province_ids`
- `district_ids`
- `customer_success_zone_ids`

## Event Flow

Event: `ddi-filters-updated`

- Emitted by `DdiFilterWidget`
- Consumed by all DDI widgets for location/CS Zone scope updates

Event: `ddi-resident-market-share-filters-updated`

- Emitted by `DistrictDataSummaryWidget::applyTableFilters()`
- Consumed by:
  - `ResidentMarketShareChartWidget`
  - `ResidentPotentialStatsOverviewWidget`

## Query Architecture

### Main table query

`App\\Services\\DistrictDataSummaryQueryService::build(array $provinceIds = [], array $districtIds = []): Builder`

- Base model: `residences`
- Core predicate: `residences.mooban_type = PUBLIC`
- Joins:
  - `residence_stats_view as rsv`
  - `residence_activation_statuses as ras`
  - `mmbcnerp.thailand_sub_districts as sd`
  - `mmbcnerp.thailand_districts as td`
  - `mmbcnerp.thailand_provinces as tp`
  - `mmbcnerp.cdp_companies as pm`
  - `users as pmu`
- Aggregate relation:
  - `withMax(['subscriptionExpires as sg_expiry_date' => ...], 'expiry_date')`
- Filter application:
  - direct `whereIn` constraints on joined `tp.id` and `td.id`

### CS Zone constraint strategy

`App\\Services\\CustomerSuccessZoneService` provides CS Zone option and district/province option resolution, while `DdiFilterWidget` resolves district constraints for emitted filters.

## Data Sources

Primary read/write sources used by this module:

- `residences` (write model)
- `residence_stats_view` (read model for aggregated stats)
- `residence_activation_statuses`
- `subscription_expires`
- `mmbcnerp.thailand_sub_districts`
- `mmbcnerp.thailand_districts`
- `mmbcnerp.thailand_provinces`
- `mmbcnerp.cdp_companies`
- `users`
- `widget_aggregates` (global precomputed summary payloads)

## Caching Strategy

### CS Zone option/version cache

`CustomerSuccessZoneService` currently uses derived cache keys based on zone snapshot/fingerprint and selected IDs.

### Widget-level caches

Most widgets cache filtered aggregate queries with short TTL (typically 60-120 seconds), keyed by filter fingerprints including `customer_success_zone_ids`.

## Table Capabilities (`DistrictDataSummaryWidget`)

Main capabilities:

- Column set includes activation status, district, mooban names, totals, sign-up rate, PM type/company, juristic fields, SG expiry, and BPO supplier names.
- Filter set includes:
  - activation status
  - house type
  - service duration
  - AGM month
  - SG expiry month
  - security guard count
  - VMS/accounting/apps-user suppliers
  - property management type
- Sort default: `residences.updated_at desc`
- Pagination options: 10, 25, 50

## Operational Notes

- This module depends on cross-database joins against `mmbcnerp.*`; both schemas must be available from the same DB server context.
- `residence_stats_view` is treated as the performance read model; avoid reverting aggregate widgets to per-request heavy joins/count-distinct logic.

## Testing Notes (Project Rule)

Project-level testing policy:

- Do NOT use `RefreshDatabase` in this repository.
- Do NOT run `migrate:fresh` from tests.

Recommended approach for this module:

- Use stable seeded fixtures in the test database.
- Keep tests read-focused where possible.
- For mutating tests, use transaction-based isolation in the test runtime where compatible with the execution model.

## Maintenance Checklist

When extending DDI, verify all items:

1. New filters are added consistently to `DdiFilterWidget` dispatch payload and all listeners.
2. Cache keys include any new filter dimensions.
3. CS Zone behavior remains consistent in both table query and widget aggregations.
4. Widget listeners stay aligned to `ddi-filters-updated` and secondary market-share filter event.
5. Authorization remains policy/gate based.
6. New tests avoid database refresh traits and destructive schema resets.
