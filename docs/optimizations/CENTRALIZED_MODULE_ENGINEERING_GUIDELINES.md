# Centralized Module Engineering Guidelines

## Purpose

This document consolidates the reusable implementation patterns validated in these modules:

- District Dashboard (DDI)
- Residence
- Residence Info
- Residence BPO
- Unit
- Unit User
- Vehicle
- Pet
- Parcel

Use this as the default reference when refactoring or optimizing other modules so architecture, query patterns, and UX behavior stay consistent across the project.

## Technology Baseline (Current Project)

| Layer | Version |
| --- | --- |
| PHP | 8.4 |
| Laravel | 12.x |
| Filament | 4.x |
| Livewire | 3.x |
| Pest | 3.x |
| PHPUnit | 11.x |
| Tailwind CSS | 3.x |

## Core Architectural Principles

1. Keep write models and read models separated for analytics-heavy screens.
2. Reuse shared support/services before introducing new helper logic.
3. Keep filter state deterministic (normalized arrays, stable cache keys).
4. Keep authorization centralized (policy constants + gate checks).
5. Keep table/widget code thin; move expensive query logic into services/read models.
6. Prefer cached option providers and aggregate snapshots over repeated live computations.

## Canonical Module Structure

Use this layout for all data-heavy Filament modules:

```text
app/Filament/Resources/<Module>/
  <Module>Resource.php
  Pages/List<Module>.php
  Tables/<Module>Table.php
  Widgets/*.php

app/Services/
  <Module>QueryService.php
  <Module>WidgetDataService.php
  <ReadModel>SyncService.php

app/Support/
  <Module>Support.php
  <SharedFilterHelper>.php
  <OptionsSupport>.php

tests/Unit/
  <Module>SupportTest.php
  <Module>QueryServiceTest.php

tests/Feature/Filament/
  <Module>ResourcePageTest.php
```

## Reuse-First Shared Building Blocks

Use these existing components before creating new utilities:

| Component | Reuse Standard |
| --- | --- |
| `App\Support\DdiWidgetSupport::normalizeIds()` | Normalize incoming filter values to sorted unique int arrays. |
| `App\Support\DdiWidgetSupport::cacheKey()` | Build deterministic cache keys from normalized payloads. |
| `App\Services\DistrictDataSummaryQueryService::build()` | Canonical DDI table query with read-model joins and province/district scoping. |
| `App\Services\ResidenceWidgetDataService::getAllData()` | Single source for Residence header widget aggregates. |
| `App\Services\ResidenceStatsViewSyncService::*` | Read-model synchronization + global aggregate precomputation. |
| `App\Services\ThailandLocationService::*` | Shared cached province/district/subdistrict/main-road options + residence id resolver. |
| `App\Support\QueryGuardSupport::denyAll()/noRowsClause()` | Canonical empty-result query guard for impossible scopes (for example: no accessible residences under selected filters). |
| `App\Services\CustomerSuccessZoneService::*` | CS Zone options, district resolution, and zone-based query constraints. |
| `App\Support\ResidenceOptionsSupport::*` | Cached options for activation status, BPO supplier, visible residences, guard count. |
| `App\Support\ResidenceLocationFilterSupport::makeProvinceFilters()` | Shared province/district/subdistrict/main-road filter schema + query contract for Residence-family tables. |
| `App\Services\ResidenceInfoWidgetDataService::getAllData()` | Shared read-model + short-lived cache aggregate source for Residence Info widgets. |
| `App\Support\WidgetColorPalette::residenceInfo*()` | Shared chart/stat color mappings for Residence Info visual consistency. |
| `App\Services\UnitWidgetDataService::getAllData()/getByResidenceIds()/getDashboardDataForUser()` | Unit widget aggregate source from `units_stats_view` with PM/Operation Center scoped filtering and short-lived cache keys. |
| `App\Services\UnitStatsViewSyncService::syncOne()/syncAll()/syncWidgetAggregates()` | Unit read-model sync and cache-version invalidation source for Unit widget data freshness. |
| `App\Services\UnitImportService::import()` | Canonical Unit Excel import flow: read rows, validate all rows first, then transactional upsert. |
| `App\Services\UnitUserWidgetDataService::getScopedDataForUser()/getScopedCreationTrendForUser()` | Unit User widget aggregate source from `unit_user_stats_view` with role-scoped filtering and cached aggregate payloads. |
| `App\Support\WidgetColorPalette::unitUser*()` | Shared Unit User chart/stat color mappings (gender, owner/tenant, activation status, age groups, country series, creation trend). |
| `App\Services\UnitUserImportService::import()` | Canonical Unit User Excel import flow: validate row contract first, then transactional user/unit-user persistence with post-commit notification mail. |
| `App\Services\VehicleWidgetDataService::getScopedDataForUser()/getScopedCreationTrendForUser()/getAllData()` | Vehicle widget aggregate source from `vehicle_stats_view` with role-scoped filtering and deterministic short-lived cache keys. |
| `App\Services\VehicleStatsViewSyncService::syncOne()/syncAll()/syncWidgetAggregates()` | Vehicle read-model sync and cache-version invalidation source for Vehicle dashboard freshness. |
| `App\Support\VehicleDashboardSupport::*` | Shared Vehicle dashboard scoping and filter normalization support for resource queries, widget access checks, and cache-key determinism. |
| `App\Support\VehicleAgeCategorySupport::*` | Shared Vehicle age-category options, read-model/table filtering contract, and age bucket aggregation rules. |
| `App\Support\VehicleInsuranceSupport::*` | Shared insurance-company display and fallback formatting for Vehicle table and exports. |
| `App\Support\WidgetColorPalette::vehicle*()` | Shared Vehicle chart/stat color mappings (vehicle type, creation trend, brand/insurance series, body type, fuel type, age). |
| `App\Services\PetWidgetDataService::getScopedDataForUser()/getScopedCreationTrendForUser()/getAllData()` | Pet widget aggregate source with role-scoped filtering, deterministic cache keys, and centralized filter application. |
| `App\Support\PetDashboardSupport::*` | Shared Pet dashboard filter normalization and deterministic cache-key payload flattening helpers. |
| `App\Support\WidgetColorPalette::pet*()` | Shared Pet chart/stat color mappings (pet type, creation trend, age distribution). |
| `App\Services\ParcelWidgetDataService::getScopedDataForUser()/getScopedCreationTrendForUser()/getScopedStatusTrendForUser()/getAllData()` | Parcel widget aggregate source from `parcel_stats_view` with role-scoped filtering (Global Admin / PM / OpCenter), deterministic short-lived cache keys, and centralized filter application. |
| `App\Services\ParcelStatsViewSyncService::syncOne()/syncAll()/syncWidgetAggregates()` | Parcel read-model sync and cache-version invalidation source for Parcel widget data freshness. |
| `App\Support\WidgetColorPalette::parcelStatusColors()/parcelCourierSeriesColors()/parcelCreationTrendHex()` | Shared Parcel chart/stat color mappings (status, courier series, creation trend). |
| `App\Support\SpreadsheetImportSupport::*` | Shared upload-path resolution, import file cleanup, and row-level error message formatting. |
| `App\Services\FilamentExport\FilamentTableExportSupport::*` | Shared Filament table export helpers for visible columns, selected IDs, file naming, and export audit logging. |
| `App\Support\BpoSoftwareSupplierFilterHelper::*` | Standard JSON filtering/search for BPO supplier fields. |
| `App\Models\WidgetAggregate::getCached()/putCache()` | Module-scoped global aggregate cache for unfiltered widget states. |

## Standardized Authorization Pattern

Use policy as the single source of truth. Each module should follow one of the patterns below consistently.

### Pattern A: Ability Constant + Gate (dashboard pages)

1. Define ability constants in a dedicated policy.
2. Register gate in `AuthServiceProvider` using policy constant.
3. Reuse the same ability check in resource/page `canAccess()`, `shouldRegisterNavigation()`, and widget `canView()`.

Reference implementation: District Dashboard policy/gate flow.

### Pattern B: Policy Helper Methods (resource modules)

1. Expose module-specific static helper methods on the module policy (for example: `hasDashboardAccess()`, `hasAdminDashboardAccess()`, `isGlobalAdmin()`, `isPropertyManager()`).
2. Reuse those policy helpers in all access points: resource `canAccess()`, resource `shouldRegisterNavigation()`, widget `canView()`, and table filter/action visibility callbacks.
3. Do not duplicate role arrays or hardcoded role checks in widgets, tables, or support classes.
4. If a support class performs role-aware scoping, it must delegate role decisions to policy helper methods.
5. Keep authenticated user lookup style consistent within module code: use `Auth::user()` for policy/service checks, and only use `Filament::auth()->user()` where panel guard context is explicitly required.
6. Avoid mixing `Auth::user()` and `auth()->user()` inside the same module; Unit, UnitUser, Residence, Vehicle, and Pet modules should standardize on `Auth::user()`.

## Standardized Filter Architecture

### 1. Event-Driven Widget Filters (DDI Pattern)

- Primary event: `ddi-filters-updated`
- Secondary event for widget subsets: `ddi-resident-market-share-filters-updated`
- Required payload shape:

```php
[
    'customer_success_zone_ids' => int[],
    'province_id' => ?int, // compatibility key when needed
    'province_ids' => int[],
    'district_ids' => int[],
]
```

Rules:

1. Normalize IDs before state set and before dispatch.
2. Reset dependent filters when parent filter changes.
3. Use deterministic effective district resolution when CS Zone intersects location filters.

### 2. Table-Driven Filters (Residence/Residence Info/Residence BPO Pattern)

- Keep grouped filter schemas in dedicated table classes (`Filter::make(...)->schema([...])`).
- Use `ExposesTableToWidgets` on list pages.
- Use `InteractsWithPageTable` in widgets and refresh on filter updates.
- Use `ResidenceLocationFilterSupport::makeProvinceFilters()` for Residence, Residence Info, and Residence BPO location filter reuse.
- For Unit module filters, keep filter payload keys aligned between table and widgets (`unit_search`, `mooban`, `province_filters`, `status_filters`, `date_filters`) so `UnitWidgetDataService::applyReadModelFilters()` remains table-consistent.
- Keep filter group naming consistent across modules:
  - `mooban`
  - `province_filters`
  - `status_filters`
  - `date_filters`
  - `companies`
  - module-specific operational filters

### 3. Location Filter Standard

1. All location selectors should source options from `ThailandLocationService` and CS zone-aware providers.
2. Use `->searchable()->preload()->native(false)` for location selects where appropriate.
3. Reset child selections on parent changes (`province -> district -> subdistrict`).
4. For very large fact tables, resolve `residence_id` first via `ThailandLocationService::getResidenceIdsByLocation()` and apply `whereIn('residence_id', ...)`.

## Query and Read-Model Standards

### 1. Read Model First for Analytics

- Use `residence_stats_view` for dashboard/widget aggregate queries.
- Do not regress analytics widgets to per-request deep relation joins when read-model data exists.
- For Residence Info widgets, prefer `ResidenceInfoWidgetDataService` (read-model + short TTL cache) over per-widget relation join queries.
- For Unit widgets, use `units_stats_view` through `UnitWidgetDataService` and avoid direct `units` + `unit_user` joins in each widget class.
- For Vehicle widgets, use `vehicle_stats_view` through `VehicleWidgetDataService` and avoid per-widget joins to `vehicles`, `vehicle_models`, and related tables.

### 2. Resource Query Baseline

For table resources that display aggregate fields:

1. Start from model query for policies/scopes.
2. Join `residence_stats_view` for computed columns (`distinct_user_count`, `sign_up_percentage`, `units_count`, etc.).
3. Keep eager loads explicit for relation-driven columns.

Unit-specific note:

1. `UnitResource` table remains the write-model query (`units`) for row-level operations, policies, and relation-driven columns.
2. Unit widgets should read aggregate metrics from `units_stats_view` (read model) to keep dashboard performance stable under filtering.

### 3. Aggregation Query Pattern

- Prefer one grouped aggregate query + in-memory mapping over many small count queries.
- Keep query builders in service classes when reused by more than one widget/table.

### 4. Analyzer-Friendly Query Conventions

- Prefer explicit direction in `orderBy('column', 'asc'|'desc')`.
- Use explicit null guards before `whereIn()` on dynamic arrays.
- Keep projection explicit for aggregate queries.
- For impossible scopes (for example resolved ID list is empty), use `QueryGuardSupport::denyAll($query)` instead of ad-hoc raw SQL literals.

## Caching and Precomputation Standards

### Cache Tiering

| Tier | Typical TTL | Usage |
| --- | --- | --- |
| Global aggregate snapshot | 300s | Unfiltered dashboard/widget states from `widget_aggregates`. |
| Filtered runtime widget cache | 60-120s | Short-lived per-filter aggregates. |
| Option cache | 6h | Provinces/districts/subdistricts/options lists. |
| Mapping cache (CS zone -> district ids) | 30m | Zone lookup reuse. |
| Location-to-residence resolver cache | 5m | Heavy dependent location filter paths. |
| Unit widget filtered cache (`usv_*`) | 60s | Unit dashboard runtime aggregates, versioned via `usv_widget_version`. |
| Vehicle widget filtered cache (`vsv_*`) | 60s | Vehicle dashboard runtime aggregates, versioned via `vsv_widget_version`. |

Rules:

1. Cache keys must be deterministic and derived from normalized filters.
2. Unfiltered state should always attempt aggregate snapshot first.
3. Filtered state should use short TTL `Cache::remember`.
4. Keep cache namespaces/module slugs explicit to avoid collisions.

Environment note:

1. Shared environments should use Redis-backed cache (`CACHE_DRIVER=redis`) for consistent cross-request and cross-worker widget cache behavior.
2. Local development may still use file/array cache, but performance validation for dashboards should be performed against Redis-like behavior.

## Widget Implementation Standards (Filament v4)

1. Disable polling by default unless real-time behavior is required.
2. Keep data assembly in PHP class methods, not Blade loops.
3. If widget data is heavy, pass precomputed arrays through `getViewData()`.
4. Keep color and label mapping centralized (enums/support palettes), not duplicated per widget.
5. Use consistent chart/stat semantics across modules (count + percentage + total context).

## Table and Filter UX Standards

1. Keep default sort explicit and aligned with business relevance.
2. Keep pagination options bounded (`10, 25, 50` or module-appropriate variants).
3. Mark non-critical columns toggleable.
4. Use grouped filter fieldsets for readability.
5. Keep bilingual search where domain data supports dual-language names.

## Import and Export Standards

1. Keep List page actions thin; move import row parsing, validation, and persistence into dedicated service classes.
2. Validate full import payload first, then write using `DB::transaction(...)` only when there are no row errors.
3. Keep row-level validation errors deterministic and user-readable (`Row X - Column Y: message`).
4. Reuse `FilamentTableExportSupport` in table bulk export actions to avoid re-implementing column extraction, selected IDs, and audit metadata.
5. Preserve existing UX behavior per module (for example: slugified vs non-slugified file names) through helper options, not duplicated closures.

### Own Service vs Shared Reuse (Cross-Module Reference)

Use this decision matrix when refactoring other modules:

| Service/Support | Ownership | Reuse Rule | Current Module References |
| --- | --- | --- | --- |
| `App\Services\UnitImportService` | Own (Unit domain) | Keep Unit-specific validation/persistence here. Do not reuse for other entities. | Unit |
| `App\Services\ResidenceImportService` | Own (Residence domain) | Keep Residence-specific side effects (accounts/subscriptions/features) here. Do not reuse for other entities. | Residence |
| `App\Services\UnitUserImportService` | Own (Unit User domain) | Keep resident onboarding rules (owner/main-tenant uniqueness, user linkage, unit-user creation) here. Do not reuse for other entities. | UnitUser |
| `App\Support\SpreadsheetImportSupport` | Shared | Reuse for upload path resolution, cleanup, and row-error formatting in all import services. | Unit, Residence, UnitUser |
| `App\Services\FilamentExport\FilamentTableExportSupport` | Shared | Reuse in every Filament bulk export action for visible columns, selected IDs, filename rules, and audit metadata. | Unit, Residence, Residence Info, Residence BPO, Vehicle, UnitUser |
| `App\Support\VehicleDashboardSupport` | Shared (Vehicle dashboard) | Reuse for Vehicle resource prefilter subqueries, role-aware widget visibility, and deterministic Vehicle filter normalization. | Vehicle |
| `App\Support\VehicleAgeCategorySupport` | Shared (Vehicle dashboard) | Reuse for Vehicle age-category select options, table/read-model filtering logic, and widget age-bucket computation. | Vehicle |
| `App\Support\VehicleInsuranceSupport` | Shared (Vehicle module) | Reuse for insurance display fallback formatting instead of duplicate table/private formatter methods. | Vehicle |
| `App\Support\ResidenceLocationFilterSupport` | Shared (Residence-family) | Reuse when module query path is compatible with Residence province/district/subdistrict relationships. | Residence, Residence Info, Residence BPO |
| `App\Services\ThailandLocationService` | Shared (global) | Reuse for location option providers and residence-id resolution instead of duplicate location queries. | DDI, Residence-family, Unit, Vehicle |

Decision rule:

1. Create an **own service** when validation rules, side effects, and persistence model are domain-specific.
2. Reuse a **shared support/service** when logic is transport/presentation cross-cutting (paths, formatting, table export boilerplate, location option loading).
3. If a module needs the same flow shape but different domain rules, create a new own service that composes shared supports instead of extending another module's own service.

## JSON and Enum Handling Standards

1. Route BPO supplier JSON filtering through `BpoSoftwareSupplierFilterHelper`.
2. Prefer enum `options()` and label methods for all user-facing state text.
3. Avoid hardcoded labels/colors when centralized enum or palette exists.

## Localization Standards (EN/TH)

1. Do not introduce new user-facing literals in services, validation errors, notifications, or actions; use translation keys.
2. For every new or changed translation key, update both `resources/lang/en/*.php` and `resources/lang/th/*.php` in the same PR.
3. Prefer explicit namespaced keys (for example `app.email`, `unit.unit_number`, `user.user`) over ambiguous root keys.
4. If a key is missing in either locale, add a temporary translation immediately and flag it for product wording review rather than shipping a missing-key fallback.
5. When touching module widgets/resources, scan for newly introduced hardcoded labels/headings/descriptions and replace them with translation keys before merge.
6. Translation verification is required in review: confirm key parity between `resources/lang/en/<module>.php` and `resources/lang/th/<module>.php` for every touched module.

## Read-Model Sync and Background Work Standards

1. Keep `ResidenceObserver`-driven targeted sync (`syncOne`) as primary freshness path.
2. Keep scheduled full sync (`syncAll`) as reconciliation/backstop.
3. Run aggregate precompute after sync to keep dashboard unfiltered loads instant.
4. Use scheduler overlap protection for sync commands.

## Testing Standards

For module refactors, maintain at least:

1. Unit tests for support/query contract determinism.
2. Policy tests for access constraints.
3. Feature tests for Filament resource query behavior and widget integration.

Recommended commands:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --filter=DistrictDashboardPolicyTest
php artisan test --compact --filter=DistrictDataSummaryQueryServiceTest
php artisan test --compact --filter=ResidenceBpoResourcePageTest
```

## Laravel + Filament Standards to Enforce in Future Refactors

### Laravel 12

1. Use `Cache::remember` as default cache retrieval pattern.
2. Use scheduler `withoutOverlapping()` for variable-duration tasks.
3. Use scheduler `onOneServer()` for multi-server single-run tasks.

### Filament 4

1. Always scope resource/page queries to user permissions (`getEloquentQuery`/`modifyQueryUsing`).
2. Keep resource responsibilities separated:
   - Resource class: access/navigation/model query
   - Table class: columns/filters/actions
   - Page class: widget registration + page-level actions
3. Keep page-to-widget filter synchronization through `ExposesTableToWidgets` + `InteractsWithPageTable`.

## Unit Module Alignment Notes

1. Unit dashboard widgets should not duplicate PM/Operation Center residence scope logic in each widget; use `UnitWidgetDataService::getDashboardDataForUser()` as the shared scope entrypoint.
2. Keep widget color mappings centralized in `WidgetColorPalette` (`unitStatusColors`, `unitOwnerTenantColors`, `unitSignUpColors`, `unitHouseType*`).
3. Keep Unit table filters and Unit widget filters contract-compatible so table and widget totals remain aligned under the same active filters.

## Unit User Module Alignment Notes

1. Keep Unit User dashboard widgets sourced from `unit_user_stats_view` through `UnitUserWidgetDataService`; avoid per-widget relation joins.
2. Keep `ListUnitUsers` page actions thin; import/upload actions should delegate to `UnitUserImportService` and only handle user-facing notifications.
3. Keep Unit User table export actions using `FilamentTableExportSupport` so audit metadata and selected-column behavior stay consistent with Unit and Residence-family modules.
4. Normalize Unit User filter payloads before cache key generation to avoid duplicate cache entries for equivalent filters in different order.
5. Keep Unit User widget color mappings centralized in `WidgetColorPalette` (`unitUserGenderColors`, `unitUserOwnerTenantColors`, `unitUserActivationStatusColors`, `unitUserAgeGroupColors`, `unitUserCountrySeriesColors`, `unitUserCreationTrendHex`) instead of hardcoded per-widget hex values.

## Vehicle Module Alignment Notes

1. Keep Vehicle dashboard widgets sourced from `vehicle_stats_view` through `VehicleWidgetDataService`; avoid per-widget relation joins.
2. Keep `VehicleResource::getEloquentQuery()` role-scoped prefiltering centralized through `VehicleDashboardSupport::vehicleIdScopeSubquery()`.
3. Keep Vehicle widget/table filter payloads normalized before cache key generation using `VehicleDashboardSupport::normalizedFilters()` to avoid duplicate cache entries for equivalent filters.
4. Keep Vehicle age-category options and filtering/bucketing logic centralized in `VehicleAgeCategorySupport` so table filters and widget aggregates stay contract-compatible.
5. Keep Vehicle insurance display fallback logic centralized in `VehicleInsuranceSupport` rather than duplicating private formatter methods in table/resource classes.
6. Keep Vehicle widget color mappings centralized in `WidgetColorPalette` (`vehicleTypeColors`, `vehicleCreationTrendColors`, `vehicleBrandSeriesColors`, `vehicleInsuranceSeriesColors`, `carBodyTypeColors`, `carFuelTypeColors`, `motorcycleBodyTypeColors`, `vehicleAge`) instead of hardcoded per-widget hex values.
7. Keep Vehicle access checks policy-driven via `VehiclePolicy` static helpers across resource/table/widget visibility logic; avoid introducing module-specific role-check traits or helpers that duplicate policy logic.
8. Keep Vehicle widgets aligned with Unit and UnitUser interaction style: use `InteractsWithPageTable` directly in each widget and call `VehicleWidgetDataService` scoped methods, instead of introducing Vehicle-only widget interaction traits.

## Pet Module Alignment Notes

1. Keep Pet dashboard widgets sourced through `PetWidgetDataService` and avoid per-widget query duplication.
2. Keep Pet access checks policy-driven via `PetPolicy` static helper methods across resource, table, and widget visibility logic.
3. Keep Pet widget/table filter payloads normalized before cache key generation using `PetDashboardSupport::normalizedFilters()`.
4. Keep Pet widget color mappings centralized in `WidgetColorPalette` (`petTypeColors`, `petCreationTrendColors`, `petAgeColors`) instead of hardcoded per-widget hex values.

## Parcel Module Alignment Notes

1. Keep Parcel dashboard widgets sourced from `parcel_stats_view` through `ParcelWidgetDataService`; avoid per-widget live `parcels` + joins queries.
2. Keep `ParcelResource::getEloquentQuery()` role-scoped via `ParcelPolicy` static helpers (`isPropertyManager`, `isOperationCenter`) and `Auth::user()` instead of `auth()->user()`.
3. Keep Parcel widget access checks policy-driven via `ParcelPolicy::hasDashboardAccess()` and role-specific helpers across resource, table, and widget `canView()` logic.
4. Keep Parcel widget filter payloads normalized via `ParcelWidgetDataService`'s internal `normalizedFilters()` before cache key generation.
5. Keep Parcel widget color mappings centralized in `WidgetColorPalette` (`parcelStatusColors`, `parcelCourierSeriesColors`, `parcelCreationTrendHex`) instead of hardcoded per-widget hex values.
6. Keep `ParcelObserver`-driven targeted sync (`syncOne` via `SyncParcelStatsViewJob`) as the primary freshness path; scheduled `parcel:sync-stats-view` (daily) + `parcel:sync-widget-aggregates` (every 5 min) as reconciliation backstop.
7. Keep `PmsStatsOverview` and `PmsLineChart` as PM-only widgets (not on the main parcel list page); they use `getScopedDataForUser`/`getScopedStatusTrendForUser` without table filter context. Polling is disabled on all parcel widgets.

## Laravel Boost Workflow Standard

For every backend optimization or refactor PR:

1. Use Boost docs search first for version-specific guidance.
2. Reuse Boost tools for schema/log/query inspection before coding.
3. Implement smallest safe change set.
4. Verify translation coverage for touched user-facing strings in both `resources/lang/en/*.php` and `resources/lang/th/*.php`.
5. Run formatter/tests.
6. Update module reference docs in `docs/optimizations/` when behavior changes.

## Refactor Playbook for Other Modules

1. Define access policy and navigation rules first.
2. Identify whether module needs a read model (analytics-heavy = yes).
3. Extract query logic into a service class.
4. Normalize filter payload contract and cache keys.
5. Centralize shared options/filter helpers in `app/Support` or `app/Services`.
6. Add/adjust unit + feature tests for contract coverage.
7. Document final module state in `docs/optimizations/`.

## Standard PR Checklist

- [ ] Access and navigation checks are policy-driven and consistent.
- [ ] Filter payloads are normalized and deterministic.
- [ ] Read-model usage is prioritized for analytics queries.
- [ ] Aggregate caching follows unfiltered snapshot + filtered short TTL strategy.
- [ ] Shared option/filter helpers are reused (no duplicate helper logic).
- [ ] Widget/table queries avoid unnecessary repeated counts/joins.
- [ ] New or changed user-facing strings are localized in both EN and TH files.
- [ ] Touched module locale files were parity-checked (`resources/lang/en/<module>.php` vs `resources/lang/th/<module>.php`).
- [ ] Formatter and targeted tests pass.
- [ ] Documentation index is updated when a new reference doc is added.

## Convergence Backlog Status

Completed in this pass:

1. Shared location filter schema/query builder extraction for Residence, Residence Info, and Residence BPO tables (`ResidenceLocationFilterSupport`).
2. Residence Info widget alignment to read-model-first and short-lived cache aggregate service (`ResidenceInfoWidgetDataService`).
3. Consolidation of Residence Info chart/stat color mappings into a shared support contract (`WidgetColorPalette::residenceInfo*`).
4. Parcel module read-model (`parcel_stats_view`) + sync service + widget data service + observer-driven sync + scheduled reconciliation commands.
5. Parcel widget refactor: all 6 widgets migrated off per-request live joins to `ParcelWidgetDataService`; polling disabled; color mappings centralized in `WidgetColorPalette`; `canView()` policy-driven via `ParcelPolicy` static helpers.
6. `ParcelResource` standardized: `Auth::user()`, `ParcelPolicy` static role helpers, explicit column projections, and `pickup_type` added to select list.

Recommended next convergence steps:

1. Add focused unit tests for `ParcelWidgetDataService` normalization, filter contracts, and `ParcelStatsViewSyncService` sync logic.
2. Consider adding unfiltered global `widget_aggregates` snapshot precompute for Residence Info widget payloads if dashboard traffic grows.
3. Gradually migrate other modules with repeated location-filter schemas to `ResidenceLocationFilterSupport` where relationship paths are compatible.
4. Run `php artisan parcel:sync-stats-view` after deployment to backfill `parcel_stats_view` with existing data.
