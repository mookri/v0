# Authorization & Policy

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 04 §1–§11; pasted authz analysis (capability model)

The authorization register specifies Navdhya's policy-driven authorization: RBAC roles and grants, the ABAC policy layer, effective authority, scope mapping, segregation of duties, and route/kernel permission semantics (SEC-005…013). The capability-model upgrade — parsed capability metadata, first-class policies, explicit DENY precedence, delegation, and the authorization decision log with AI-agent safety — is recorded as SEC-020…024 (newly authored, APPROVED). Authority is a hierarchy node plus scope mode; see the hierarchy register.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| SEC-005 | [SEC-005](#sec-005-model-overview) | RBAC + ABAC + SoD overview |
| SEC-006 | [SEC-006](#sec-006-permission-catalog--registry) | Capability catalog, registry API |
| SEC-007 | [SEC-007](#sec-007-roles--grants) | Role profiles, authority grants |
| SEC-008 | [SEC-008](#sec-008-abac-policy-layer-org-scope) | Attribute conditions, org-scope resolution |
| SEC-009 | [SEC-009](#sec-009-effective-authority) | scopes ∩ grants ∩ membership |
| SEC-010 | [SEC-010](#sec-010-scopes--permissions-mapping) | OAuth scope to capability mapping |
| SEC-011 | [SEC-011](#sec-011-segregation-of-duties) | SoD conflict rules |
| SEC-012 | [SEC-012](#sec-012-route-authorization-requirements) | Every route declares can: or public marker |
| SEC-013 | [SEC-013](#sec-013-kernel-permission-semantics) | Platform-operation permission semantics |

---

## SEC-005 Model overview

- id: SEC-005
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §1
- depends_on: CON-003, K-001
- affects: SEC-006

### Model Overview

```text
                    ┌─────────────────────────────┐
                    │      PERMISSION CATALOG      │  (from module manifests)
                    │  <module>:<resource>:<action>│
                    └──────────────┬──────────────┘
                                   │ assigned to
                    ┌──────────────▼──────────────┐
                    │            ROLES             │  (tenant-scoped)
                    └──────────────┬──────────────┘
                                   │ assigned to
                    ┌──────────────▼──────────────┐
                    │        MEMBERSHIPS           │  (user × tenant)
                    └──────────────┬──────────────┘
                                   │ evaluated with
        ┌──────────────────────────▼──────────────────────────┐
        │                AUTHORIZATION DECISION                │
        │  permission grant ∩ ABAC conditions ∩ token scope    │
        │  ∩ tenant membership ∩ org scope ∩ SoD clearance     │
        └──────────────────────────────────────────────────────┘
```

Design choices:
- **Roles are tenant-scoped**, never global. There is no "super admin role"
  inside tenant data; platform operators act through kernel admin routes with
  their own platform permission set ([security baseline §4](./security-baseline.md)).
- **Grants attach to memberships**, so a user can be admin in tenant A and
  viewer in tenant B with zero shared state.
- **ABAC conditions are policy-level**, not role-level: roles grant
  permissions; policies constrain when/where they apply.

---

## SEC-006 Permission catalog & registry

- id: SEC-006
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §2
- depends_on: SEC-005, K-004
- affects: SEC-020

### Permission Catalog & Registry

### 2.1 Catalog

Permissions are declared exclusively in module manifests ([domain framework §3.2](../09-domain-framework/domain-framework.md)):

```json
{ "code": "sales:order:approve", "name": "Approve sales orders", "sensitive": true }
```

At boot/deploy, the kernel command `platform:sync-permissions`:

1. Collects every manifest permission.
2. Upserts the central `permissions` table (catalog):
   `id ULID`, `code UNIQUE`, `name`, `module_code`, `sensitive`, `active`.
3. Marks catalog rows no longer declared as `active=0` (retained for audit
   history; assignments blocked).
4. Validates naming: `<module_code>:<resource>:<action>` with resource/action
   in `[a-z0-9_]+`; action from the closed vocabulary ([domain framework §3.3](../09-domain-framework/domain-framework.md)).
5. Emits `platform.permissions.synced` (audit) with the diff summary.

The kernel namespace `platform` holds kernel permissions (same catalog):
`platform:tenant:manage`, `platform:plan:configure`, `platform:user:invite`,
`platform:membership:manage`, `platform:org:manage`, `platform:role:manage`,
`platform:role:assign` (sensitive), `platform:client:create` (sensitive),
`platform:client:rotate_secret` (sensitive), `platform:flags:configure`,
`platform:audit:view`, `platform:scim:configure`, `platform:idp:configure`
(sensitive), `platform:settings:manage`, `platform:tenant:promote` (sensitive),
`platform:hooks:configure` (sensitive), `platform:hooks:view` ([ecosystem §1](../18-extensions/ecosystem.md)).

The kernel additionally declares permissions under the `kernel` namespace for
self-service and machine surfaces (same catalog, `module_code = kernel`):
`kernel:profile:read`, `kernel:profile:manage`, `kernel:mfa:manage`
`kernel:sessions:manage`, `kernel:tenant:list` — granted to every interactive user by default
and not revocable via roles; `kernel:webhooks:manage` (sensitive; [API platform §7](../15-integrations/integration-framework.md)),
`kernel:hooks:manage` (sensitive; [ecosystem §1](../18-extensions/ecosystem.md)), `kernel:notifications:send`
(sensitive; copilot/agent tooling, [intelligence §5](../17-ai/intelligence.md)) — admin-grantable;
`kernel:scim:write` (client-credentials only, tenant-bound; [identity & token platform §10](./identity-and-token-platform.md)).
Every `kernel:` permission has an identically named scope (§6).

The platform-operator console ([security baseline §4](./security-baseline.md)) uses a separate `operator:`
namespace (e.g. `operator:tenant:provision`, `operator:partners:scope_exception`,
`operator:break_glass`). Operator permissions are granted only to platform
operators (never to tenant memberships), are not issued as OAuth scopes, and
are excluded from role assignment and SoD evaluation.

### 2.2 Registry API

`Platform\Authorization\PermissionRegistry` provides:

| Method | Purpose |
|--------|---------|
| `all()`, `forModule(code)` | Catalog queries (cached) |
| `exists(code)` | Manifest/route validation |
| `isSensitive(code)` | Step-up gating ([identity & token platform §6](./identity-and-token-platform.md)) |
| `expandScope(scope)` | Scope → permission set expansion (§6) |

---

## SEC-007 Roles & grants

- id: SEC-007
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §3
- depends_on: SEC-006, K-003
- affects: SEC-009

### Roles & Grants

### 3.1 Schema (central connection)

`roles`: `id ULID`, `tenant_id`, `code`, `name`, `description`,
`kind` (`builtin|custom`), `status`, timestamps, soft delete.
`UNIQUE (tenant_id, code)`.

`role_permissions`: `role_id`, `permission_id`, `granted_at`.
`UNIQUE (role_id, permission_id)`.

`membership_role_assignments`: `id ULID`, `membership_id`, `role_id`,
`assigned_by`, `assigned_at`, `expires_at NULL` (optional time-boxed
grants). Expired assignments are ignored by evaluation and cleaned nightly.

### 3.2 Built-in roles (seeded per tenant on provisioning)

| Code | Intent | Seed content |
|------|--------|--------------|
| `owner` | Tenant owner; full control incl. billing/tenant settings | all current module permissions + platform:* within tenant scope |
| `admin` | Tenant administrator | all current module permissions except `platform:tenant:*` |
| `manager` | Department manager | view/list/create/update/approve across commercial modules, no configure/delete |
| `staff` | Day-to-day operator | view/list/create/update on assigned modules, no approve/configure/delete |
| `viewer` | Read-only | `*:view`/`*:list` only |

Built-in roles are `kind=builtin`: their permission sets are maintained by
the kernel (re-synced when modules install) and **cannot be edited**; tenants
create `custom` roles for anything else. The `owner` role is protected:
assignment requires an existing owner or platform operator action with step-up.

### 3.3 Grant lifecycle

- Assignment: `platform:role:assign` (sensitive → ACR 2); SoD check (§7);
  event `platform.role.assigned`.
- Revocation: immediate; membership's cached permission set invalidated;
  refresh-token lineage for the tenant stays valid but the live scope
  re-check (§5.3) strips removed permissions on the next request; event
  `platform.role.revoked`.
- Time-boxed grants: `expires_at` set → surfaced in admin UI with remaining
  time; expiry is automatic and audited.

---

## SEC-008 ABAC policy layer (org scope)

- id: SEC-008
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §4
- depends_on: SEC-007, HIER-004
- affects: SEC-009

### ABAC Policy Layer

### 4.1 Policy shape

Each module ships Eloquent policies registered via its provider
([domain framework §3.1](../09-domain-framework/domain-framework.md)). A policy method composes:

1. **Permission gate**: `$membership->can('<module>:<resource>:<action>')`.
2. **Org-scope condition**: the record's `org_id` must be within the actor's
   org subtree (primary org + `org_assignments`), unless the permission was
   granted with tenant-wide scope (§4.2).
3. **Ownership condition**: where declared, `created_by == actor` for
   `update`/`delete` on own-record variants (e.g. expenses, timesheets).
4. **Record-state condition**: state machines restrict verbs by state (e.g. a
   confirmed order cannot be edited, only amended via workflow; [kernel services §4](../11-workflow/workflow-engine.md)).
5. **SoD clearance**: sensitive actions re-checked at decision time (§7.3).

### 4.2 Org-scope resolution

- Every tenant-scoped business record carries `org_id` (the org that owns it)
  in addition to `tenant_id` — set from `TenantContext->org` at creation,
  overridable only when the actor can act in both source and target orgs.
- Org visibility = primary org subtree ∪ additional org assignments.
  Subtree computed from `organizations.path` (`LIKE '<path>/%'` with index).
- A membership may be **tenant-wide** (`org_assignments` contains the root
  org), which collapses the org condition.
- Org reassignment of records (`<module>:<resource>:reassign` permission)
  writes before/after org in audit.

### 4.3 Policy rules

- Policies MUST NOT query another module's tables; conditions use local
  columns + contract lookups ([domain framework §5](../02-architecture/architecture-master.md)).
- Policy decisions are pure functions of (membership, record, ability); no
  network calls at decision time except cached contract reads.
- Denial reasons are stable codes (`permission_missing`, `org_out_of_scope`,
  `state_forbidden`, `sod_conflict`) returned in the 403 envelope
  ([API platform §4](../07-kernel/api-platform.md)) — never raw SQL or stack detail.

---

## SEC-009 Effective authority

- id: SEC-009
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §5
- depends_on: SEC-007, SEC-008
- affects: SEC-022

### Effective Authority (Normative)

The single rule every request path MUST implement:

> **effective authority = token scopes ∩ user permission grants ∩ active
> tenant membership**, further constrained by ABAC conditions (§4) and SoD
> clearance (§7) at decision time.

### 5.1 Evaluation order (fail fast)

1. **Token validity** (signature, exp, aud, jti) → 401 on failure.
2. **Tenant membership**: `X-Tenant-Id` present in token tenant set AND
   membership active → 401 `tenant_not_in_token` / 403 `membership_inactive`.
3. **Permission grant**: membership's cached permission set contains the
   route's required permission → 403 `permission_missing`.
4. **Token scope**: the granted effective scope set contains the route's
   required scope → 403 `insufficient_scope` (RFC 6750 style, envelope code).
5. **ABAC policy** (§4) → 403 with specific reason code.
6. **SoD runtime check** (§7.3) for sensitive actions → 403 `sod_conflict`.
7. **Step-up/ACR** requirement already enforced by middleware ([identity & token platform §6](./identity-and-token-platform.md)).

### 5.2 Why scopes and grants both matter

- **Scopes** cap what a *client/session* may do (a mobile app issued with
  `sales:read` cannot write even if the user is an admin).
- **Grants** define what a *user* may do in a tenant.
- Intersection means a stolen limited-scope token never escalates, and a
  broad-scope token never bypasses RBAC.

### 5.3 Live re-check vs caching

- Permission sets per membership are cached in Redis (TTL 5 min, write-through
  invalidation on any role/grant/membership change event).
- Membership **status** is never cached beyond the request: suspension or
  revocation must deny immediately.
- Token claims (`tenant_ids`, `scope`) are issuance-time snapshots; the live
  intersection above is authoritative.

---

## SEC-010 Scopes ↔ permissions mapping

- id: SEC-010
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §6
- depends_on: SEC-003, SEC-006
- affects: SEC-009

### Scopes ↔ Permissions Mapping

Kernel scope model (mirrors [identity & token platform §5](./identity-and-token-platform.md)):

| Scope form | Expansion |
|------------|-----------|
| `<module>:read` | all `<module>:*:view` + `<module>:*:list` permissions |
| `<module>:write` | all `<module>:*:create`/`update`/`delete` permissions |
| `<module>:<resource>:<action>` | exactly that permission |
| `kernel:*` scopes | kernel permission equivalents (§2.1 list) |

Rules:
1. `expandScope()` is materialized from the permission catalog at sync time
   into `scope_expansions` (scope string → permission id set).
2. Interactive tokens are issued with the user's requestable scopes capped by
   the client profile; the issued `scope` claim is the effective set.
3. Client-credentials tokens: scopes from `allowed_scopes` only; any
   permission with `sensitive=true` is excluded from their expansion
   (architecture test).
4. Web session (Blade admin) is treated as a full-authority interactive
   client: its "scope set" equals all grants; authorization still flows
   through the same evaluation order so web and API behavior is identical.
5. Manifests declare non-trivial scopes ([domain framework §3.2](../09-domain-framework/domain-framework.md)); `read`/`write` are
   implicit and need not be listed.

---

## SEC-011 Segregation of duties

- id: SEC-011
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §7
- depends_on: SEC-007
- affects: SEC-022, SEC-023

### Segregation of Duties (SoD)

### 7.1 Rule model

`sod_rules` (central): `id ULID`, `tenant_id NULL` (NULL = platform default,
tenant-overridable), `code`, `name`, `kind` (`conflict|approval_chain`),
`role_set JSON`, `permission_set JSON`, `severity` (`block|warn`),
`status`.

Two rule kinds:

- **conflict**: a single membership MUST NOT hold all of the listed roles or
  permissions simultaneously.
  Platform defaults shipped in seeders:
  | Code | Conflict | Severity |
  |------|----------|----------|
  | `sod.po_vendor_bill` | `purchasing:order:approve` + `invoicing:bill:approve` | block |
  | `sod.vendor_master_payments` | `suppliers:supplier:create`/`update` + `payments:payout:approve` | block |
  | `sod.payroll_run_approve` | `payroll:run:create` + `payroll:run:approve` | block |
  | `sod.journal_post_approve` | `gl:journal:create` + `gl:journal:approve` | block |
  | `sod.role_grant_self` | granting oneself a role | block |
  Tenants MAY add custom rules or downgrade a platform rule to `warn`
  (never remove a block without platform operator consent; audit-logged).

- **approval_chain**: sensitive operations require approval by a *different*
  membership satisfying a role/permission predicate (feeds the workflow
  approval engine, [kernel services §4](../11-workflow/workflow-engine.md)): e.g. payments above threshold require an
  approver with `payments:payout:approve` who is not the payment creator.

### 7.2 Assignment-time enforcement

On every `platform:role:assign` / SCIM group→role sync:

1. Compute the resulting permission set.
2. Evaluate all active `conflict` rules for the membership.
3. `block` → assignment rejected with `sod_conflict` + rule code in the
   error details; `warn` → assignment proceeds, emits
   `platform.sod.warning` (security alert surface).
4. Every evaluation result is audit-logged.

### 7.3 Runtime enforcement

Permission grants alone are insufficient for sensitive actions; at decision
time (§5.1 step 6) the kernel re-evaluates conflict rules involving the
specific permission being exercised (guards against rules added after
assignment and cross-assignment combinations). Approval-chain rules are
enforced by the workflow engine: the approver MUST differ from the submitter
and satisfy the predicate; self-approval returns `sod_conflict`.

### 7.4 SoD event catalog

`platform.sod.rule_created`, `platform.sod.rule_updated`,
`platform.sod.conflict_blocked`, `platform.sod.warning`.

### Implementation note A-SEC-011-1 — assignment-time live (2026-10-01)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- `sod_rules` (central, NULL tenant = platform default) + `SodEvaluator`
  (conflict kind only) + grant-time hook in `RoleController::grant`:
  blocking rules → 403 `sod_conflict` with `details.rules`; warn rules →
  grant proceeds + `platform.sod.warning`; `sod.role_grant_self` enforced
  by issuer/target identity comparison at the same effective severity;
  idempotent re-grants skip evaluation (no state change); every outcome
  audit-logged as `sod.evaluated`. Platform defaults seedable via
  `SodRuleSeeder` (`db:seed --class=SodRuleSeeder`).
- Tenant rows override platform rows by code (downgrade block→warn works
  today); tenant rule-admin CRUD with operator-consent checks is NOT built
  yet, so seeds + factories are the only writers.
- Explicitly pending: runtime re-evaluation in the policy engine (§7.3),
  `approval_chain` enforcement in the workflow engine, `rule_created` /
  `rule_updated` events (emitted once rule-admin CRUD exists).

### Implementation note A-SEC-011-2 — runtime enforcement live (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive; A-SEC-011-1 still
describes assignment-time)

- New `DecidingStage::sod_conflict` sits between allow-grant and the
  allow: a granted capability is re-checked against blocking conflict
  rules involving the exercised permission (role-only rules stay
  assignment-time business; warn trips stay allowed — assignment time
  owned the warning).
- Denials throw `SodConflictException` (extends the now-subclassable
  `AuthorizationDeniedException`) and render 403 `sod_conflict` with
  `details.capability` + `details.rules`; the explain surface shows the
  stage (fragment enum extended).
- `SodRule` writes bump the decision-cache generation (platform rows
  bump global), so rules added after assignment bite within the same
  request cycle — proven by test, not by TTL hope.
- Still pending: rule-admin CRUD with operator consent, `rule_created` /
  `rule_updated` events, `approval_chain` in the workflow engine.

### Implementation note A-SEC-011-3 — rule-admin CRUD live (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- `SodRuleController` (`/api/v1/platform/sod-rules`, issuer-membership
  gated like role admin, machines denied): tenant custom rules,
  overrides by code (downgrade block→warn), cursor-paginated effective
  list with `source`/`overrides_platform`, `rule_created`/`rule_updated`
  events + `sod.rule.*` audit rows.
- Platform rows are immutable (409 with override guidance); disabling a
  code with an active platform block fails closed (422
  `operator_consent_required`) — consent has no issuer yet, so removal
  is impossible, exactly as §7.1 requires. Override deletion resurfaces
  the platform default (evaluator merges by code; nothing to rebuild).
- `approval_chain` enforcement in the workflow engine is now the only
  remaining SoD item.

### Amendment A-SEC-011-4 — approval_chain removed from v1 scope (2026-10-03)

**Status:** ACTIVE · **Supersedes:** pending item in A-SEC-011-3

- **Decision:** `approval_chain` requirement dropped from v1. The
  workflow engine (W1–W3) is out of scope; no multi-step approval
  flows will be built.
- **Standing SoD:** rule CRUD (A-SEC-011-3), runtime re-evaluation
  (A-SEC-011-2), assignment-time (A-SEC-011-1), operator-consent
  guard — all still active. No workflow engine in v1.

---

## SEC-012 Route authorization requirements

- id: SEC-012
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §8
- depends_on: SEC-009
- affects: EXP-002

### Route Authorization Requirements

Normative (architecture-tested, [domain framework §7](../19-testing/testing-strategy.md)):

1. **Every** registered route declares exactly one of:
   - `can:<permission>` middleware (web), or
   - `scope:<scope>` + `can:<permission>` (API; scope middleware verifies the
     token scope, policy verifies the grant + ABAC), or
   - an explicit kernel public marker (health, discovery, auth endpoints —
     maintained in a kernel allowlist; CI fails if the allowlist grows
     without review).
2. Routes for `sensitive` permissions additionally carry `requires_acr:2`.
3. Permission strings on routes MUST exist in the catalog at boot; unknown
   permission on a route is a boot error.
4. The architecture test emits the full **authorization matrix**
   (route × permission × scope × ACR) as a CI artifact for security review.

---

## SEC-013 Kernel permission semantics

- id: SEC-013
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §9
- depends_on: SEC-006
- affects: CTRL-020

### Kernel Permission Semantics for Platform Operations

Tenant-level platform management (memberships, orgs, roles, settings, IdP,
flags, clients) is performed through `platform:*` permissions assigned inside
the tenant's own role system — the tenant admin console manages its own
people. True platform-operator actions (tenant provisioning/promotion, plan
management, cross-tenant support) live on a separate kernel admin surface
([security baseline §4](./security-baseline.md)) with:

- dedicated operator users (no tenant memberships),
- mandatory ACR 2 + break-glass logging,
- every operator action in the tenant's audit trail with actor type
  `platform_operator`.

---

---

## Supplementary migrated content


The sections below are the NEW capability-model upgrade (planning [gap analysis §4](../01-product/gap-analysis.md#4-authorization-model-upgrade-decision-record);
locked decisions LD-01, LD-02, LD-15, LD-16). They are additive: §1–§10 of
this register are unchanged (LD-02). They cite the
[hierarchy register](../05-hierarchy/hierarchy-and-working-context.md) for
Authority = hierarchy node + scope mode, and the
[kernel contracts register](../07-kernel/kernel-contracts.md) for
Capability (K-004), Policy (K-009), AuthorizationDecision (K-013), and
Delegation (K-030).

## SEC-020 Capability model

- id: SEC-020
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §12; LD-01; LD-02; pasted authz analysis
- depends_on: SEC-009, K-004
- affects: SEC-021, SEC-022, SEC-023

Capabilities are **parsed**, not opaque strings. Every capability code
`<module>:<resource>:<action>` (colon syntax retained per LD-01) parses to
structured metadata: `module`, `resource`, `action`, plus `version` and a
`sensitive` flag. Scopes and conditions are **separate dimensions** and are
never encoded into the identifier.

- **Action vocabulary** is open per module: each module declares its
  actions in its manifest. The closed kernel action set
  (`view|create|update|delete|approve|configure|execute|export`) is the
  floor every module inherits; modules may add business actions
  (`submit`, `capture`, `convert`, `waive`, ...).
- **Business actions over CRUD**: where a domain has a meaningful business
  verb (approve, capture, convert, post), it SHOULD be declared as its own
  action rather than overloaded onto `update`.
- **Sensitive metadata** marks capabilities that require step-up or
  explicit grant review (approve, capture, execute, refund, configure on
  money/stock/security surfaces).
- The **capability registry API** gains: parse/lookup by code, list by
  module, sensitive-filter, and metadata read. Manifest materialization
  (CON-005) populates it.

## SEC-021 Policies

- id: SEC-021
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §13; LD-15; pasted authz analysis
- depends_on: SEC-020, K-009
- affects: SEC-022

Policies separate **permission** (does the principal hold the capability)
from **policy** (given they hold it, is this specific use permitted). A
policy is a declarative record with:

- `effect`: `allow` or `deny`;
- a **capability selector** (module/resource/action pattern);
- **scope bindings** (`scope_type`/`scope_id`, resolved against hierarchy
  nodes per the hierarchy register);
- a **bounded condition grammar** — a closed set of comparison operators
  over declared fields (amount, currency, org, time, status). Free-form
  expression languages are **banned** (LD-15).

**Role-assignment scoping**: a role assignment may carry
`scope_type`/`scope_id`, binding the granted capabilities to a hierarchy
node + scope mode. This is how RBAC grants acquire data scope without
encoding scope into the capability.

**Platform defaults vs tenant policies** (LD-15): platform-default policies
(`tenant_id NULL`) are overridable but not silently removable; tenant
policies are audited, versioned, and evaluated at decision time. The
central `policies` table lands per the tenancy data architecture.

## SEC-022 Explicit DENY and precedence chain

- id: SEC-022
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §14; pasted authz analysis
- depends_on: SEC-020, SEC-021, K-013
- affects: SEC-024

Evaluation is a deterministic chain, evaluated top-to-bottom; the first
decisive result wins:

1. **Explicit DENY** — any matching `deny` policy or explicit denial
   returns DENY immediately.
2. **Security constraints** — SoD conflicts, tenant/membership state
   (suspended/expired), unlicensed module, unresolved tenant/working
   context → DENY.
3. **Policy constraints** — matching policy conditions that fail → DENY.
4. **Explicit ALLOW** — `scopes ∩ grants ∩ membership`, narrowed by
   working context (`WorkingContext ⊆ MaximumAuthority`) and satisfied
   `allow` policies → ALLOW.
5. **Default DENY** — if no explicit allow is reached, DENY (fail closed,
   CON-003).

The chain integrates at the effective-authority step (§5.1 step 5 of this
register's migrated content). Every decision records which stage decided it
in the decision log (SEC-024).

Console consumer: Administration → Explain access (`/platform/explain`)
replays this chain for one member + capability through
`GET /api/v1/authz/explain` (capability `platform:authz:explain`;
evaluation bypasses the decision cache, so the trace is always current)
and renders the per-stage trace — stage, outcome, reason, details.

## SEC-023 Delegation

- id: SEC-023
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §15; LD-16; pasted authz analysis
- depends_on: SEC-020, K-030
- affects: SEC-024

Delegation grants a delegatee a subset of the grantor's capabilities for a
bounded window. Normative rules (LD-16):

- Delegations are **time-boxed**: always bounded by `expires_at`; no
  open-ended delegations.
- Delegations are **revocable**: the grantor (or an administrator) may
  revoke at any time; revocation takes effect immediately.
- Delegations **carry the grantor's scope restrictions**: a delegatee never
  exceeds the grantor's own scopes or working-context bounds.
- Delegations are **SoD-checked** at assignment **and at use**: a SoD
  conflict present at delegation or arising later denies the delegated use.
- **Self-delegation** and **upward delegation** (to a membership with
  broader grants) are **rejected**.
- Delegations emit dedicated events (`platform.delegation.*`) and are fully
  audited.

The central `delegations` table and `platform.delegation.*` events land per
the tenancy and events registers.

## SEC-024 Authorization decision log and AI-agent safety

- id: SEC-024
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §16; pasted authz analysis
- depends_on: SEC-022, K-013
- affects: 17-ai, 19-testing

**Decision log.** Every authorization decision is logged with: principal,
capability, resource, working context, decision, deciding stage
(SEC-022 chain step), evaluated policies, and timestamp. Logging is
**sampled allow / full deny**: allow decisions may be sampled at a
configured rate; **every deny is logged in full**. The decision log feeds
anomaly detection (the AI register) and is queryable by platform operators.
The central `authorization_decisions` table lands per the tenancy data
architecture.

**AI-agent safety rules.** Authorization for agent principals is stricter
than for humans:

- **Per-call re-evaluation**: an agent's authorization is re-evaluated on
  every tool call; cached grants are not reused across calls.
- **Machine-token sensitive-scope ban**: machine/agent tokens MUST NOT
  carry `sensitive`-flagged scopes (SEC-020). Sensitive operations require
  a human principal or an explicitly reviewed, narrow delegation.
- **Budget-guarded tool calls**: agent tool calls run under a budget
  (call/value quota); exceeding the budget cuts off further calls and
  raises an alert. Budget state is part of the decision log.

These rules are enforced in the same decision chain and are a named test
class in the [testing strategy](../19-testing/testing-strategy.md).

---

### Verification requirements & assumptions (migrated)

- **Authorization matrix tests**: for each module, a generated test walks
  every route × (no-auth, wrong-tenant, no-permission, no-scope, wrong-org,
  permission-granted) and asserts the exact status + envelope code.
- **SoD tests**: each seeded rule has a positive (blocked) and negative
  (allowed) case at assignment and at runtime.
- **Intersection tests**: broad-scope/low-grant and narrow-scope/high-grant
  combinations both deny correctly (§5.2).
- **Cache-invalidation tests**: role revocation denies within one request.

---

- No per-record ACLs in v1 (org subtree scoping + ownership cover the
  requirements); a record-ACL extension is additive if ever needed.
- Field-level redaction (e.g. hiding salary fields) is handled by module
  resources/policies in v2 scope; v1 relies on permission granularity.
- Role hierarchy/inheritance is intentionally omitted (flat roles + explicit
  grants keep evaluation auditable); built-in role presets cover common needs.
