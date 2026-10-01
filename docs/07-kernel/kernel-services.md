# Kernel Services

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 05 §1, §5–§13 (events/outbox → events register; workflow → workflow register)

The kernel services are the platform primitives that are neither identity/authorization, events, nor workflow (those have their own registers): audit, notifications, files, search, settings, feature flags, observability, and idempotency. They are consumed by every domain module and are materialized from manifests where applicable (CON-005). Contract sketches for these services live in the kernel contracts register.


---

## Service inventory

| Service | Primary classes | Storage | Consumed by |
|---------|----------------|---------|-------------|
| Events & Outbox | `DomainEvent`, `EventPublisher`, `OutboxRelay` | tenant connection `outbox` | every module |
| Workflow | `WorkflowEngine`, `StateMachineDefinition`, `ApprovalPolicy` | tenant connection `workflow_instances`, `approval_requests` | purchasing, sales, finance, hr, commerce returns |
| Audit | `AuditLogger`, model observers | tenant connection `audit_logs` | every state change |
| Notifications | `Notifier`, templates, channel drivers | central templates; tenant `notifications_log` | every module |
| Files | `FileStore` | object storage disk + tenant `files` | every module |
| Search | `SearchIndex` + MySQL FULLTEXT driver | tenant `search_index_*` | catalog, customers, orders |
| Settings | `Settings` | central (global) + tenant (tenant/org/user) | kernel + modules |
| Feature Flags | `FeatureFlags` | central `feature_flags`, `tenant_feature_flags` | kernel + module registry |
| Observability | logging, request ids, health, usage meters | logs + Redis + central meters | everything |

---
---

## Audit service

### 5.1 Table (tenant connection)

`audit_logs`: `id BIGINT`, `tenant_id`, `occurred_at` (µs precision),
`actor_type` (`user|platform_operator|system|partner_client|scim`),
`actor_id`, `membership_id NULL`, `client_id NULL`, `request_id`,
`correlation_id`, `action` (stable code, e.g. `sales.order.approve`,
`platform.role.assigned`, `auth.login.failed`), `subject_type`,
`subject_id`, `before JSON NULL`, `after JSON NULL`, `meta JSON NULL`,
`prev_hash CHAR(64)`, `entry_hash CHAR(64)`.
Append-only: no UPDATE path in application code; no soft deletes.

### 5.2 Hash chain (tamper evidence)

- `entry_hash = SHA-256(prev_hash || canonical_json(entry_fields))`;
  `prev_hash` of the first row is a genesis constant.
- Chains are **per tenant** so dedicated-mode tenants keep their own chain.
- Nightly job verifies chain continuity; any break →
  `platform.audit.chain_broken` security alert (P1 severity).
- Chain verification is incremental (last verified id stored per tenant).

### 5.3 What MUST be audited

Minimum set (modules add domain actions on top):
1. Authentication: login success/failure, logout, step-up, MFA enrollment,
   token revocation, refresh reuse.
2. Authorization: role assignment/revocation, SoD blocks/warnings.
3. Tenant lifecycle: provisioning, suspension, promotion, offboarding.
4. Settings & flags: every change with before/after.
5. Sensitive data access: exports, bulk reads of personal data.
6. Every workflow approval/rejection.
7. CRUD on master data (customers, products, vendors, employees) via model
   observers; transactional mutations (orders, payments, journals) via
   explicit `AuditLogger` calls inside the domain service.

### 5.4 Rules

- Before/after capture MUST redact secrets (passwords, tokens, keys) via a
  kernel redaction list before serialization.
- Audit writes share the domain transaction (same connection) — a failed
  business write leaves no audit row and vice versa.
- Retention 7 years default ([tenancy & data §10](../13-data/data-architecture.md)); monthly partitions; archive to
  object storage with per-tenant keys before drop.
- Read access: `platform:audit:view` (tenant admins see their tenant's log;
  platform operators with break-glass logging); exports watermarked with
  exporter identity.

---
---

## Notifications

### 6.1 Model

- Templates live centrally (`notification_templates`): `code UNIQUE`,
  `module_code`, `channel set`, subject/body per locale (Blade syntax),
  `variables_schema JSON`.
- Tenant overrides allowed per locale (never change variables contract).
- `Notifier::send(template_code, recipients, variables, context)` resolves
  recipients' channel preferences (`notification_preferences` per
  user+channel) and enforces tenant quiet hours for non-critical classes.

### 6.2 Channels (v1)

| Channel | Driver | Notes |
|---------|--------|-------|
| In-app | database + polling (websockets out of scope v1) | unread counts in admin topbar |
| Email | SMTP/API provider (driver-swappable) | DKIM/SPF assumed operator-configured |
| SMS | provider driver | critical class only by default |
| Webhook | see [API platform §7](../15-integrations/integration-framework.md) | machine-to-machine, not a user channel |

### 6.3 Delivery log & guarantees

- `notifications_log` (tenant): recipient, channel, template, status
  (`queued|sent|failed|suppressed`), provider ref, timestamps; 90-day
  retention.
- At-least-once delivery via queue; idempotent send (dedupe key =
  template+recipient+subject ref).
- Failures retry with backoff; permanent failure → log + alert threshold.
- Events: `platform.notification.failed` (aggregate alerting only; no event
  per send).

---
---

## Files

### 7.1 Contract

`FileStore` API: `store(tenantPath, stream|file, meta)`, `url(id)` (signed),
`delete(id)`, `meta(id)`. All module file usage goes through it.

### 7.2 Storage layout & rules

- Disk: S3-compatible object storage in prod; local disk driver for dev.
- Path scheme: `<tenant_id>/<module>/<yyyy>/<mm>/<file_id>/<original_name>` —
  tenant prefix makes bucket policies and erasure trivial.
- `files` table (tenant): `id ULID`, `module`, `subject_type/id NULL`,
  `original_name`, `mime`, `size`, `disk`, `path`, `sha256`, `visibility`
  (`private|signed`), `uploaded_by`, `deleted_at`.
- Access is always through signed URLs (15 min default TTL) or streamed
  through an authorized controller route (`<module>:<resource>:view` +
  policy check); no public buckets.
- Upload limits per plan (default 25 MB/file); virus-scan hook point
  (integration in [security baseline §2](../04-security/security-baseline.md)); images get server-side dimension validation.
- Erasure hooks ([tenancy & data §10](../13-data/data-architecture.md), [security baseline §6](../04-security/security-baseline.md)) delete by tenant prefix.

---
---

## Search

### 8.1 Driver model

`SearchIndex` interface: `index(doc)`, `delete(id)`, `query(module, text,
filters, page)`. v1 driver: MySQL FULLTEXT. The interface exists so a
Meilisearch/OpenSearch driver can replace it without module changes.

### 8.2 Conventions

- Per-module index definitions in the module (fields, weights, filterable
  attributes) declared in `module.json` section `search`.
- Index rows live in tenant connection tables `search_index_<module>`
  rebuilt from source tables; updates via model events (post-commit),
  rebuild command `platform:search-reindex --module=<m>`.
- FULLTEXT natural-language queries + structured filters (status, org,
  date range) combined in SQL; pagination cursor-style.
- Search MUST honor tenant + org scopes (filters injected by kernel, not by
  modules).
- Events: none per query; reindex completion emits
  `platform.search.reindexed`.

---
---

## Settings

### 9.1 Scopes & storage

`settings` rows keyed by `(scope, scope_id, key)` with scope ∈
`global | tenant | org | user`:
- `global` rows live on the central connection.
- `tenant/org/user` rows live on the tenant connection.

### 9.2 Rules

1. Every setting key is registered in code (`Platform\Settings\Registry`)
   with type, default, validation, and which scopes may override —
   unregistered keys are a programming error (fail fast).
2. Resolution: `Settings::get(key, scopeContext)` walks user → org → tenant
   → global, first override wins.
3. Mutation goes through `platform:settings:manage`-guarded endpoints;
   every change audited with before/after.
4. Settings are cached per tenant (write-through invalidation); never store
   secrets in settings ([security baseline §3](../04-security/security-baseline.md)).
5. Well-known kernel settings: `tenant.base_currency` (immutable after first
   posting), `tenant.timezone`, `tenant.locale`, `tenant.fiscal_year_start`,
   `audit.retention_years`, `notifications.quiet_hours`, `files.max_size_mb`.

---
---

## Feature flags

### 10.1 Model

Central `feature_flags`: `code UNIQUE`, `kind` (`module|feature|ops`),
`default`, `description`. `tenant_feature_flags`: per-tenant overrides
(driven by plan bundle or explicit grant).

Evaluation: `FeatureFlags::enabled(code, tenant)` → tenant override ??
plan bundle ?? default. Module registry consults `module.<code>` flags to
include/exclude modules per tenant ([domain framework §2](../02-architecture/architecture-master.md)).

### 10.2 Rules

1. Flag checks in domain code MUST have a default-safe branch (feature off =
   legacy/no-op path works).
2. Flags are not settings: no org/user scope; use settings for user
   preferences.
3. Changes are audited (`platform.flags.changed`) and take effect within
   60 s (cache TTL).
4. Ops flags (e.g. `ops.outbox.pause`) are platform-operator only.

---
---

## Observability

### 11.1 Logging

- Structured JSON (channel `stack` with `single` JSON driver): fields
  `timestamp, level, message, request_id, correlation_id, tenant_id, user_id,
  client_id, module, context{}`.
- `request_id` generated at edge middleware; propagated to jobs, events,
  audit, and outgoing webhooks (header `Navdhya-Request-Id`).
- PII policy: emails/usernames logged only in identity/security contexts;
  payload logging opt-in per module with redaction list.

### 11.2 Health & readiness

- `/up` (Laravel health route): process alive, no dependency checks.
- `/ready`: central DB ping, tenant shared DB ping, Redis ping, queue push
  test, outbox relay heartbeat age < 120 s. Non-200 removes the node from LB.

### 11.3 Metrics (kernel meters)

Emitted as structured log events in v1 (scrape/forward via OTel collector is
the documented upgrade path):
- Request rate/latency per route class, error rate per envelope code.
- Outbox: pending age, dead-letter count, relay throughput.
- Queues: depth + processing latency per queue.
- Auth: token issuance rate, refresh reuse count (security), failed logins.
- Per-tenant usage meters: API calls, storage bytes, event volume, active
  users — feeds plan limits and billing ([security baseline §5](../04-security/security-baseline.md)).

### 11.4 Alert classes

| Class | Examples | Response |
|-------|----------|----------|
| P1 security | audit chain broken, refresh reuse, tenant isolation failure in prod | page immediately |
| P1 availability | /ready failing on >50% nodes, DB unreachable | page |
| P2 | outbox lag > 60 s, queue depth spike, dead letters | business-hours ops |
| P3 | flag churn, reindex durations, quota warnings | dashboard |

---
---

## Idempotency infrastructure

(Surface API here; HTTP semantics in [API platform §5](./api-platform.md).)

- `idempotency_keys` (tenant): `key` (client-provided + client_id hash),
  `tenant_id`, `route`, `request_hash`, `response_status`,
  `response_body JSON`, `created_at`, `expires_at` (24 h).
- Unique `(tenant_id, client_id, key)`; concurrent duplicate → 409
  `idempotency_in_progress`.
- Replay returns the stored response verbatim; mismatched payload for same
  key → 422 `idempotency_key_reused`.

---
---

## Verification requirements

- Outbox: transaction rollback leaves no outbox row; relay publishes
  exactly-once-to-queue with idempotent listeners; dead-letter path tested.
- Workflow: guard failure, hook rollback, approval SoD rejection, expiry.
- Audit: hash chain verification command; redaction tests; observer coverage
  per master-data model.
- Notifications: template variable validation against schema; per-channel
  failure retries.
- Settings: precedence walk test; unregistered key throws.
- Flags: override/plan/default matrix.
- Health: /ready dependency stubs each produce non-200 in isolation.