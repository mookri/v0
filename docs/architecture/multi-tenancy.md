# Multi-Tenancy Trajectory (§50)

Tenancy lives exactly where it genuinely exists — nowhere else gets a
tenant field. This is the complete list; additions require an ADR.

## Where tenancy lives

1. **Token claims**: `RequestActor.market?` / `orgId?` (default market
   `'in'` for legacy/dev tokens). Minted at login from the identity's
   home market/org.
2. **Enforcement**: `assertSameTenant(actor, resource)` — the only allowed
   compare. Cross-market throws `TENANT_MISMATCH`; org narrowing throws
   `TENANT_ORG_MISMATCH`. Enforcement points call this, never inline
   compares (future arch gate).
3. **Repository scope**: tenant-scoped stores take scope parameters
   (`listByStates` grows a scope argument when the second tenant lands);
   `listAll`-shaped methods are banned on tenant-scoped stores.
4. **Config scope**: flag evaluation context gains `market`/`orgId`
   (control-plane L2 shape reserved); track versions resolve per market
   (`pandit-v1.in` fallback to `pandit-v1`).

## Explicitly not tenants

- Applicants/customers (users, not tenants — they *belong to* a market).
- Events/analytics (annotated at projection time from the actor claim).
- Individual applications, bookings, payments (scoped by ownership +
  market, not by a tenant id on every row).

## Roles

Tenant-scoped reviewer roles (`market-reviewer` vs `platform-admin`) are
the v2 access model; v1 ships platform roles with the enforcement helper
ready. Admin role changes go through the admin-ops runbook.

## Status

Claims + helper + login passthrough done; enforced on review queue,
review decisions, and all six applicant routes (shared `loadOwnApplication`
guard: auth + ownership + tenant). Next surfaces: customer BFF routes.
