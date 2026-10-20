# Kernel Contracts Register

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** docs 01–06 contract surfaces; pasted master plan §kernel contracts

The single normative table of every cross-boundary data contract the platform relies on (K-001…K-030). Each row gives the contract name, a schema sketch, and the owning document. Machine-readable extraction (JSON Schema / OpenAPI) is deferred to the implementation phase; this register is the prose source of truth until then.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| K-001 | [Principal](#k-001-principal) | Principal { |
| K-002 | [Membership](#k-002-membership) | Membership { |
| K-003 | [RoleProfile](#k-003-roleprofile) | RoleProfile { |
| K-004 | [Capability](#k-004-capability) | Capability { |
| K-005 | [AuthorityGrant](#k-005-authoritygrant) | AuthorityGrant { |
| K-006 | [Scope](#k-006-scope) | Scope { |
| K-007 | [Hierarchy](#k-007-hierarchy) | Hierarchy { |
| K-008 | [WorkingContext](#k-008-workingcontext) | WorkingContext { |
| K-009 | [Policy](#k-009-policy) | Policy { |
| K-010 | [TenantContext](#k-010-tenantcontext) | TenantContext { |
| K-011 | [ExecutionContext](#k-011-executioncontext) | ExecutionContext { |
| K-012 | [ResourceReference](#k-012-resourcereference) | ResourceReference { |
| K-013 | [AuthorizationRequest / AuthorizationDecision](#k-013-authorizationrequest--authorizationdecision) | AuthorizationRequest { |
| K-014 | [Feature](#k-014-feature) | Feature { |
| K-015 | [ModuleManifest](#k-015-modulemanifest) | ModuleManifest { |
| K-016 | [Configuration](#k-016-configuration) | Configuration { |
| K-017 | [Blueprint](#k-017-blueprint) | Blueprint { |
| K-018 | [Command](#k-018-command) | Command { |
| K-019 | [Query](#k-019-query) | Query { |
| K-020 | [Event](#k-020-event) | Event { |
| K-021 | [EventEnvelope](#k-021-eventenvelope) | EventEnvelope { |
| K-022 | [Outbox](#k-022-outbox) | OutboxRow { |
| K-023 | [Idempotency](#k-023-idempotency) | IdempotencyRecord { |
| K-024 | [Error](#k-024-error) | Error { |
| K-025 | [Audit](#k-025-audit) | AuditRecord { |
| K-026 | [Version](#k-026-version) | Version { |
| K-027 | [Correlation](#k-027-correlation) | Correlation { |
| K-028 | [Extension](#k-028-extension) | Extension { |
| K-029 | [Health](#k-029-health) | Health { |
| K-030 | [Delegation](#k-030-delegation) | Delegation { |

---


The kernel-contracts register is the single normative table of every
cross-boundary data contract the platform relies on. Each row gives the
contract name, a schema sketch, and the owning document. Machine-readable
extraction (JSON Schema / OpenAPI) is deferred to the implementation phase
per ADR-0001 and the D6 decision; this register is the prose source of
truth until then.

Schema sketches use a compact notation: `field: type` with `?` for
optional and `[]` for lists. All contracts are tenant-scoped unless marked
`(central)`.

## K-001 Principal

- id: K-001
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 03 §1; doc 04 §1
- affects: SEC-005, HIER-004

```
Principal {
  principal_id: uuid
  kind: enum(user | service | agent | partner)
  tenant_id: uuid?        # null for platform-level service principals
  user_id: uuid?          # when kind=user
  client_id: uuid?        # when kind=service|agent|partner
  memberships: Membership[]
}
```

Owning docs: [identity platform](../04-security/identity-and-token-platform.md),
[authorization](../04-security/authorization-and-policy.md).

## K-002 Membership

- id: K-002
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §1; doc 04 §3
- affects: SEC-009, HIER-015

```
Membership {
  membership_id: uuid
  user_id: uuid
  tenant_id: uuid
  org_node_id: uuid       # organization-hierarchy node
  roles: RoleProfile[]
  state: enum(active | suspended | expired)
}
```

Owning docs: [tenancy](../06-tenancy/tenancy-and-data.md),
[authorization](../04-security/authorization-and-policy.md).

## K-003 RoleProfile

- id: K-003
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §3
- affects: SEC-009

```
RoleProfile {
  role_id: uuid
  code: string            # e.g. sales_manager
  tenant_id: uuid?        # null = platform built-in role
  capabilities: Capability[]
  sod_set: string[]
}
```

Owning doc: [authorization](../04-security/authorization-and-policy.md).

## K-004 Capability

- id: K-004
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §12; LD-01
- affects: SEC-012

```
Capability {
  code: string            # <module>:<resource>:<action>
  module: string
  resource: string
  action: string
  version: int
  sensitive: bool
  description: string
}
```

Owning doc: [authorization §12](../04-security/authorization-and-policy.md).

## K-005 AuthorityGrant

- id: K-005
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §3; HIER-004
- affects: SEC-009, HIER-004

```
AuthorityGrant {
  grant_id: uuid
  principal_id: uuid
  capability: Capability
  hierarchy_node_id: uuid
  scope_mode: enum(SELF | NODE | DIRECT_CHILDREN | SUBTREE)
  conditions: Condition[]
  valid_from / valid_until: timestamp?
}
```

Owning docs: [authorization](../04-security/authorization-and-policy.md),
[hierarchy](../05-hierarchy/hierarchy-and-working-context.md).

## K-006 Scope

- id: K-006
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §6
- affects: SEC-010

```
Scope {
  scope: string           # OAuth scope mirroring capability codes
  grants: Capability[]
}
```

Effective authority = scopes ∩ grants ∩ membership. Owning doc:
[authorization](../04-security/authorization-and-policy.md).

## K-007 Hierarchy

- id: K-007
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: HIER-001…003
- affects: HIER register

```
Hierarchy {
  hierarchy_id: uuid
  tenant_id: uuid
  type: enum(organization | management | sales_territory | warehouse
             | legal_entity | project | custom)
  name: string
  version: int
  nodes: HierarchyNode[]
  edges: HierarchyEdge[]
  authority_roots: uuid[]
}
HierarchyNode { node_id, node_key, name, attrs: json, state }
HierarchyEdge { parent_node_id, child_node_id }
```

Owning doc: [hierarchy](../05-hierarchy/hierarchy-and-working-context.md).

## K-008 WorkingContext

- id: K-008
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: HIER-006…010
- affects: HIER-008, EXP-011

```
WorkingContext {
  principal_id: uuid
  hierarchy_id: uuid
  node_id: uuid
  scope_mode: enum(SELF | NODE | DIRECT_CHILDREN | SUBTREE)
  stack: ContextFrame[]
  updated_at: timestamp
}
```

Owning doc: [hierarchy](../05-hierarchy/hierarchy-and-working-context.md).

## K-009 Policy

- id: K-009
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §13; LD-15
- affects: SEC-013

```
Policy {
  policy_id: uuid
  tenant_id: uuid?        # null = platform default
  effect: enum(allow | deny)
  capability_selector: pattern
  scope_bindings: ScopeBinding[]
  conditions: Condition[]  # bounded grammar, no free-form expressions
  version: int
}
```

Owning doc: [authorization §13](../04-security/authorization-and-policy.md).

## K-010 TenantContext

- id: K-010
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §4.1
- affects: TEN-006, DELTA-004

```
TenantContext {
  tenant_id: uuid
  db_mode: enum(shared | dedicated)
  connection: string
  plan_id: uuid
  feature_flags: map<string,bool>
}
```

Owning doc: [tenancy](../06-tenancy/tenancy-and-data.md).

## K-011 ExecutionContext

- id: K-011
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §4.4
- affects: TEN-006

```
ExecutionContext {
  tenant_context: TenantContext
  principal: Principal
  working_context: WorkingContext?
  request_id: string
  correlation_id: string
}
```

Owning doc: [tenancy](../06-tenancy/tenancy-and-data.md).

## K-012 ResourceReference

- id: K-012
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §5
- affects: DF-006

```
ResourceReference {
  module: string
  resource_type: string
  resource_id: uuid
}
```

The only legal way to point at another module's data (no cross-module
foreign keys). Owning doc: [domain framework](../09-domain-framework/domain-framework.md).

## K-013 AuthorizationRequest / AuthorizationDecision

- id: K-013
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §5, §16
- affects: SEC-010, SEC-016

```
AuthorizationRequest {
  principal: Principal
  capability: Capability
  resource: ResourceReference?
  working_context: WorkingContext?
  tenant_context: TenantContext
}
AuthorizationDecision {
  decision: enum(allow | deny)
  reason: enum(explicit_deny | security_constraint | policy_constraint
               | allow_grant | default_deny | forged_context)
  evaluated_policies: uuid[]
  logged: bool
}
```

Owning doc: [authorization](../04-security/authorization-and-policy.md).

## K-014 Feature

- id: K-014
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §10; CTRL-004
- affects: PROD-010, CTRL-004

```
Feature {
  flag_key: string
  module: string
  state: enum(installed | licensed | configured | activated | healthy)
  tenant_overrides: map<uuid,bool>
}
```

Owning docs: [kernel services](kernel-services.md),
[control plane](../08-control-plane/control-plane.md).

## K-015 ModuleManifest

- id: K-015
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §3.2
- affects: CTRL-003, DF-002

```
ModuleManifest {
  code: string
  version: string
  phase: string
  dependencies: string[]
  permissions: Capability[]
  events: EventDescriptor[]
  scopes: string[]
  feature_flag: string
  navigation: NavEntry[]
  config_classes: ConfigClass[]
}
```

Owning docs: [domain framework](../09-domain-framework/domain-framework.md),
[control plane](../08-control-plane/control-plane.md).

## K-016 Configuration

- id: K-016
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §9; CTRL-013
- affects: CTRL-013

```
Configuration {
  key: string
  value: json
  precedence_level: enum(platform | plan | tenant | org | module | user)
  scope_id: uuid?
}
```

Owning docs: [kernel services](kernel-services.md),
[control plane](../08-control-plane/control-plane.md).

## K-017 Blueprint

- id: K-017
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: CTRL-005…007
- affects: CTRL-005

```
Blueprint {
  blueprint_id: uuid
  tenant_id: uuid
  version: int
  modules: ModuleIntent[]
  config: Configuration[]
  hierarchy_defaults: json
}
ModuleIntent { module: string, license_tier: string, activate: bool }
```

Owning doc: [control plane](../08-control-plane/control-plane.md).

## K-018 Command

- id: K-018
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §8; DF-004
- affects: DF-004

```
Command {
  command_id: uuid
  name: string            # module.resource.action
  payload: json
  idempotency_key: string?
}
```

Owning docs: [API platform](api-platform.md),
[domain framework](../09-domain-framework/domain-framework.md).

## K-019 Query

- id: K-019
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §2; DF-005
- affects: DF-005

```
Query {
  name: string
  filters: json
  page: { cursor?, limit }
  scope: WorkingContext?
}
```

Owning docs: [API platform](api-platform.md),
[domain framework](../09-domain-framework/domain-framework.md).

## K-020 Event

- id: K-020
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2
- affects: EV register

```
Event {
  name: string            # module.subject.verb
  payload: json
}
```

Owning doc: [events](../12-events/events-and-outbox.md).

## K-021 EventEnvelope

- id: K-021
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.1
- affects: EV-002

```
EventEnvelope {
  event_id: uuid
  name: string
  tenant_id: uuid
  occurred_at: timestamp
  correlation_id: string
  causation_id: uuid?
  actor: Principal?
  payload: json
  schema_version: int
}
```

Owning doc: [events](../12-events/events-and-outbox.md).

## K-022 Outbox

- id: K-022
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §3
- affects: EV-006

```
OutboxRow {
  outbox_id: uuid
  tenant_id: uuid
  envelope: EventEnvelope
  status: enum(pending | relayed | failed)
  attempts: int
}
```

Owning doc: [events](../12-events/events-and-outbox.md).

## K-023 Idempotency

- id: K-023
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §12; doc 06 §5
- affects: DF-008

```
IdempotencyRecord {
  key: string
  tenant_id: uuid
  request_hash: string
  response: json
  created_at / expires_at: timestamp
}
```

Owning docs: [kernel services](kernel-services.md),
[API platform](api-platform.md).

## K-024 Error

- id: K-024
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §4
- affects: DF-009

```
Error {
  code: string            # stable machine-readable
  message: string
  details: json?
  request_id: string
}
```

Owning doc: [API platform](api-platform.md).

## K-025 Audit

- id: K-025
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §5
- affects: CON-004

```
AuditRecord {
  audit_id: uuid
  tenant_id: uuid
  actor: Principal
  action: string
  resource: ResourceReference
  before / after: json?
  request_id: string
  prev_hash / hash: string
}
```

Owning doc: [kernel services](kernel-services.md).

## K-026 Version

- id: K-026
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: CON-006
- affects: DF-010

```
Version {
  entity: string
  version: int
  effective_at: timestamp
  superseded_by: int?
}
```

Owning doc: [domain framework](../09-domain-framework/domain-framework.md).

## K-027 Correlation

- id: K-027
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §11
- affects: EV-004

```
Correlation {
  correlation_id: string
  causation_id: string?
  request_id: string
}
```

Propagated across commands, events, jobs, and webhooks. Owning doc:
[events](../12-events/events-and-outbox.md).

## K-028 Extension

- id: K-028
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 11
- affects: DELTA-012

```
Extension {
  extension_id: uuid
  kind: enum(hook | custom_field | custom_object | custom_report)
  module_target: string
  manifest: json
  scopes: string[]
}
```

Owning doc: [ecosystem](../18-extensions/ecosystem.md).

## K-029 Health

- id: K-029
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00 §6.5; CTRL-016
- affects: CTRL-016

```
Health {
  subject: string         # platform | tenant | module
  status: enum(up | degraded | down)
  checks: { name, status, latency_ms }[]
}
```

Owning docs: [control plane](../08-control-plane/control-plane.md),
[SRE](../20-sre/sre-operations.md).

## K-030 Delegation

- id: K-030
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §15; LD-16
- affects: SEC-012

```
Delegation {
  delegation_id: uuid
  grantor: Principal
  delegatee: Principal
  capabilities: Capability[]
  scope_restrictions: ScopeBinding[]
  valid_from / expires_at: timestamp
  sod_checked: bool
  revoked_at: timestamp?
}
```

Owning doc: [authorization §15](../04-security/authorization-and-policy.md).
