# Integration Framework & Webhooks

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [API platform §7](./integration-framework.md); [API platform §10](./integration-framework.md); [ecosystem §3](../18-extensions/ecosystem.md)

The integration framework is the single contract surface for all external integrations: webhooks (subscription model, delivery contract, retries, secret rotation — [API platform §7](./integration-framework.md)), their Phase 5 promotion to a managed product surface ([ecosystem §3](../18-extensions/ecosystem.md)), and channel adapters that map external or non-web channels onto the platform API without business rules ([API platform §10](./integration-framework.md)). Connector-specific behavior lives in dossiers: [Shopify sync](./connectors/shopify.md) and [India statutory](./connectors/india-statutory.md).

---

## Webhooks

### 7.1 Subscription model

- Tenant admins (or partner clients via API) subscribe to event names from
  the event catalog ([kernel services §2.2](../12-events/events-and-outbox.md)) at
  `/api/v1/platform/webhooks/endpoints`:
  `{ url, events: ["sales.order.confirmed", ...], secret, status }`.
- URL MUST be HTTPS; loopback/link-local/private ranges are rejected
  (SSRF guard); redirects are not followed.
- Max 20 endpoints per tenant; per-endpoint event list is explicit — no
  wildcard subscription in v1.
- Permission: `kernel:webhooks:manage` (sensitive).

### 7.2 Delivery contract

- Delivery = `POST` to the subscriber URL with JSON body = the full event
  envelope ([kernel services §2.1](../12-events/events-and-outbox.md)) — subscribers see exactly what internal listeners see.
- Headers: `Navdhya-Webhook-Id` (event_id), `Navdhya-Webhook-Timestamp`,
  `Navdhya-Webhook-Signature`, `Navdhya-Request-Id`, `User-Agent:
  Navdhya-Webhooks/1.0`.
- **Signature**: HMAC-SHA256 over `"<webhook_id>.<timestamp>.<raw_body>"`
  (webhook_id, dot, unix timestamp, dot, raw JSON body) using the endpoint
  secret; hex digest in the signature header. Timestamps older than
  5 minutes MUST be rejected by verifiers (documented + in SDKs).
- At-least-once delivery; subscribers MUST dedupe on `event_id`
  (`Navdhya-Webhook-Id`).
- Timeouts: 10 s connect+read; non-2xx or timeout = failure.

### 7.3 Retries & operational state

- Retry schedule: 1 min, 5 min, 30 min, 2 h, 6 h, 12 h, then stop (≈ 24 h
  window, [architecture master §6.1](../02-architecture/architecture-master.md)).
- Delivery log (tenant): `webhook_deliveries` — endpoint, event_id, status,
  response code, latency, attempt; retained 30 days; visible in admin console
  with redelivery action (redelivery reuses the same event_id).
- Circuit breaker: >90% failures over 24 h → endpoint `disabled` +
  notification to tenant admins + `platform.webhook.endpoint_disabled`.
- Secret rotation: `.../endpoints/{id}:rotate_secret` returns the new secret;
  both secrets sign during a 24 h overlap window (dual signature headers).

---
---

## Webhooks GA (Phase 5 promotion)

Webhooks are designed and specified completely in [API platform §7](./integration-framework.md) (subscription
model, delivery contract, retries, circuit breaker, secret rotation). Phase
5 promotes them from first-party infrastructure to a **managed product
surface**:

### 3.1 GA additions (delta over [API platform §7](./integration-framework.md))

1. **Self-service management UI + API GA**: endpoint CRUD, per-event test
   deliveries, delivery log browser with redelivery, and failure analytics
   under `/api/v1/platform/webhooks` (permission
   `kernel:webhooks:manage`, sensitive — unchanged).
2. **Event catalog browsing**: tenants can only subscribe to events their
   plan exposes (plan bundles filter the catalog; [tenancy & data §3](../06-tenancy/tenancy-and-data.md) `plans`).
3. **Filtering**: per-endpoint payload filters (JSON-path conditions on
   envelope fields, e.g. `payload.org_id == "01J8..."`) evaluated at
   fan-out time — reduces subscriber noise without new endpoints. Filters
   are validated against the event schema at save time.
4. **Batched delivery option**: endpoints may opt into batching (window
   5–60 s; batches of ≤ 100 envelopes in a JSON array body; signature over
   the batch body) for high-volume subscribers.
5. **Delivery SLA surface**: delivery success-rate metrics per endpoint in
   the admin (from `webhook_deliveries`); endpoints below threshold get
   the circuit-breaker treatment of [API platform §7.3](./integration-framework.md).
6. **Dead-letter inspection**: dead webhook events (relay dead-letter,
   [kernel services §3.2](../12-events/events-and-outbox.md)) are inspectable + replayable per endpoint by tenant admins.

### 3.2 Event catalog (GA additions)

`platform.webhook.endpoint_created`, `platform.webhook.endpoint_updated`,
`platform.webhook.endpoint_disabled`, `platform.webhook.secret_rotated`,
`platform.webhook.batch_delivered` (ops telemetry),
`platform.webhook.dead_lettered` (per-endpoint alert).

### 3.3 Acceptance for GA

- Signature reference implementations (TS + PHP) pass the shared test
  vectors; test vectors ship in the SDK.
- Replay/redelivery produces byte-identical bodies with the same event_id.
- Batch delivery preserves per-event ordering within an aggregate.
- Plan filtering verified: a starter-plan tenant cannot subscribe to
  payroll.* events even via direct API.

---
---

## Channel adapters

### 10.1 Adapter model

A channel adapter maps an external or non-web channel onto the platform API:

```text
channel protocol ──▶ adapter (auth mapping, payload mapping, retry policy)
                        └──▶ /api/v1 (same contracts, same authZ, same audit)
```

Adapters live in `app/Platform/Channels/` (kernel-owned) for first-party
channels; Phase 5 opens the model to ecosystem adapters ([ecosystem](../18-extensions/ecosystem.md)).

Normative: adapters MUST NOT contain business rules — only protocol
translation, idempotency bridging, and error mapping.

### 10.2 Channel matrix

| Channel | Auth ([identity & token platform](../04-security/identity-and-token-platform.md)) | Adapter notes |
|---------|---------------|---------------|
| Blade admin (v1) | session + CSRF; underlying OAuth web client | §8 convergence |
| POS terminal | device code grant (§3 [identity & token platform](../04-security/identity-and-token-platform.md)); per-terminal client | offline queue on device; sync uses idempotency keys; receipt printing local |
| Mobile (future) | auth code + PKCE | same `/api/v1`; push via notifications service (v2) |
| Partner integrations | client credentials, tenant-bound, scoped | scopes from client profile; webhook consumer side documented here §7 |
| Shopify / WooCommerce | partner client + adapter | catalog/order/customer sync maps to Catalog/Customers/Sales contracts; conflict policy: platform is source of truth unless tenant flags channel-owned fields |

### 10.3 POS specifics (v1 scope)

- Terminal pairing = device-code flow ([identity & token platform §3.4](../04-security/identity-and-token-platform.md)) with
  `client_type=pos_device`; pairing requires `commerce:pos:pair`
  (sensitive).
- POS operations use the commerce API ([commerce domains](../14-domains/commerce/store-checkout.md)): cart open/close, offline
  sale capture (`:bulk` with client-generated idempotency keys), shift
  open/close with cash declaration, receipt retrieval.
- Terminal revocation: admin action revokes the device client → next refresh
  fails → terminal locked screen.

---
---

## Connector contract (normative)

Every integration — channel adapter, statutory adapter, or partner integration — implements one contract surface. The mechanics are inherited from the webhook delivery contract and channel adapter model below, the outbox relay ([events & outbox](../12-events/events-and-outbox.md)), and API idempotency ([API platform](../07-kernel/api-platform.md)).

| Element | Requirement |
|---|---|
| Auth & credentials | Provider credentials stored encrypted in `channel_connections.credentials_ciphertext`; provider endpoints are configuration, never hard-coded; credential access requires the connection `configure` permission (sensitive) |
| Object mapping | Bidirectional provider↔canonical mapping declared per object; `channel_mappings` tracks `last_synced_hash` per entity for drift detection |
| Canonical model | Connectors map onto platform contracts (catalog, customers, sales, inventory, checkout) — never onto other modules' tables ([Delta isolation](../03-delta/delta-isolation.md)) |
| Sync directions | Outbound via outbox events (eventual consistency, retried); inbound via provider webhooks with signature verification before any write |
| Idempotency | Every outbound call carries an idempotency key derived from the source event; retries never duplicate registrations or orders |
| Retry & dead letter | Provider-call retries with backoff and circuit breaker; dead-lettered webhook events inspectable and replayable per endpoint |
| Rate limits | Egress throttled to provider quotas; provider 429/5xx honored with backoff; per-connection sync runs observable (`*_sync_runs`) |
| Reconciliation | Nightly full reconcile compares mapping hashes; drift raises repair-queue events, never silent overwrites |
| Failure isolation | Adapter failure never blocks the originating platform operation (statutory registration is async; imports are queued; invoice issuance proceeds without an IRN) |
| Authorization | Connection configuration and sync triggers are sensitive operations (step-up per the [experience architecture](../10-experience/experience-architecture.md)); sync history visible under the module's `sync:view` permission |

Connectors are documented as **dossiers**: [Shopify sync](./connectors/shopify.md) and [India statutory](./connectors/india-statutory.md). Each dossier records object mapping, source-of-truth rules, conflict resolution, sandbox mode, and schema pinning.
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| platform.webhook.* (GA ops events) | 10¹ |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| webhooks GA | outbox relay ([kernel services §3](../12-events/events-and-outbox.md)), event catalog ([kernel services §2.2](../12-events/events-and-outbox.md)) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| webhooks GA | `kernel:webhooks:manage` (sensitive — unchanged from [API platform §7.1](./integration-framework.md)) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../04-security/authorization-and-policy.md) register and [ADR-0002](../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Webhook signature & replay**: signature reference implementations (TS + PHP) pass shared test vectors; HMAC over `<webhook_id>.<timestamp>.<raw_body>`; timestamps older than 5 minutes rejected; redelivery replays byte-identical bodies with the same event_id.
2. **Webhook operations**: retry schedule (1 min → 12 h, then stop), circuit breaker (>90% failures over 24 h ⇒ endpoint disabled + tenant notification), SSRF guard cases (private/loopback/link-local rejection, no redirect following), secret-rotation dual-signature window.
3. **Webhooks GA**: plan-filtered catalog (starter tenant cannot subscribe
    to payroll.*); batch delivery ordering within aggregate; dead-letter
    replay byte-identical; filter JSON-path validation against schema.
4. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../06-tenancy/tenancy-and-data.md)).
5. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../19-testing/testing-strategy.md).
