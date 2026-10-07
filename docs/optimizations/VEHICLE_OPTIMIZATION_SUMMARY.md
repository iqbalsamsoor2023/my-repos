# Vehicle Dashboard Optimization Summary

Docs hub: [README.md](./README.md)

## Purpose

The Vehicles module is the global vehicle master-data page. Super Admins and Admins see all vehicles across all residences in the header dashboard, while Property Management users see only their assigned residence's vehicle data. The optimization keeps unfiltered widgets instant through `widget_aggregates` (pre-computed every 5 minutes) and makes filtered widgets react within milliseconds using the `vehicle_stats_view` read model, so table filters and widget counts stay aligned without any runtime JOINs on large tables.

**Access**: Super Admin & Admin (full view), Property Management (residence-scoped view), Property Management Operation Center (multi-residence scoped view).

**Filters**: Mooban Type & Sub Type, Province/District/Subdistrict/Main Road, Residence, Status, Dates, Vehicle attributes — all resolved against denormalized columns in `vehicle_stats_view` (no `whereHas` chains).

## Pages

| Page | Route | Purpose |
|------|-------|---------|
| `ListVehicles` | `/admin/vehicles` | Vehicles table plus header dashboard widgets |
| `CreateVehicle` | `/admin/vehicles/create` | Register a vehicle |
| `EditVehicle` | `/admin/vehicles/{id}/edit` | Edit a vehicle |

## Widgets

| Widget | Role Scope | Key Data |
|--------|------------|---------|
| `VehicleTypeChart` | Super Admin / Admin / PM | Car vs Motorcycle donut (total_cars, total_motorcycles) |
| `VehicleCreationsChart` | Super Admin / Admin | Creation trend line per day/week/month |
| `SectionHeadingCar` | Super Admin / Admin / PM | UI section heading — no data query |
| `CarBrandsChart` | Super Admin / Admin / PM | Top 20 car brands line chart (car_brands) |
| `CarBrandsStatsOverview` | Super Admin / Admin / PM | Stat cards per car brand |
| `CarFuelTypeChart` | Super Admin / Admin / PM | Fuel type donut (car_fuel_types) |
| `CarFuelTypeStatsOverview` | Super Admin / Admin / PM | Fuel type stat cards |
| `CarBodyTypeChart` | Super Admin / Admin | Body type bar (car_body_types) |
| `CarBodyTypeStatsOverview` | Super Admin / Admin | Body type stat cards |
| `CarAgeChart` | Super Admin / Admin | Vehicle age distribution bar (car_model_years → age buckets) |
| `CarAgeStatsOverview` | Super Admin / Admin | Vehicle age stat cards |
| `CarTopInsuranceChart` | Super Admin / Admin | Top 20 car insurance companies line chart |
| `CarInsuranceCompanyStatsOverview` | Super Admin / Admin | Car insurance stat cards |
| `SectionHeadingMotorcycle` | Super Admin / Admin / PM | UI section heading — no data query |
| `MotorcycleBrandChart` | Super Admin / Admin / PM | Top 20 motorcycle brands line chart |
| `MotorcycleBrandsStatsOverview` | Super Admin / Admin / PM | Motorcycle brand stat cards |
| `MotorcycleBodyTypeChart` | Super Admin / Admin | Motorcycle body type donut |
| `MotorcycleBodyTypeStatsOverview` | Super Admin / Admin | Motorcycle body type stat cards |
| `MotorcycleAgeChart` | Super Admin / Admin | Motorcycle age distribution bar |
| `MotorcycleAgeStatsOverview` | Super Admin / Admin | Motorcycle age stat cards |
| `MotorcycleTopInsuranceChart` | Super Admin / Admin | Top 20 motorcycle insurance companies line chart |
| `MotorcycleInsuranceCompanyStatsOverview` | Super Admin / Admin | Motorcycle insurance stat cards |

## Data Sources

| Table / Service | Purpose |
|----------------|---------|
| `vehicles` | Source of truth for vehicle records and timestamps |
| `vehicle_models` | Vehicle model metadata (type, body_type, vehicle_brand_id) |
| `vehicle_brands` | Brand name denormalized into `vehicle_stats_view` |
| `vehicle_stats_view` | Vehicle-level read model, one row per active vehicle; used by all widgets |
| `widget_aggregates` | Pre-computed global payload keyed `vehicle_global_stats` / module `vehicle` |
| `VehicleWidgetDataService` | Aggregation layer for all vehicle widgets |

## Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `VehicleWidgetDataService` | `app/Services/` | Reads from `vehicle_stats_view`, handles cache versioning, per-request deduplication, age bucketing, and aggregates widget payloads |
| `VehicleStatsViewSyncService` | `app/Services/` | Rebuilds one or all vehicle rows; refreshes global widget aggregates |
| `VehicleStatsView` | `app/Models/` | Eloquent model for `vehicle_stats_view` |
| `SyncVehicleStatsViewJob` | `app/Jobs/` | Queue job (StatsSync, ShouldBeUnique 30s) that refreshes one vehicle after writes |
| `VehicleObserver` | `app/Observers/` | Dispatches stats sync after vehicle saved/deleted/restored |
| `SyncVehicleStatsView` | `app/Console/Commands/` | Artisan command `vehicle:sync-stats-view` |
| `SyncVehicleWidgetAggregates` | `app/Console/Commands/` | Artisan command `vehicle:sync-widget-aggregates` |
| `VehiclesTable` | `app/Filament/Resources/Vehicles/Tables/` | Table with all filters (mooban, province, residence, status, dates, vehicle attributes) |

## Performance Optimizations

### Three-Tier Caching

1. **Widget Aggregates (MySQL)** — Pre-computed every 5 minutes for unfiltered SA/Admin view (zero DB queries).
2. **Versioned Laravel Cache (Redis/file)** — 60s TTL for filtered views, invalidated by version bump on every sync.
3. **Process-Level Request Cache** — Static `$requestCache` array in `VehicleWidgetDataService` deduplicates calls from 20+ widgets in the same Livewire render cycle. All widgets call `getScopedDataForUser()` independently; only the first call computes — the other 19 return instantly from memory. Automatically resets per PHP-FPM request. Call `flushRequestCache()` in tests.

### Read-Model Prefilter for Eloquent Query

`VehicleResource::getEloquentQuery()` uses `whereIn('id', vehicle_stats_view subquery)` instead of `whereHas` chains. The `vehicle_stats_view` table only contains vehicles with valid (non-soft-deleted) relationships (vehicleModel, unit, residence), so this single indexed subquery replaces 3 correlated `EXISTS` subqueries. At 1M+ rows, this avoids row-by-row scanning.

### Centralized Age Bucketing

Age-range bucketing (model_year → "1-5 years", "6-10 years", etc.) is computed once in `VehicleWidgetDataService::aggregateQuery()` as `car_age_buckets` / `motorcycle_age_buckets`. The 4 age widgets (CarAgeChart, CarAgeStatsOverview, MotorcycleAgeChart, MotorcycleAgeStatsOverview) consume pre-bucketed data directly. Age ranges are defined as `AGE_RANGES` on the service; colors come from `WidgetColorPalette::vehicleAge()`.

### DRY Service Helpers

`aggregateQuery()` uses extracted helpers to avoid duplicate query blocks:
- `groupByBrand($base, $vehicleType)` — top 20 brands
- `groupByModelYear($base, $vehicleType)` — model year distribution
- `groupByInsurance($base, $vehicleType)` — top 20 insurance companies
- `groupByNullableInt($base, $column, $vehicleType)` — enum-value distribution with null handling

## Data Flow

```text
vehicle write (create / update / delete / restore)
      -> VehicleObserver dispatches SyncVehicleStatsViewJob($vehicleId) afterCommit on StatsSync queue
            -> VehicleStatsViewSyncService::syncOne($vehicleId)
                  -> upsert one row into vehicle_stats_view (JOINs vehicles→vehicle_models→vehicle_brands→units→residences→insurance_companies)
                  -> bump filtered widget cache version (vsv_widget_version)

manual full refresh
      -> php artisan vehicle:sync-stats-view [--chunk=1000]
            -> VehicleStatsViewSyncService::syncAll()
                  -> chunk active vehicle IDs in batches
                  -> bulk JOIN + upsert into vehicle_stats_view via chunkById(vehicleId alias)
                  -> remove stale rows for soft-deleted vehicles
            -> VehicleStatsViewSyncService::syncWidgetAggregates()
                  -> compute global aggregates and write to widget_aggregates[vehicle_global_stats]
                  -> bump filtered widget cache version

scheduled aggregate refresh
      -> every 5 minutes: php artisan vehicle:sync-widget-aggregates
            -> VehicleStatsViewSyncService::syncWidgetAggregates()

widget read path (Super Admin / Admin, no filters)
      -> VehicleWidgetDataService::getAllData([])
            -> WidgetAggregate::getCached('vehicle_global_stats', 300, 'vehicle') — instant
            -> if miss: computeFromReadModel([]) + putCache

widget read path (Super Admin / Admin, with filters)
      -> VehicleWidgetDataService::getAllData($filters)
            -> Cache::remember('vsv_widget_{version}_{md5(filters)}', 60s)
                  -> VehicleWidgetDataService::computeFromReadModel($filters)
                        -> aggregateQuery() — multiple GROUP BY queries on vehicle_stats_view with composite indexes

widget read path (Property Management)
      -> VehicleWidgetDataService::getByResidenceId($residenceId, $filters)
            -> Cache::remember('vsv_pm_{version}_{residenceId}_{md5(filters)}', 60s)
```

## Caching

| Cache Layer | TTL | Populated By |
|-------------|-----|-------------|
| `widget_aggregates[vehicle_global_stats]` (MySQL) | 300s (5 min) | `vehicle:sync-widget-aggregates` (scheduled every 5 min) |
| `vsv_widget_{version}_{md5(filters)}` (app cache) | 60s | `VehicleWidgetDataService::getAllData($filters)` on cache miss |
| `vsv_pm_{version}_{residenceId}_{md5(filters)}` | 60s | `VehicleWidgetDataService::getByResidenceId()` on cache miss |
| `vsv_widget_version` | persistent | incremented by `bumpCacheVersion()` on each sync |
| Process-level `$requestCache` (static array) | per-request | `getScopedDataForUser()` and `getScopedCreationTrendForUser()` — auto-resets per PHP-FPM request |

### Widget Interaction Pattern

- Vehicle widgets follow the same pattern as Unit and UnitUser widgets: `InteractsWithPageTable` in each widget + direct calls to scoped widget data service methods.
- Vehicle, Unit, UnitUser, and Residence module checks now follow the same user lookup convention: `Auth::user()` for policy/service checks (keep `Filament::auth()->user()` only where Filament panel guard context is required).
- Do not introduce Vehicle-only widget interaction traits unless there is a cross-module adoption plan.

## Database Schema: `vehicle_stats_view`

| Column | Type | Notes |
|--------|------|-------|
| `vehicle_id` | unsignedBigInteger PK | One row per vehicle |
| `unit_id` | unsignedBigInteger | FK to units |
| `residence_id` | unsignedBigInteger | Denormalized for fast scoping |
| `property_management_user_id` | unsignedBigInteger nullable | For PM role scoping |
| `vehicle_type` | smallInteger | VehicleType enum (1=Car, 2=Motorcycle) |
| `vehicle_brand_id` | unsignedBigInteger nullable | |
| `vehicle_brand_name` | varchar nullable | Denormalized for GROUP BY without JOIN |
| `body_type` | smallInteger nullable | CarBodyType / MotorcycleBodyType enum |
| `fuel_type` | smallInteger nullable | FuelType enum |
| `model_year` | unsignedSmallInteger nullable | For age distribution |
| `insurance_company_id` | unsignedBigInteger nullable | |
| `insurance_company_name` | varchar nullable | Denormalized (Thai) for GROUP BY without JOIN |
| `insurance_company_name_en` | varchar nullable | English name for chart labels |
| `residence_activation_status_id` | unsignedSmallInteger nullable | Indexed for status filter |
| `mooban_type` | unsignedTinyInteger nullable | Denormalized from residences |
| `sub_type` | unsignedTinyInteger nullable | Denormalized from units |
| `subdistrict_id` | unsignedBigInteger nullable | For province/district/subdistrict filter chain |
| `main_road` | varchar nullable | For main road search filter |
| `vehicle_created_at` | timestamp | For creation trend charts |
| `vehicle_updated_at` | timestamp | For updated date filters |
| `synced_at` | timestamp | Last sync time |

### Composite Indexes

| Index | Columns | Supports |
|-------|---------|---------|
| `vehicle_stats_view_type_brand_index` | (vehicle_type, vehicle_brand_id) | Brand breakdown per type |
| `vehicle_stats_view_type_fuel_index` | (vehicle_type, fuel_type) | Fuel type breakdown |
| `vehicle_stats_view_type_body_index` | (vehicle_type, body_type) | Body type breakdown |
| `vehicle_stats_view_type_year_index` | (vehicle_type, model_year) | Age distribution |
| `vehicle_stats_view_type_insurance_index` | (vehicle_type, insurance_company_id) | Insurance breakdown |
| `vehicle_stats_view_residence_type_index` | (residence_id, vehicle_type) | PM/PMOC residence scoping |
| `vehicle_stats_view_status_type_index` | (residence_activation_status_id, vehicle_type) | Status filter |
| `vehicle_stats_view_type_created_index` | (vehicle_type, vehicle_created_at) | Creation trend chart |

## Scheduling (Kernel.php)

```php
$schedule->command('vehicle:sync-stats-view')->dailyAt('05:30')->withoutOverlapping(60);
$schedule->command('vehicle:sync-widget-aggregates')->everyFiveMinutes()->withoutOverlapping(10);
```

## Queue Requirements

All vehicle-level sync jobs run on the **`StatsSync`** queue.

```sh
php artisan queue:work --queue=StatsSync
```

## Artisan Commands

```sh
# Full rebuild of vehicle_stats_view + widget aggregates
php artisan vehicle:sync-stats-view [--chunk=1000]

# Refresh only the global widget_aggregates cache (fast, no sync)
php artisan vehicle:sync-widget-aggregates
```

## Tests

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Services/VehicleWidgetDataServiceTest.php` | Pest Feature | computeFromReadModel, filters, residence scoping, cache versioning, age buckets, request cache |
| `tests/k6/stress-test-vehicles.js` | k6 Load Test | Base page + 5 filter scenarios, 10 VUs (local) / 100 VUs (prod) |

Run the service tests:

```sh
./vendor/bin/pest tests/Feature/Services/VehicleWidgetDataServiceTest.php
```

Run the k6 stress test (local):

```sh
k6 run --env LOCAL_DEV=true --env BASE_URL=https://mymooban-2.test \
       --env EMAIL=your@email.com --env PASSWORD=yourpassword \
       tests/k6/stress-test-vehicles.js
```

## Localization

- Vehicle widget headings, labels, and stat descriptions should use translation keys instead of hardcoded literals.
- Keep vehicle locale files in parity for every user-facing change:
      - `resources/lang/en/vehicle.php`
      - `resources/lang/th/vehicle.php`
- Reuse shared fallback keys such as `user.not_yet_set` where applicable.

## Before / After Comparison

| Metric | Before | After |
|--------|--------|-------|
| Widget service calls per page load | 22 × independent `getScopedDataForUser()` | 1 real computation + 19 instant returns (process-level cache) |
| Widget queries per page load | 22 × (2-5 JOINs per query) | 0 for cached; ~12 indexed GROUP BY queries when filtered |
| Table base query | 3 nested `whereHas` correlated subqueries | `whereIn('id', vehicle_stats_view PK subquery)` — single indexed semi-join |
| Province/District filter | 5-table `whereHas` chain | `whereIn('subdistrict_id', ...)` on denormalized read model |
| Mooban Type/SubType filter | 2-table `whereHas` chain | `whereIn('mooban_type', ...)` on denormalized read model |
| Residence filter | `whereHas('unit.residence')` chain | `vehicle_stats_view.residence_id` (indexed) |
| Age bucketing | 4 copies of identical logic across widgets | Computed once in service, returned as `car_age_buckets` / `motorcycle_age_buckets` |
| Unfiltered load time | 200-3000ms per widget | <10ms (widget_aggregates cache) |
| Filtered load time | 500-5000ms per widget | 5-50ms (indexed read model) |
| Observer sync | None — no live updates to dashboard counts | `VehicleObserver` syncs one row per change, afterCommit |
| Scale target | ~10k vehicles degraded | 1M+ vehicles with flat response time |
