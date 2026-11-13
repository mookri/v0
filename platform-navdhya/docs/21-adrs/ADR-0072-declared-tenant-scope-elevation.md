# ADR-0072: Declared Tenant-Scope Elevation Replacing Ad-Hoc Bypass

## Status
Accepted (2026-11-11)

## Context
88 ad-hoc `withoutGlobalScopes()` calls across `app/`, including 15 inside HTTP controllers, obscured legitimate cross-tenant / public-read elevations (`A-10`).

## Decision
Introduce `PlatformScope::run(string $reason, \Closure $callback, ?string $tenantId = null)` with an audited `finally`-restored context and mandatory reason string. Remove all `withoutGlobalScopes()` calls from `app/**/Http/Controllers/` (0 remaining), document remaining service exits in `docs/06-tenancy/scope-exit-inventory.md`, and enforce a monotonic budget (`<= 54`) via `ScopeExitBudgetTest`.

