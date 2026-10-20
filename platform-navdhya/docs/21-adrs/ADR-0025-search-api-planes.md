# ADR-0025 — Search API planes (public v2 + privileged)

Status: Accepted (S60, S61, 2026-10-18)
Extends: ADR-0024

## Context

v0 Search exposed a single anonymous surface (`/api/v1/search/*`) with a
shared inline `throttle:60,1` (the shared per-IP counter trap, runbook §15)
and no privileged plane. Dashboard/staff had no search over customers,
bookings, vendors, pandit drafts or orders.

## Decision

**S60 — Public plane v2.** `/api/v1/search/*` keeps its anonymous posture
but moves to named rate limiters (`search-public` 120/min, `search-suggest`
300/min, `search-analytics` 60/min). Search ranks with `SearchScorer` (real
scores, consistent keyset cursor), returns `did_you_mean`, `search_version`,
and an `X-Search-Trace` header; `index-docs` gains ETag + `X-Search-Version`
with 304 support for cheap BFF freshness polls. Analytics ingests optional
`trace_id`/`search_version` for forensic replay.

**S61 — Privileged plane.** `/api/v1/search/privileged` (capability
`search:privileged:view`, tenant from `TenantContext` — never the header)
searches customers (PII masked unless `search:pii:view`), bookings, vendors,
pandits and orders scoped to the resolved tenant. Every request writes an
`authz`-gated audit row (`search.privileged`) with a hashed query and
trace id. Dedicated `search-privileged` limiter keyed by IP + actor. No
silent degradation: missing tenant context → 403.

## Consequences

- Public and privileged planes share the scoring/normalizer core but have
  independent limits, auth and audit postures.
- PII projection is capability-driven, not header-driven.
- Privileged plane is human-only (machine principals denied via the
  capability engine).

## Tests

- `SearchModuleTest` (11), `SearchCoreV2Test` (5), `SearchCoreV2GoldenTest` (1),
  `SearchPrivilegedRouteTest` (1). Total Search: 38.