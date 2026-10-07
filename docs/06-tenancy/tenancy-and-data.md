# Tenancy & Data Isolation

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 02 §1–7, §11; kernel event catalog

The tenancy register specifies Navdhya's hybrid multi-tenancy: shared row-level isolation with per-tenant dedicated databases on promotion. It covers the tenancy model, data partitioning, connection model, tenant registry, fail-closed resolution, row-level isolation mechanics, dedicated mode, and the promotion/demotion runbook. Schema conventions, migrations, and data lifecycle live in the data architecture register.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| TEN-001 | [TEN-001](#ten-001-tenancy-model) | Hybrid: shared row-level + dedicated databases |
| TEN-002 | [TEN-002](#ten-002-db-modes--placement) | shared vs dedicated mode per tenant |
| TEN-003 | [TEN-003](#ten-003-data-partitioning) | Central vs tenant data connection split |
| TEN-004 | [TEN-004](#ten-004-connection-model--naming) | central + tenant data connections, naming |
| TEN-005 | [TEN-005](#ten-005-tenant-registry-schema-central) | tenants, memberships, plans tables |
| TEN-006 | [TEN-006](#ten-006-tenant-resolution-fail-closed) | Web + API resolution, deny on unresolved |
| TEN-007 | [TEN-007](#ten-007-tenantcontext-contract) | K-010 kernel contract |
| TEN-008 | [TEN-008](#ten-008-async-scopes-queues-scheduler-listeners) | Context restoration on async paths |
| TEN-009 | [TEN-009](#ten-009-row-level-isolation-mechanics) | BelongsToTenant trait |
| TEN-010 | [TEN-010](#ten-010-index--uniqueness-conventions) | Tenant-scoped uniques and indexes |
| TEN-011 | [TEN-011](#ten-011-prohibited-patterns-ci-enforced) | Banned query/join patterns |
| TEN-012 | [TEN-012](#ten-012-dedicated-mode--connection-resolver) | Resolver + dedicated-mode rules |
| TEN-013 | [TEN-013](#ten-013-tenant-promotion-shared--dedicated) | Freeze/backfill/verify/cutover runbook |
| TEN-014 | [TEN-014](#ten-014-demotion--rollback-window) | 14-day rollback, demote/purge commands |
| TEN-015 | [TEN-015](#ten-015-tenant-lifecycle-states--events) | provisioned/active/suspended/promoted/offboarded |
| TEN-016 | [TEN-016](#ten-016-plans--entitlements-at-the-tenant-layer) | Plan binding on tenant record (control plane owns) |
| TEN-017 | [TEN-017](#ten-017-multi-membership-semantics) | One user, many tenants; active membership |
| TEN-018 | [TEN-018](#ten-018-isolation-verification) | AssertTenantIsolation matrix suite |

---

## TEN-001 Tenancy model

- id: TEN-001
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §1
- depends_on: CON-003
- affects: TEN-002, DELTA-004

### Tenancy Model (Normative)

Navdhya uses **hybrid tenancy**:

- **Default — row-level isolation (db_mode = `shared`).** All tenants share
  one tenant data database; every tenant-scoped table carries a `tenant_id`
  column enforced by kernel global scopes.
- **Promoted — dedicated database (db_mode = `dedicated`).** A tenant may be
  promoted to its own MySQL database (and later its own server) with **no
  application-code changes**; the kernel connection resolver routes all tenant
  data access to the tenant's connection.

Normative rules:

1. Every tenant-scoped table MUST carry `tenant_id CHAR(26)` **in both
   modes** (dedicated mode keeps the column for backfill safety, tooling
   uniformity, and demotion).
2. The **kernel authorization path never depends on the tenant data
   connection**: identity, memberships, orgs, roles, grants, OAuth clients and
   tokens live centrally (see §2) so authentication/authorization stays
   available even if a tenant DB is degraded.
3. Tenant context MUST be resolved before any data access; unresolved context
   fails closed (see §4).
4. No query MAY filter tenant rows manually; isolation comes exclusively from
   kernel-applied scopes (§5).

---

## TEN-002 DB modes & placement

- id: TEN-002
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §1
- depends_on: TEN-001
- affects: TEN-013

Each tenant declares a `db_mode` of `shared` (row-level isolation on the shared tenant DB) or `dedicated` (own database/connection). Placement is recorded on the tenant record and drives the connection resolver. New tenants default to `shared`; promotion to `dedicated` is an explicit, runbook-driven operation (TEN-013). db_mode is control-plane-observable state.

## TEN-003 Data partitioning

- id: TEN-003
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §2
- depends_on: TEN-001
- affects: DATA-007

### Data Partitioning

### 2.1 Central connection (`central`, always)

| Table group | Contents |
|-------------|----------|
| Identity | `users`, `tenant_memberships`, `organizations`, `org_memberships` |
| Authorization | `roles`, `permissions` (catalog), `role_permissions`, `membership_role_assignments`, `sod_rules` |
| Token platform | Passport tables: `oauth_clients`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_auth_codes`, `oauth_personal_access_clients`, plus `jti_revocations`, `device_codes`, `federated_identities`, `identity_provider_configs` |
| Platform registry | `module_registry` cache, `event_catalog`, `feature_flags`, `plans`, `tenant_feature_flags`, `settings` (global scope only) |
| SCIM | `scim_provisioning_jobs`, `scim_sync_state` |

Central tables MUST NOT contain tenant business data. `tenant_id` appears in
central tables only where it scopes platform objects (memberships, orgs,
roles, IdP configs, settings, flags).

### 2.2 Tenant data connection (per tenant's db_mode)

| Table group | Contents |
|-------------|----------|
| Domain modules | All Phase 1–6 module tables (catalog, sales, inventory, finance, ...) |
| Kernel tenant services | `audit_logs`, `outbox`, `workflow_instances`, `approval_requests`, `notifications_log`, `files`, `search_index_*`, `settings` (tenant/org/user scopes), `idempotency_keys` |

**Why audit + outbox live here:** both MUST commit in the same database
transaction as the domain change they record — the transactional outbox
guarantee ([kernel services §3](../12-events/events-and-outbox.md)) and audit consistency depend on it. Therefore they
follow the tenant's data connection (shared DB for row-level tenants, own DB
for promoted tenants).

### 2.3 Connection naming

| Connection | Purpose |
|------------|---------|
| `central` | Central tables above; also the default Laravel connection |
| `tenant_shared` | Shared tenant data database (all `shared` tenants) |
| `tenant_ded_<tenantId>` | Dynamically registered per promoted tenant |

---

## TEN-004 Connection model & naming

- id: TEN-004
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §2.1–2.3
- depends_on: TEN-003
- affects: TEN-013

### Connection model & naming

### 2.1 Central connection (`central`, always)

| Table group | Contents |
|-------------|----------|
| Identity | `users`, `tenant_memberships`, `organizations`, `org_memberships` |
| Authorization | `roles`, `permissions` (catalog), `role_permissions`, `membership_role_assignments`, `sod_rules` |
| Token platform | Passport tables: `oauth_clients`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_auth_codes`, `oauth_personal_access_clients`, plus `jti_revocations`, `device_codes`, `federated_identities`, `identity_provider_configs` |
| Platform registry | `module_registry` cache, `event_catalog`, `feature_flags`, `plans`, `tenant_feature_flags`, `settings` (global scope only) |
| SCIM | `scim_provisioning_jobs`, `scim_sync_state` |

Central tables MUST NOT contain tenant business data. `tenant_id` appears in
central tables only where it scopes platform objects (memberships, orgs,
roles, IdP configs, settings, flags).

### 2.2 Tenant data connection (per tenant's db_mode)

| Table group | Contents |
|-------------|----------|
| Domain modules | All Phase 1–6 module tables (catalog, sales, inventory, finance, ...) |
| Kernel tenant services | `audit_logs`, `outbox`, `workflow_instances`, `approval_requests`, `notifications_log`, `files`, `search_index_*`, `settings` (tenant/org/user scopes), `idempotency_keys` |

**Why audit + outbox live here:** both MUST commit in the same database
transaction as the domain change they record — the transactional outbox
guarantee ([kernel services §3](../12-events/events-and-outbox.md)) and audit consistency depend on it. Therefore they
follow the tenant's data connection (shared DB for row-level tenants, own DB
for promoted tenants).

### 2.3 Connection naming

| Connection | Purpose |
|------------|---------|
| `central` | Central tables above; also the default Laravel connection |
| `tenant_shared` | Shared tenant data database (all `shared` tenants) |
| `tenant_ded_<tenantId>` | Dynamically registered per promoted tenant |

---

## TEN-005 Tenant registry schema (central)

- id: TEN-005
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §3
- depends_on: TEN-001
- affects: CTRL-002, TEN-016

### Tenant Registry Schema (central)

`tenants` table:

| Column | Type | Notes |
|--------|------|-------|
| `id` | `CHAR(26)` ULID | PK |
| `name` | `VARCHAR(190)` | Display name |
| `slug` | `VARCHAR(63)` UNIQUE | URL/API identifier |
| `status` | `VARCHAR(20)` | `provisioning, active, suspended, offboarding, offboarded` (kernel state machine) |
| `plan_code` | `VARCHAR(63)` | FK-by-value to `plans.code` |
| `db_mode` | `VARCHAR(10)` | `shared` or `dedicated` |
| `db_connection_name` | `VARCHAR(63)` NULL | Set when dedicated |
| `db_config_ciphertext` | `TEXT` NULL | Encrypted dedicated connection config (host, port, database, user) — key via app key, vault path per [security baseline §3](../04-security/security-baseline.md) |
| `base_currency` | `CHAR(3)` | ISO-4217; immutable after first posting ([finance domains](../14-domains/finance/gl.md)) |
| `timezone` | `VARCHAR(63)` | IANA zone; display only — storage is UTC |
| `locale` | `VARCHAR(10)` | Default locale |
| `created_at/updated_at` | `DATETIME` UTC | || `deleted_at` | `DATETIME` NULL | Soft delete (offboarding retains row) |

Kernel events (consolidated kernel event catalog, [kernel services §2.4](../12-events/events-and-outbox.md)): `platform.tenant.provisioned`,
`platform.tenant.activated`, `platform.tenant.suspended`,
`platform.tenant.promoted`, `platform.tenant.offboarded`.

`plans` defines plan code, name, feature-flag bundle, and limits (max users,
max orgs, API rate tier) consumed by FeatureFlags and rate limiting.

**Amendment A-TEN-005-1 (C2, 2026-10-01):** `tenants` gains `slug UNIQUE`,
`status`, `plan_code`, `db_connection_name`, `db_config_ciphertext`,
`base_currency`, `timezone`, `locale`, `deleted_at`; new central tables
`plans`, `organizations`, `org_memberships`. Legacy `code`/`state` stay
readable during the dual-read window; `tenants:backfill-v2` copies
`code`→`slug`, `state`→`status` (dry-run, idempotent, collision-abort).
Removal of legacy columns lands in a later release after parity is
confirmed. Migration: `2026_10_01_000001_tenant_registry_v2_expand`.

---

## TEN-006 Tenant resolution (fail closed)

- id: TEN-006
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §4
- depends_on: TEN-005, CON-003
- affects: ARCH-004, SEC-022

### Tenant Resolution (Fail Closed)

### 4.1 `TenantContext`

`Platform\Tenancy\TenantContext` is a request/job-scoped singleton holding:
`tenant`, `membership`, `org`, `connectionName`, `actor`. It is set exactly
once per scope by resolution middleware and cleared on termination.

### 4.2 Web (session) resolution

1. `Authenticate` resolves the user.
2. `ResolveTenantFromMembership`:
   - If the request targets a tenant (tenant switcher action), validate the
     user holds an **active membership** in that tenant, store
     `active_membership_id` in the session, redirect back.
   - Otherwise load `active_membership_id` from session; if absent or the
     membership is no longer active → redirect to tenant chooser (single
     membership → auto-activate).
3. Suspended/offboarded tenants → 403 with a kernel error code, session's
   active membership cleared.

### 4.3 API (token) resolution

1. `AuthenticateToken` validates the JWT ([identity & token platform](../04-security/identity-and-token-platform.md)) and extracts claims
   including `tenant_ids` (user tokens) or `tenant_id` (client-credentials
   tokens, single-bound).
2. `ResolveTenantFromToken`:
   - `X-Tenant-Id` header MUST be present on every tenant-scoped request;
     the value MUST be in the token's tenant claim set → else 401
     (`tenant_not_in_token`).
   - Missing header on a tenant-scoped route → 400 (`tenant_header_missing`).
   - Platform-global routes (e.g. `/api/v1/platform/tenants` for the
     authenticated user's memberships) are exempt and marked in the route
     registry.; the console's `/account/workspaces` page renders exactly this payload (every membership state, so denials are explainable)
3. The tenant's `status` and the membership's `status` MUST both be active.

### 4.4 Asynchronous scopes (queues, scheduler, listeners)

- Jobs MUST implement `TenantAware` (kernel trait): `tenant_id` +
  `connection_name` are serialized into the job payload at dispatch time;
  the queue middleware re-establishes `TenantContext` before `handle()` and
  verifies the tenant still exists and is active.
- Event listeners receive tenant context from the `DomainEvent` envelope.
- Scheduler commands that touch tenant data MUST iterate tenants explicitly
  via `TenantRegistry::active()` and scope each run.
- **Fail-closed invariant:** any attempt to write a tenant-scoped model while
  `TenantContext` is unresolved MUST throw `TenantContextMissingException`
  (never a silent write with NULL tenant).

---

## TEN-007 TenantContext contract

- id: TEN-007
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §4.1; K-010
- depends_on: TEN-006
- affects: K-010

### TenantContext (K-010)

**Amendment A-TEN-007-1 (C1, 2026-10-01):** context additionally carries
`connectionName` and `actor`; `set()` throws on a second call without an
intervening `clear()` (scope transitions are explicit clear-then-set;
test helpers were migrated to the same discipline).

### 4.1 `TenantContext`

`Platform\Tenancy\TenantContext` is a request/job-scoped singleton holding:
`tenant`, `membership`, `org`, `connectionName`, `actor`. It is set exactly
once per scope by resolution middleware and cleared on termination.

## TEN-008 Async scopes (queues, scheduler, listeners)

- id: TEN-008
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §4.4
- depends_on: TEN-006
- affects: DELTA-008

### Async scopes

### 4.4 Asynchronous scopes (queues, scheduler, listeners)

- Jobs MUST implement `TenantAware` (kernel trait): `tenant_id` +
  `connection_name` are serialized into the job payload at dispatch time;
  the queue middleware re-establishes `TenantContext` before `handle()` and
  verifies the tenant still exists and is active.
- Event listeners receive tenant context from the `DomainEvent` envelope.
- Scheduler commands that touch tenant data MUST iterate tenants explicitly
  via `TenantRegistry::active()` and scope each run.
- **Fail-closed invariant:** any attempt to write a tenant-scoped model while
  `TenantContext` is unresolved MUST throw `TenantContextMissingException`
  (never a silent write with NULL tenant).

---

## TEN-009 Row-level isolation mechanics

- id: TEN-009
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §5.1
- depends_on: TEN-006
- affects: DELTA-004

### BelongsToTenant mechanics

### 5.1 `BelongsToTenant` trait (kernel)

Applied to every tenant-scoped model:

- **creating**: fills `tenant_id` from `TenantContext` (throws if missing;
  overwriting an already-set value is a programming error and throws).
- **global scope**: `WHERE tenant_id = ?` bound from context on every read,
  update, delete — including relations and soft deletes.
- **force bypass** exists only as `TenantScope::without()` for kernel
  maintenance commands (backfill, promotion tooling); every bypass call site
  is architecture-tested and logged to the audit trail.

## TEN-010 Index & uniqueness conventions

- id: TEN-010
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §5.2
- depends_on: TEN-009
- affects: DATA-004

### Index & uniqueness conventions

### 5.2 Index and uniqueness conventions

- Every secondary index on a tenant table MUST be prefixed with `tenant_id`
  (composite), except full-text indexes.
- Every UNIQUE constraint on business keys MUST include `tenant_id`
  (e.g. `UNIQUE (tenant_id, sku)`).
- PKs are ULIDs (globally unique), so PK lookups still require the tenant
  scope — the global scope provides it.

## TEN-011 Prohibited patterns (CI-enforced)

- id: TEN-011
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §5.3
- depends_on: TEN-009
- affects: ARCH-018

### Prohibited patterns

### 5.3 Prohibited patterns (CI-enforced, [testing strategy](../19-testing/testing-strategy.md))

- Raw queries or query-builder calls on tenant tables outside model scopes
  without the kernel tenant binding.
- `tenant_id` comparisons written by hand in module code.
- Cross-tenant reporting queries in module code (analytics goes through the
  Phase 6 read path, [intelligence](../17-ai/intelligence.md), which uses an explicitly authorized connection).

---

## TEN-012 Dedicated mode & connection resolver

- id: TEN-012
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §6
- depends_on: TEN-002
- affects: TEN-013

### Dedicated Mode & Connection Resolver

### 6.1 Resolver

`Platform\Tenancy\ConnectionResolver` (implemented; amendment A-TEN-012-1,
C1, 2026-10-01):

1. Given a tenant, reads `db_mode` from the central registry (cached, TTL
   60 s, bust on `platform.tenant.promoted`).
2. `shared` → returns `tenant_shared`.
3. `dedicated` → decrypts `db_config_ciphertext`, registers a runtime
   connection `tenant_ded_<tenantId>` via Laravel config, returns it.
4. Models on `BelongsToTenant` resolve their connection from
   `TenantContext->connectionName` (not a static `$connection` property).

### 6.2 Rules for dedicated mode

- Schema is identical to shared mode (same migrations, §8).
- `tenant_id` columns and scopes remain active.
- No joins across connections; kernel services that need central + tenant
  data do so via two queries and in-memory assembly.
- Backups, monitoring and upgrade windows are per-tenant; the runbook in §7
  covers cutover.

---

## TEN-013 Tenant promotion (shared → dedicated)

- id: TEN-013
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §7
- depends_on: TEN-012
- affects: 22-runbooks/tenant-promotion

### Tenant Promotion Runbook (shared → dedicated)

**Preconditions:** tenant active; target MySQL instance provisioned with the
same schema version as `tenant_shared`; maintenance window scheduled.

*Implementation note (amendment A-TEN-013-1, C1, 2026-10-01): the runbook
is software — `tenant:promote [--dry-run]`, `tenant:demote`,
`tenant:purge-shared` implement freeze/backfill/verify/cutover/smoke
with per-chunk checksums; table scope is discovered (any table carrying
`tenant_id`), never hardcoded.*

1. **Freeze** — set tenant status `promoting` (writes rejected with 503 +
   `Retry-After`; reads continue from shared DB).
2. **Backfill** — kernel command `tenant:promote --tenant=<id>` copies the
   tenant's rows table-by-table in chunks (5k rows), checksumming each chunk
   (row count + CRC32 aggregate) into the dedicated DB.
3. **Verify** — per-table checksum comparison; any mismatch aborts (tenant
   returns to `active`, no cutover).
4. **Cutover** — single central transaction: set `db_mode=dedicated`,
   `db_connection_name`, store encrypted config, bump registry cache, emit
   `platform.tenant.promoted`.
5. **Smoke** — automated read/write smoke suite against the tenant; on
   failure, flip `db_mode` back (rows remain authoritative in shared DB until
   step 6).
6. **Rollback window** — shared-DB rows are retained for **14 days**;
   `tenant:demote --tenant=<id> [--from-backup]` reverses the cutover. After
   the window, `tenant:purge-shared --tenant=<id>` deletes the tenant's
   shared rows (audited).

Demotion (dedicated → shared) uses the same runbook in reverse with an
exclusive maintenance window.

---

## TEN-014 Demotion & rollback window

- id: TEN-014
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §7
- depends_on: TEN-013
- affects: 22-runbooks/tenant-promotion

Demotion (dedicated → shared) runs the promotion runbook in reverse with an exclusive maintenance window. Shared-DB rows are retained for 14 days after cutover as the rollback window; `tenant:demote` reverses the cutover inside that window, and `tenant:purge-shared` deletes the tenant's shared rows after it (audited). The full operator procedure lives in the tenant-promotion runbook.

## TEN-015 Tenant lifecycle states & events

- id: TEN-015
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §3; kernel event catalog
- depends_on: TEN-005
- affects: EV-013, CTRL-010

A tenant moves through normative lifecycle states: `provisioned → active ⇄ suspended → offboarded`, with `promoting` as a transient state during promotion. Each transition emits a `platform.tenant.*` kernel event (registered in the events register) and is audited. Suspended tenants fail closed on all surfaces; offboarding triggers export then scheduled erasure per the data architecture.

**Pre-era adoption (amendment A-TEN-015-1, C1, 2026-10-01):**
`tenant:adopt-legacy --owner= [--slug=] [--dry-run] [--i-have-a-backup]`
provisions a `legacy` tenant for pre-tenancy datasets, binds the owner,
and attributes every NULL-`tenant_id` row to it (dry-run reporting,
backup refusal without the flag, slug-collision refusal, zero-NULL
verification). Adopted rows are attributable by their legacy tenant —
never delete history to simulate greenfield.

## TEN-016 Plans & entitlements at the tenant layer

- id: TEN-016
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §3 plans
- depends_on: TEN-005
- affects: CTRL-002

The tenant record carries its plan binding and db_mode; plan contents, entitlement resolution, and blueprint materialization are owned by the control plane. Entitlement checks run inside tenant resolution so an unlicensed module is denied in the same fail-closed path. See the control plane register (CTRL-002, CTRL-015).

## TEN-017 Multi-membership semantics

- id: TEN-017
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §1; doc 03 §1
- depends_on: TEN-005, SEC-001
- affects: EXP-014

A user may hold memberships in multiple tenants. Exactly one membership is active per session/request; the active membership selects the tenant context and the org node. Switching tenant resets the working context (it is tenant-scoped) and is audited. Membership state (active/suspended/expired) is a security constraint in the authorization chain.

## TEN-018 Isolation verification

- id: TEN-018
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §11
- depends_on: TEN-009
- affects: 19-testing

### Verification Requirements

- `AssertTenantIsolation` kernel test trait: seeds two tenants, runs a CRUD
  matrix per model, asserts zero cross-tenant visibility including relations,
  soft-delete restore, and raw query paths.
- Standing leakage suite (`tests/TenantIsolation/`) runs on every CI build
  against MySQL 8 ([testing strategy §5](../19-testing/testing-strategy.md)).
- Promotion runbook steps 2–5 are implemented as kernel commands with dry-run
  mode and are themselves feature-tested.

---
