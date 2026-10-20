# SRE Operations

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 00 §6; doc 05 §11.4; doc 06 §6; CTRL-016

Operational targets and controls for running the platform: the v1 non-functional targets (availability, durability, latency, scale), the health & readiness contract, alert classes and response, and noisy-neighbor controls. High availability, disaster recovery, and failure-mode degradation live in [HA & DR](./ha-dr.md); day-to-day procedures live in the [runbooks](../22-runbooks/incident-response.md). NFR targets are registered as ARCH-015…017 in the [architecture master](../02-architecture/architecture-master.md).

---

## Non-functional targets (v1)

### 6.1 Availability & durability

| Target | Value |
|--------|-------|
| Availability (API, monthly) | 99.9% (≤ ~43 min downtime/month) |
| RPO | ≤ 5 min (binlog-based point-in-time recovery) |
| RTO | ≤ 1 h (primary failover + app redeploy) |
| Webhook delivery | at-least-once, retried with exponential backoff for 24 h |
| Event durability | outbox guarantees no event loss on commit |

### 6.2 Latency (p95, excluding client network)

| API class | p95 | p99 |
|-----------|-----|-----|
| Token issuance (`/oauth/token`) | 300 ms | 600 ms |
| Simple reads (single resource, cached metadata) | 120 ms | 250 ms |
| List/search (indexed, ≤ 100 items/page) | 250 ms | 500 ms |
| Writes (single aggregate, incl. outbox commit) | 300 ms | 700 ms |
| Complex operations (checkout, posting, payroll run) | 1.5 s | 3 s |
| Reports (pre-aggregated) | 2 s | 5 s |

### 6.3 Scale targets (design horizon, not launch load)

- 1,000 tenants on the shared DB before first dedicated-mode promotions.
- 50 requests/s sustained per web node; horizontal scale-out beyond that.
- 10M audit rows per month with partition/archival strategy ([kernel services §5](../07-kernel/kernel-services.md)).
- 1M products, 10M inventory movement rows per year on shared tenant DB.

### 6.4 Security & compliance posture

OWASP ASVS Level 2 target; SOC 2 / ISO 27001 evidence readiness via the audit
log. Full baseline in [security baseline](../04-security/security-baseline.md).

### 6.5 Operability

- Health endpoints: `/up` (liveness) and `/ready` (DB + Redis + queue connectivity).
- Structured JSON logs with `request_id`, `tenant_id`, `user_id`, `client_id` on every line.
- Every tenant-visible error returns a stable machine-readable `code` ([API platform §4](../07-kernel/api-platform.md)).

---
---

## Health & readiness contract

Operations observes platform health through two kernel endpoints and the control-plane health contract:

- `/up` (liveness): process alive; no dependency checks.
- `/ready` (readiness): central DB ping, tenant shared DB ping, Redis ping, queue push test, outbox relay heartbeat age < 120 s. A non-200 removes the node from the load balancer.
- Module health: each module declares health checks consumed by the control plane; module health rolls up into tenant health and platform health (CTRL-016, [control plane](../08-control-plane/control-plane.md#ctrl-016-health-contract)).
- Every tenant-visible error returns a stable machine-readable `code` ([API platform](../07-kernel/api-platform.md)); structured JSON logs carry `request_id`, `correlation_id`, `tenant_id`, `user_id`, `client_id` on every line.

Observability mechanics (logging schema, kernel meters, health implementation) live in the [kernel services — observability](../07-kernel/kernel-services.md) register.
---

## Alert classes & response

| Class | Examples | Response |
|-------|----------|----------|
| P1 security | audit chain broken, refresh reuse, tenant isolation failure in prod | page immediately |
| P1 availability | /ready failing on >50% nodes, DB unreachable | page |
| P2 | outbox lag > 60 s, queue depth spike, dead letters | business-hours ops |
| P3 | flag churn, reindex durations, quota warnings | dashboard |

---

P1 security classes (audit chain broken, refresh reuse, tenant isolation failure in production) page immediately and open an incident under the [incident response runbook](../22-runbooks/incident-response.md). Meter definitions live in the [kernel services](../07-kernel/kernel-services.md) observability register.
---

## Noisy-neighbor controls

Plan-driven rate limiting is the v1 fairness control: per-client → per-tenant → per-user → per-IP evaluation order, plan-based tiers, and observable quota exhaustion (usage meters feed plan limits and upsell surfaces — never silent drops). Public-surface throttling guards are part of the CI-pinned allowlist ([testing strategy](../19-testing/testing-strategy.md)).

### 6.1 Throttle dimensions

Every API request is throttled on (in evaluation order):

1. **Per client** (`client_id`): protects the platform from a runaway
   integration.
2. **Per tenant** (aggregate of the tenant's clients): plan-based fairness.
3. **Per user** (interactive tokens): abuse control.
4. **Per IP** (unauthenticated endpoints only): auth endpoints, discovery.

### 6.2 Tiers (plan-driven, [tenancy & data §3](../06-tenancy/tenancy-and-data.md) `plans.rate_tier`)

| Tier | Sustained (req/min) | Burst | Typical |
|------|---------------------|-------|---------|
| `standard` | 600 | 120 | starter plans |
| `professional` | 3,000 | 600 | mid plans |
| `enterprise` | 12,000 | 2,400 | enterprise plans |
| `partner` | per-client profile ([identity & token platform §2.3](../04-security/identity-and-token-platform.md) `rate_tier`) | — | partner clients |

- Token issuance endpoints carry their own aggressive limits ([identity & token platform §11](../04-security/identity-and-token-platform.md)).
- Algorithm: sliding-window counter in Redis; failures closed to deny only
  when Redis is down **and** the route is not health/discovery.
- Responses carry `X-RateLimit-*` headers; 429 includes `Retry-After`.
- Quota exhaustion is observable (usage meters, [kernel services §11.3](../07-kernel/kernel-services.md)) and feeds plan
  upsell surfaces, not silent drops.

---
---

## Verification requirements

1. **Load profiles**: k6 release runs stay within the ARCH-016 latency budgets with no regression > 20% vs baseline ([testing strategy — load](../19-testing/testing-strategy.md)).
2. **Health endpoints**: `/up` and `/ready` dependency checks covered by feature tests from M0 (GATE-0 evidence).
3. **Alert production**: every P1 alert class has a producing test (audit chain break, refresh reuse, isolation failure injection).
4. **Throttle tiers**: tier tables asserted with a fake Redis clock ([API platform verification](../07-kernel/api-platform.md)).
5. **DR drill**: quarterly restore drill produces a signed report with measured RTO ([HA & DR](./ha-dr.md); evidence register GATE-4).