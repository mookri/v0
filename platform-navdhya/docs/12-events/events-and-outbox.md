# Events & Outbox

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 05 §2–3; doc 06 §7 (webhook pointer); doc 14 §8

The events register specifies the domain-event platform: the normative envelope, naming/versioning, kernel-filled tenant/actor fields, correlation, payload rules, the materialized event catalog with fail-closed publication, publication semantics, consumer idempotency, the transactional outbox, the relay worker, ordering guarantees, and the consolidated kernel event catalog.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| EV-001 | [EV-001](#ev-001-event-envelope) | Normative DomainEvent envelope shape |
| EV-002 | [EV-002](#ev-002-naming--versioning-rules) | <module>.<entity>.<past_verb>, additive versions |
| EV-003 | [EV-003](#ev-003-kernel-filled-fields) | tenant_id and actor set by kernel only |
| EV-004 | [EV-004](#ev-004-correlation--causation) | correlation_id / causation_id chains |
| EV-005 | [EV-005](#ev-005-payload-rules) | JSON scalars only, 64 KB soft cap |
| EV-006 | [EV-006](#ev-006-event-catalog--fail-closed-publication) | event_catalog registry, undeclared rejected |
| EV-007 | [EV-007](#ev-007-publication-semantics) | Outbox insert in caller tx, afterCommit listeners |
| EV-008 | [EV-008](#ev-008-consumer-idempotency) | processed_events dedupe on event_id |
| EV-009 | [EV-009](#ev-009-outbox-table) | outbox schema on tenant connection |
| EV-010 | [EV-010](#ev-010-relay-worker) | SKIP LOCKED claims, backoff, dead-letter |
| EV-011 | [EV-011](#ev-011-ordering-guarantees) | Per-aggregate ordering only |
| EV-012 | [EV-012](#ev-012-outbox-invariants--retention) | No bypass, immutable rows, replay tooling |
| EV-013 | [EV-013](#ev-013-consolidated-kernel-event-catalog) | platform.* event index |
| EV-014 | [EV-014](#ev-014-webhook-fan-out) | Relay → webhook delivery (integration framework) |
| EV-015 | [EV-015](#ev-015-event-verification) | Standing event/outbox suites |

---

## EV-001 Event envelope

- id: EV-001
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.1
- depends_on: K-021
- affects: EV-002

### Envelope (normative)

Every domain event extends `Platform\Events\DomainEvent` and serializes to:

```json
{
  "event_id": "01J8VZK3Q4Y6R2W9T1B5N7M3C0",
  "event_name": "sales.order.confirmed",
  "event_version": 1,
  "occurred_at": "2026-08-24T10:15:30.123456Z",
  "producer": { "module": "sales", "service": "navdhya-app" },
  "tenant_id": "01J8TENANT...",
  "actor": { "type": "user", "id": "42", "membership_id": "01J8MEM...", "client_id": "3" },
  "aggregate": { "type": "sales_order", "id": "01J8ORDER..." },
  "correlation_id": "req_01J8...",
  "causation_id": null,
  "payload": { "order_number": "SO-2026-000123", "currency": "INR", "total_minor": 1549900, "lines_count": 4 }
}
```

Rules:
1. `event_name` MUST match `<module>.<entity>.<past_verb>` ([domain framework §3.3](../09-domain-framework/domain-framework.md)) and
   be declared in the module manifest with a JSON Schema file.
2. `event_version` is a monotonically increasing integer; payload schemas
   evolve **additively only** — new optional fields may be added, existing
   fields MUST NOT be renamed, retyped, or removed without a new version.
3. `tenant_id` and `actor` are filled by the kernel from `TenantContext` —
   modules never set them manually.
4. `correlation_id` propagates the originating request id; `causation_id`
   links a reaction event to the event that caused it (event chains stay
   traceable in audit/search).
5. Payloads MUST be JSON-serializable scalars/arrays/objects; no Eloquent
   models, no PHP-specific types (carve-out rule C5, [domain framework §6](../02-architecture/architecture-master.md)).
6. Payload size soft cap 64 KB; large references use ids + contract lookups.

## EV-002 Naming & versioning rules

- id: EV-002
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.1 rules 1–2
- depends_on: EV-001
- affects: ARCH-014

`event_name` MUST match `<module>.<entity>.<past_verb>` and be declared in the module manifest with a JSON Schema file. `event_version` is a monotonically increasing integer; payload schemas evolve additively only — new optional fields may be added, existing fields MUST NOT be renamed, retyped, or removed without a new version.

## EV-003 Kernel-filled fields

- id: EV-003
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.1 rule 3
- depends_on: TEN-006
- affects: DELTA-009

`tenant_id` and `actor` are filled by the kernel from TenantContext — modules never set them manually. This guarantees the envelope's tenant is the true execution tenant and underpins tenant isolation of the event surface.

## EV-004 Correlation & causation

- id: EV-004
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.1 rule 4; K-027
- depends_on: K-027
- affects: 20-sre

`correlation_id` propagates the originating request id; `causation_id` links a reaction event to the event that caused it. Event chains stay traceable in audit and search. Correlation is propagated across commands, events, jobs, and webhooks.

## EV-005 Payload rules

- id: EV-005
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.1 rules 5–6
- depends_on: EV-001
- affects: DF-012

Payloads MUST be JSON-serializable scalars/arrays/objects; no Eloquent models, no PHP-specific types (carve-out rule). Payload size has a 64 KB soft cap; large references use ids + contract lookups.

## EV-006 Event catalog & fail-closed publication

- id: EV-006
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.2
- depends_on: CON-005
- affects: ARCH-013

### Event catalog

- `platform:sync-events` reads manifests and upserts central `event_catalog`:
  `name UNIQUE`, `module_code`, `schema_json`, `version`, `active`,
  `first_seen_at`, `updated_at`.
- CI validates every declared schema parses as JSON Schema (draft 2020-12)
  and that the module's event classes produce payloads conforming to it
  (schema round-trip test, [testing strategy §4](../19-testing/testing-strategy.md)).
- Undeclared events cannot be published: `EventPublisher` rejects names not
  in the catalog (fail closed).

## EV-007 Publication semantics

- id: EV-007
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.3
- depends_on: EV-009
- affects: DF-007

### Publication semantics

- Publishing is **fire-and-forget for the caller** but **exactly-once
  durable**: `EventPublisher::publish(DomainEvent $e)` inserts an outbox row
  inside the caller's DB transaction. If the transaction rolls back, the
  event never exists.
- In-process listeners (same monolith) receive the event **after commit**
  via Laravel's `afterCommit` dispatch, so listeners never see uncommitted
  state.
- Cross-module listeners MUST be idempotent: outbox relay guarantees
  at-least-once delivery; listeners dedupe on `event_id` using
  `processed_events` (tenant table, unique `event_id + listener key`,
  TTL-purged).

## EV-008 Consumer idempotency

- id: EV-008
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.3
- depends_on: K-023
- affects: DF-008

Cross-module listeners MUST be idempotent: the outbox relay guarantees at-least-once delivery; listeners dedupe on `event_id` using `processed_events` (tenant table, unique `event_id + listener key`, TTL-purged).

## EV-009 Outbox table

- id: EV-009
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §3.1
- depends_on: DATA-008
- affects: EV-010

### Outbox table (tenant connection)

`outbox`: `id BIGINT AUTO_INCREMENT` (ordering), `event_id ULID UNIQUE`,
`event_name`, `event_version`, `tenant_id`, `aggregate_type`, `aggregate_id`,
`payload JSON`, `status` (`pending|published|failed|dead`), `attempts`,
`available_at`, `published_at`, `created_at`.
Index: `(status, available_at)` for relay claims. RANGE partition by month
after volume threshold ([tenancy & data §9](../13-data/data-architecture.md)).

## EV-010 Relay worker

- id: EV-010
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §3.2
- depends_on: EV-009
- affects: EV-011

### Relay worker

`platform:outbox-relay` (dedicated process, [architecture master §5](../02-architecture/architecture-master.md)):

1. Claims up to N pending rows where `available_at <= now` using
   `SELECT ... FOR UPDATE SKIP LOCKED` (safe across multiple relay replicas).
2. For each row: publish to the internal queue topic
   `events.<module>.<entity>` and enqueue webhook fan-out ([API platform §7](../15-integrations/integration-framework.md)).
3. Mark `published` with `published_at`; on failure increment `attempts`,
   backoff `min(2^attempts * 5s, 15min)`; after 10 attempts → `dead` +
   `platform.outbox.dead_lettered` security/ops alert.
4. Ordering guarantee: per-aggregate ordering is preserved by the internal
   queue partition key `aggregate_type:aggregate_id`; no global ordering is
   promised.
5. Relay lag (oldest pending age) is a first-class metric with alerting
   threshold 60 s.

## EV-011 Ordering guarantees

- id: EV-011
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §3.2 rule 4
- depends_on: EV-010
- affects: DF-015

Per-aggregate ordering is preserved via the internal queue partition key `aggregate_type:aggregate_id`; no global ordering is promised. Consumers that need cross-aggregate ordering must reconstruct it from timestamps/versions, not from delivery order.

## EV-012 Outbox invariants & retention

- id: EV-012
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §3.3
- depends_on: EV-009
- affects: DATA-009

### Invariants

- No domain write may bypass the outbox (no direct queue pushes from module
  code; architecture test).
- Outbox rows are immutable once inserted; status transitions are the only
  updates.
- Retention: published rows purged after 30 days ([tenancy & data §10](../13-data/data-architecture.md)); dead rows
  retained 90 days for manual replay tooling (`platform:outbox-replay`).

---

## EV-013 Consolidated kernel event catalog

- id: EV-013
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.4
- depends_on: EV-006
- affects: 12-events

### Consolidated kernel event catalog (namespace `platform`)

Kernel events name the kernel as the module: `platform.<entity>.<past_verb>`,
where `<entity>` may be dotted (`scim.user`, `webhook.endpoint`). They are
registered in `event_catalog` with `module_code = platform`, follow the same
schema-versioning and fail-closed publication rules as module events, and are
declared in the kernel's own event registry (the kernel has no module
manifest). This section is the consolidated index; normative payload
definitions live in the owning section.

| Entity | Events | Defined in |
|--------|--------|-----------|
| `tenant` | `platform.tenant.provisioned` / `.activated` / `.suspended` / `.promoted` / `.offboarded` | [tenancy & data §3](../06-tenancy/tenancy-and-data.md) |
| `tenant` (privacy ops) | `platform.tenant.exported`, `platform.subject.erased` | [security baseline §5.3](../04-security/security-baseline.md) |
| `user` | `platform.user.invited` / `.created` / `.provisioned` / `.updated` / `.suspended` / `.deactivated` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `membership` | `platform.membership.created` / `.activated` / `.revoked` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `role` | `platform.role.assigned` / `.revoked` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `mfa` | `platform.mfa.enrolled` / `.revoked` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `session` | `platform.session.stepup_completed`, `platform.session.revoked` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `token` | `platform.token.revoked`, `platform.token.reuse_detected` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `idp` | `platform.idp.configured` / `.updated` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `client` | `platform.client.created` / `.secret_rotated` / `.revoked` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `scim` (core) | `platform.scim.user_provisioned` / `.user_updated` / `.user_deactivated` / `.group_synced` / `.drift_detected` | [identity & token platform §12](../04-security/identity-and-token-platform.md) |
| `scim` (GA) | `platform.scim.enabled` / `.disabled` / `.import_mode_activated` / `.reconcile_completed` | [ecosystem §6.3](../18-extensions/ecosystem.md) |
| `permissions` | `platform.permissions.synced` | [authorization & policy §2.1](../04-security/authorization-and-policy.md) |
| `sod` | `platform.sod.rule_created` / `.rule_updated` / `.conflict_blocked` / `.warning` | [authorization & policy §7.4](../04-security/authorization-and-policy.md) |
| `workflow` | `platform.workflow.transitioned` / `.approval_requested` / `.approved` / `.rejected` / `.approval_expired` | §4.4 |
| `outbox` | `platform.outbox.dead_lettered` | §3.2 |
| `audit` | `platform.audit.chain_broken` | §5.2 |
| `notification` | `platform.notification.failed` | §6.3 |
| `search` | `platform.search.reindexed` | §8.2 |
| `flags` | `platform.flags.changed` | §10.2 |
| `webhook` | `platform.webhook.endpoint_created` / `.endpoint_updated` / `.endpoint_disabled`, `platform.webhook.secret_rotated` / `.batch_delivered` / `.dead_lettered` | [API platform §7.3](../15-integrations/integration-framework.md), [ecosystem §3.2](../18-extensions/ecosystem.md) |
| `hook` / `sdk` | `platform.hook.registered` / `.updated` / `.disabled` / `.invocation_failed`, `platform.sdk.sandbox_created` | [ecosystem §1.5](../18-extensions/ecosystem.md) |
| `operator` | `platform.operator.break_glass_used` | [security baseline §4.2](../04-security/security-baseline.md) |

---

## EV-014 Webhook fan-out

- id: EV-014
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §7; doc 11 §3
- depends_on: EV-010
- affects: 15-integrations

The relay enqueues webhook fan-out for each published event. Webhook endpoints, signing, retries (exponential backoff for 24 h), dead-lettering, and replay are specified in the integration framework; GA additions land with the ecosystem phase. Webhook delivery is tenant-scoped and at-least-once.

## EV-015 Event verification

- id: EV-015
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 14 §8
- depends_on: EV-001
- affects: 19-testing

Standing suites: (a) schema round-trip (declared schema matches produced payload); (b) no direct queue pushes from module code; (c) outbox relay drains under event-flood profile; (d) per-aggregate ordering holds; (e) dead-letter alert fires after 10 attempts; (f) consumer idempotency under duplicate delivery. Each maps to a named test in the testing strategy.
