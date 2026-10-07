# District Dashboard Module Guidelines

## Purpose

This document defines the engineering standards for the District Dashboard module so future changes stay consistent, predictable, and performant in Laravel 12 + Filament v4.

Scope:

- District dashboard page
- DDI widgets and filter widget
- Supporting query/services/helpers used by DDI widgets
- Read-model and aggregate cache usage for DDI

## Module Map

Primary files:

- `app/Filament/Pages/DistrictDashboard.php`
- `app/Policies/DistrictDashboardPolicy.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Filament/Widgets/Default/Ddi/*`
- `app/Services/DistrictDataSummaryQueryService.php`
- `app/Services/CustomerSuccessZoneService.php`
- `app/Services/ResidenceStatsViewSyncService.php`
- `app/Models/WidgetAggregate.php`
- `resources/views/filament/widgets/default/ddi/*`

## Authorization Standard

Use one shared ability constant and avoid hardcoded ability strings in widgets/pages.

Required pattern:

1. Define ability in policy constant.
2. Register gate using that constant.
3. Use `Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY)` in:
   - page `canAccess()`
   - page `shouldRegisterNavigation()`
   - widget `canView()`

Reason:

- Prevents permission string drift.
- Keeps all dashboard access checks in one source of truth.

## Filter Contract Standard

The dashboard filter event contract is:

- Event name: `ddi-filters-updated`
- Payload keys:
  - `customer_success_zone_ids` (int[])
  - `province_id` (?int, compatibility field)
  - `province_ids` (int[])
  - `district_ids` (int[])

Rules:

1. Normalize all incoming IDs before use.
2. Normalization must:
   - remove null/empty values
   - cast to int
   - dedupe
   - sort ascending
3. Widgets should normalize in `applyFilters()` before storing state.

Implementation standard:

- Use `App\Support\DdiWidgetSupport::normalizeIds()` as the shared normalization contract.

Reason:

- Deterministic state and cache keys.
- Prevents equivalent selections from producing different cache keys.

## Caching Standard

### Global aggregates (unfiltered)

For no-location-filter state, prefer precomputed aggregates from `WidgetAggregate`, such as:

- `ddi_global_stats`
- `ddi_grid_card_data`

### Filtered state

For filtered state, use short-lived cache (`Cache::remember`) with normalized filter arrays.

Key rules:

1. Prefix each key with widget-specific slug.
2. Hash only normalized filters.
3. Keep TTL short for filter caches (e.g., 60-120 seconds).

Implementation standard:

- Use `App\Support\DdiWidgetSupport::cacheKey($slug, $filters)` for deterministic key generation.

Recommended key pattern:

```php
$cacheKey = 'ddi_<widget_slug>_' . md5(json_encode([
    $this->provinceIds,
    $this->districtIds,
    // optional additional normalized filters
]));
```

## Query and Data Source Standard

1. Use `residence_stats_view` for DDI widgets whenever possible.
2. For grid cards, use one grouped batch query and derive totals in PHP instead of running a second totals query.
3. Reuse service methods for expensive reusable lookups:
   - `CustomerSuccessZoneService::getDistrictIds()`
4. Keep DDI table query construction in dedicated service classes:
   - `DistrictDataSummaryQueryService`

Reason:

- Fewer repetitive joins/queries.
- Better read-model consistency and simpler widget code.

## Widget Construction Standard (Filament v4)

1. Use `#[On('ddi-filters-updated')]` to receive filter state.
2. Use `dispatch('$refresh')` after filter state changes.
3. Keep `canView()` authorization explicit in each widget.
4. Keep heavy data generation in widget classes, not in Blade.

Blade rule:

- If widget provides `getViewData()`, Blade must consume passed data (e.g., `$cards`) and must not recompute using `$this->...` heavy methods.

## Card/Grid Widget Standard

For `PropertyStatusGridCardsWidget` style widgets:

1. Use deterministic card generation only.
2. Never include placeholder/random values in production rendering.
3. Keep card ordering intentional and stable.
4. Separate data-fetching from card-mapping logic.

## Shared Constants and Enums

Avoid scattered magic values.

Current examples to centralize when expanded further:

- activation status IDs used for active trend logic
- module/key naming for widget aggregates

Preferred approach:

- Add enum/constants for status groups and shared aggregate modules when touching related logic.

## Frontend/View Standard

1. Keep widget Blade templates presentation-only.
2. Avoid duplicate expensive method calls in view loops.
3. If inline styles grow, extract to shared stylesheet/theme asset.

## Testing and Validation Standard

For every DDI module change:

1. Run formatter:
   - `vendor/bin/pint --dirty --format agent`
2. Run the smallest relevant tests:
   - policy tests for access changes
   - feature/widget-related tests when adding widget behavior
3. If filters or caches are changed, validate:
   - equivalent filter selections produce same results
   - no-filter state still reads from aggregate cache paths

## Recommended Backlog Improvements

These are recommended for next iterations:

1. Add integration tests for DDI filter dispatch contract.
2. Review and standardize activation status constants across widgets/services.
3. Consider promoting DDI widget CSS into a registered Filament theme asset when a panel theme pipeline is introduced.

## Change Checklist

Before merging DistrictDashboard changes, confirm:

- [ ] No hardcoded `view-district-dashboard` usage outside policy constant.
- [ ] Filter IDs are normalized and sorted.
- [ ] No random/placeholder UI data in production widgets.
- [ ] Blade views do not recompute heavy widget methods.
- [ ] Queries use read-model and grouped aggregations where applicable.
- [ ] Pint and targeted tests pass.

## Common Warnings & Fixes

These are recurring analyzer warnings observed during DDI changes and the recommended fixes to include in the module checklist.

- **`whereIn()` usage**: Keep filter application explicit and guarded.

   ```php
   if (! empty($ids)) {
         $query->whereIn('id', $ids);
   }
   ```

   For project-specific analyzer edge cases, follow the existing repository convention in neighboring files.

- **`pluck()` distinct intent**: When deriving a list of parent IDs from a related table, be explicit about uniqueness to avoid SQL ambiguity and unnecessary PHP dedupe:

   ```php
   ->select('province_id')->distinct()->pluck('province_id')
   ```

   Or:

   ```php
   ->whereIn(...)->distinct()->pluck('province_id')->map(fn ($v) => (int) $v)->all()
   ```

- **`orderBy()` direction**: Use explicit direction to satisfy analyzers and make intent clear: `->orderBy('name_in_english', 'asc')`.

- **Normalization contract**: `App\Support\DdiWidgetSupport::normalizeIds()` must:
  - Remove null/empty values
  - Cast entries to `int`
  - Dedupe
  - Sort ascending

   Widgets should call this helper before using IDs in DB queries or in cache keys.

- **Blade views and heavy methods**: If a widget provides large datasets (cards, aggregates), return them via `getViewData()` (or `getViewData()`-style helpers) and avoid invoking heavy methods in the Blade template loop.

- **Quick commands**: Run these locally before pushing changes:

   ```bash
   vendor/bin/pint --dirty --format agent
   php artisan test --compact --filter=YourWidgetTest
   php artisan test --compact
   ```

Add these checks to the PR checklist when touching DDI code.
