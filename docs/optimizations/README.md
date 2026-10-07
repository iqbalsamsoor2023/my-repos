# Optimization Docs Hub

Central index for module optimization and reference docs.

## Module Summaries

- [Centralized Module Engineering Guidelines](./CENTRALIZED_MODULE_ENGINEERING_GUIDELINES.md)
- [Residence Module Reference](./RESIDENCE_OPTIMIZATION_SUMMARY.md)
- [Unit Users Optimization Summary](./UNIT_USERS_OPTIMIZATION_SUMMARY.md)
- [Units Optimization Summary](./UNITS_OPTIMIZATION_SUMMARY.md)
- [VMS (Visitor Management) Module Reference](./VMS_OPTIMIZATION_SUMMARY.md)
- [District Dashboard (DDI) Module Reference](./DISTRICT_DASHBOARD_MODULE_REFERENCE.md)
- [VBS (Visitor by Site) Module Reference](./VBS_OPTIMIZATION_SUMMARY.md)
- [Vehicle Dashboard Optimization Summary](./VEHICLE_OPTIMIZATION_SUMMARY.md)

## Archive Docs

- [Archive Quick Reference](../archive/ARCHIVE_README.md)
- [Archive Command Guide](../archive/ARCHIVE_COMMAND_GUIDE.md)

## How to Create a New Module Doc

Each doc must be a **current-state module reference** — not a changelog or history. It tells anyone what a page/module does so that future enhancements or other modules can follow the same patterns.

### Required Sections (in order)

1. **Purpose** — What the module does, who uses it, what role access is required, page route/navigation path.
2. **Pages** — Table of all Filament pages/routes in the module and what each does.
3. **Widgets** (if applicable) — Render-order table listing each widget and its role.
4. **Data Sources** — Table of tables/views/services the module reads from and what each provides.
5. **Key Classes** — Table of the main PHP classes (resources, services, helpers, traits) and where they live.
6. **Data Flow** — ASCII diagram showing how data moves from write tables → read models → UI.
7. **Scheduling & Queue** — Any artisan commands, jobs, or queues. Include the exact command to run.
8. **Event Flow** (if applicable) — Livewire/Filament events emitted and who listens to them.
9. **UI Behavior Notes** — Pagination defaults, filter behavior, sort defaults, polling, column toggles, anything non-obvious.
10. **Reusable Components** — Shared helpers/traits/services with method signatures and which other modules use them.
11. **Implementation Notes for Future Enhancements** — Constraints, gotchas, and guidance for the next developer adding to this module.

### Rules

- **Current state only.** Do not include change history, phase logs, or "before/after" comparisons.
- **No changelogs.** If a previous version had something different, only document the current behavior.
- Keep each section focused: one table or a short paragraph. Avoid long prose.
- Update the index above whenever a new module doc is added.

## Standard Placement

Keep canonical module docs in this folder.
