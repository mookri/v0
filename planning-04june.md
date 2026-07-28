# Navdhya Platform — Master Plan (v10, 04 June 2026)

> **Status:** Locked. 4 decisions confirmed. 100x expansion: ADRs, capacity, cost, threat model, DORA/DX, catalogs, runbooks, team topology, DoD, acceptance criteria.
> **Scope:** 14 module clusters, 80+ modules, 17 PRs, 3 engineers, ~6 months.
> **Storage:** PostgreSQL (Drizzle ORM). JSON for SEO content (never source of truth).
> **Cadence:** 1 PR per session, user sign-off after each.

---

# PART I — STRATEGIC FOUNDATION

## 0. North Star, Tenets, Locked Decisions

### North Star
A navigationally-correct, AEO/SEO-first, MNC-grade platform where any Indian household can find a verified priest/temple, book a service, get a transparent invoice, pay safely, and have the entire journey auditable end-to-end — at 100M+ users with sub-200ms p95.

### Tenets (locked)
1. **Schema-first.** Postgres Drizzle is the contract. JSON is the denormalized read cache.
2. **Hexagonal.** `domain` ↔ `ports` ↔ `adapters` ↔ `application`. UI and infra are replaceable.
3. **Zero-trust.** Every request authenticated, authorized (RBAC+ABAC), rate-limited, audited.
4. **Multi-tenant by design.** Every row has `tenant_id`. No cross-tenant joins. Subdomain routing.
5. **AEO/SEO contract on every page.** Speakable ≤280 chars + KeyFacts 3–5 + FAQ 3–5.
6. **CQRS + outbox.** Writes via `application/usecases`, events outbox, reads from views.
7. **Deterministic.** Seeds and IDs stable. A/B variants and rankings reproducible.
8. **Type-safe end-to-end.** Zod at boundary, Drizzle in middle, server actions on top.
9. **Idempotent everywhere.** Idempotency key on every write API and server action.
10. **Observability is a feature.** OTel, logs, traces, SLOs ship with the feature, not after.
11. **Boring tech where possible.** Postgres + Next + Redis + S3 + one queue. No exotic infra.
12. **Reversible.** Every change ships with a rollback plan, a feature flag, and a one-command revert.

### Locked Decisions (user-confirmed, 04 Jun 2026)

| # | Decision | Value |
|---|---|---|
| 1 | Payments at launch | **Full platform PSP integration** (Razorpay, ledger, payouts, TDS 194-O, GST e-invoice) |
| 2 | AI scope | **Read-only MCP + llms.txt at launch** (no write/booking tools) |
| 3 | Hindi | **English pages stay English; Hindi pages get full translation** (with human review queue) |
| 4 | Cadence | **1 PR per session, your sign-off** |

---

## 0a. Stakeholder Map

| Stakeholder | Interest | Influence | Cadence | Channel |
|---|---|---|---|---|
| **Founder / Product owner** | North Star, OKRs, scope | High | Weekly demo | Notion + Slack |
| **Engineering (3)** | PR cadence, blockers | High | Daily standup | Slack `#eng` |
| **Design** | IA, design system, AEO | Med | Weekly | Figma + Slack |
| **Content / Editorial** | 1,265 pages, authors, llms.txt | High | Weekly | Notion |
| **Hindi reviewers** | Translation quality | Med | Per batch | Review queue |
| **DevOps / SRE** | Infra, SLOs, FinOps | Med | On-call | PagerDuty |
| **Security** | Threat model, pentest, DPIA | Med | Per release | Slack `#sec` |
| **Legal / Compliance** | DPDP, GST, RBI, PCI | Med | Per release | Email + Notion |
| **Finance** | Unit econ, payout reconciliation | Med | Monthly | Looker |
| **Customer support** | Tickets, refunds, escalations | Low | Per ticket | Zendesk |
| **Marketing** | SEO, llms.txt, growth | Med | Weekly | Slack `#mkt` |
| **Priests / Vendors** (supply side) | Earnings, KYC, payouts | Med | Monthly | In-app |
| **Users** (demand side) | Booking, payments, trust | High | Per session | In-app + NPS |

---

## 0b. Year 1 OKRs

### Q1 (PRs 0.5–4): Foundation
- **O1**: Ship hardened auth, zero-trust middleware, RBAC+ABAC. **KR1**: 100% of routes guarded, all 6 roles enforced, 0 P0 security findings.
- **O2**: Establish AEO contract on all 1,265 SEO pages. **KR1**: Speakable JSON-LD on 100% pages, Lighthouse SEO ≥95.
- **O3**: Replace 75KB of duplicated priest search UI. **KR1**: 1 unified `/priests` route, 0 duplication, 1,000+ entities seeded.

### Q2 (PRs 5–9): Dashboards
- **O1**: 60 dashboard sections live across 6 roles. **KR1**: 100% data-bound, 85%+ test coverage, Lighthouse Perf ≥90.
- **O2**: Persistent sidebar + drawer. **KR1**: p95 dashboard nav <100ms, mobile drawer Lighthouse a11y = 100.
- **O3**: Auth UX 100% real. **KR1**: 0 "under development" stubs in auth, 0 dead routes.

### Q3 (PRs 10–12): Directory + Search + Billing
- **O1**: 100k+ directory entities seeded, 12 archetypes live. **KR1**: Top 20k pages pre-rendered, search p95 <300ms, AEO on all archetypes.
- **O2**: Full Razorpay integration with double-entry ledger. **KR1**: End-to-end book→pay→invoice→payout works, TDS 194-O + GST e-invoice (IRN) correct, ledger invariant holds.
- **O3**: Search with vector+BM25 hybrid. **KR1**: NDCG@10 ≥0.85 on eval set of 500 queries.

### Q4 (PRs 13–15): Comm + i18n + AI + Hardening
- **O1**: Hindi mirror of 1,265 pages with human-reviewed translations. **KR1**: 100% pages translated, hreflang complete, 90%+ reviewer approval rate.
- **O2**: AI surfaces live (read-only MCP, llms.txt, plugin). **KR1**: 0 P0 security findings, MCP responds in <500ms, 5+ assistants can answer queries.
- **O3**: Production-grade observability, FinOps, DR. **KR1**: All 6 SLOs within budget, DR drill passes RTO<30m RPO<5m, Lighthouse ≥95, 0 P0/P1 pentest findings.

---

## 0c. North Star Metrics + Anti-Goals

### North Star Metric (single)
**Weekly completed bookings** (one booking per priest per customer per week). This is downstream of everything: discovery (search), trust (reviews), conversion (UX), reliability (no-shows), supply (priest availability).

### Supporting Metrics
- **Activation**: % of new users who complete a booking within 14 days
- **Retention**: D30, D90 cohort retention
- **Supply quality**: avg priest trust score, % with KYC pass
- **Unit econ**: gross margin per booking, LTV/CAC, payback
- **Reliability**: booking completion rate, no-show rate

### Anti-Goals (we will NOT optimize for)
- DAU at the cost of booking quality
- SEO traffic that doesn't convert to a booking
- Vendor lock-in (we keep ports open)
- Magic-mode AI features (no LLM-driven write actions at launch)
- 100+ language support (hi-IN only at launch, more in Year 2)
- Native mobile apps (PWA at launch, native in Year 2)
- Multi-region active-active (single region primary, multi-region standby)

---

# PART II — ARCHITECTURE

## 1. 12-Layer Architecture

```
L0  Edge/Network         CDN, WAF, DDoS, rate-limit, bot mgmt
L1  Presentation         Next.js (RSC + Client), PWA, server actions
L2  Routing              proxy.ts, route metadata, layout policies
L3  Application          usecases, server actions, jobs, schedulers
L4  Domain               pure entities, value objects, invariants
L5  Ports                repository, gateway, queue, storage, search, geo
L6  Adapters             Postgres (Drizzle), Redis, S3, OpenSearch, SMTP, SMS, WhatsApp, Payments
L7  Cross-cutting        auth, rbac/abac, audit, finops, telemetry, feature-flags, secrets
L8  API surface          REST (read), Server Actions (write), MCP (AI), tRPC-like (internal)
L9  Realtime             SSE, WebSocket, presence
L10 Background           outbox, cron, workers, queue, scheduler
L11 Observability        OTel, logs, metrics, traces, SLOs, alerts
L12 Data/ML              warehouse, ETL, embeddings, recommendations, AEO
```

---

## 2. Hexagonal Architecture (detail)

```
src/
  domain/                    # pure, no I/O
    priest/
      entities.ts            # Priest, Availability, Review
      value-objects.ts       # Geo, TrustScore, Money
      invariants.ts          # rating 1-5, geo in India, etc.
    booking/
      state-machine.ts
      invariants.ts
    billing/
      ledger.ts              # post(entry), trial-balance
      invoice.ts
  application/               # use cases, orchestrate domain + ports
    priest/
      usecases/
        search-priests.ts
        book-priest.ts
        review-priest.ts
    booking/
      usecases/
        create-booking.ts
        cancel-booking.ts
        reschedule-booking.ts
    billing/
      usecases/
        pay-booking.ts
        refund-booking.ts
        payout-priest.ts
  ports/                     # interfaces, no impl
    repository.ts
    payment-gateway.ts
    notification-gateway.ts
    search-engine.ts
    geo-lookup.ts
    storage.ts
  adapters/                  # concrete impls
    postgres/
      schema/                # Drizzle
      repositories/          # impl of ports/repository
    razorpay/
      payment-gateway.ts
    smtp/
      notification-gateway.ts
    opensearch/
      search-engine.ts
    osm/
      geo-lookup.ts
    s3/
      storage.ts
  infra/                     # cross-cutting
    auth/                    # session, csrf, mfa
    rbac/                    # policy engine
    audit/                   # audit log
    finops/                  # cost record, allocation
    telemetry/               # otel, logs
    feature-flags/
    secrets/
    ratelimit/
    idempotency/
    outbox/
  ui/                        # Next.js app
    app/                     # routes
    components/              # presentational
    features/                # per-feature containers
    lib/                     # client-side helpers
  workers/                   # background jobs
    outbox-dispatcher.ts
    cron-runner.ts
    webhook-receiver.ts
  api/                       # REST endpoints (read)
    v1/
    mcp/                     # AI surface
  tests/
    unit/
    integration/
    e2e/
    load/
    chaos/
    visual/
    security/
```

**Dependency rule:** `domain` ← `application` ← `ports` ← `adapters`. `ui` and `workers` depend on `application`. `domain` depends on nothing.

---

## 3. C4 Model

### C1 — Context
Navdhya is a platform that:
- Serves **Users** (households booking pujas)
- Serves **Priests** (supply)
- Serves **Vendors** (samagri, supplies)
- Serves **Managers / Admins** (operations)
- Integrates with **Razorpay** (payments), **Razorpay Token HQ** (cards), **ClearTax/IRIS** (GST), **OpenStreetMap** (geo), **OpenSearch** (search), **Postmark/SES** (email), **Gupshup** (WhatsApp), **Pinelabs** (SMS), **S3** (storage), **OpenAI/Anthropic/DeepL** (translation)
- Surfaces to **AI assistants** (ChatGPT, Claude, Perplexity) via MCP

### C2 — Container
```
[Web App]  Next.js (Vercel or self-hosted)
[API]      Same Next.js, /api/v1/* (REST reads), /api/mcp (AI)
[Workers]  Node.js BullMQ workers (SQS-compatible)
[DB]       Postgres 16 (Neon primary + read replica)
[Cache]    Redis 7 (sessions, rate limits, hot keys)
[Search]   OpenSearch 2.x (1 node MVP, 3-node prod)
[Queue]    BullMQ on Redis (MVP), SQS at scale
[Storage]  S3 (media, exports, backups)
[CDN]      CloudFront (India + global PoPs)
[WAF]      AWS WAF or Cloudflare
[Observability] OTel Collector → Tempo + Loki + Mimir (Grafana)
```

### C3 — Component
For each module, components are: routes (RSC + client), use cases, ports, adapters, repos, schemas, migrations, tests.

### C4 — Code
Per-module structure in `src/`. Hexagonal.

---

## 4. Data Flow Examples (textual)

### F1 — User searches for a priest
```
Browser → CDN (cached? no) → proxy.ts (auth, rate limit, ctx headers)
       → RSC render /priests?q=satyanarayan&city=delhi
       → app/priests/page.tsx (server component)
       → withAuth + withTenant
       → search_priests usecase
       → PriestSearchRepo.search(filters, page)
       → Postgres: SELECT ... FROM dir_priest WHERE ... (RLS enforced)
       → OpenSearch: hybrid BM25+vector
       → merge + score + paginate
       → enrich with AEO speakable
       → <PriestList> RSC payload
       → HTML response
       → CDN cache 5 min (anonymous), no-cache (logged-in)
```

### F2 — User books a priest and pays
```
Browser → /priests/[slug] → "Book"
       → /book/new (server action) — Idempotency-Key header required
       → withAuth (must be User) → withTenant
       → create_booking usecase
       → withTransaction:
            - book.booking INSERT
            - book.booking_event INSERT (state: DRAFT)
            - bill.quote INSERT
            - job.outbox INSERT (event: booking.created)
            - commits
       → outbox-dispatcher (worker):
            - LISTEN/NOTIFY picks up event
            - publishes to in-process bus
            - subscribers: search-index (reindex priest), notification (send confirm email)
       → returns quote_id to client
       → /book/[quote_id]/pay (server action) — Idempotency-Key required
       → pay_booking usecase
       → withTransaction:
            - bill.ledger.post([Dr AR, Cr Revenue:Puja])
            - bill.invoice INSERT
            - bill.payment PENDING (denormalized)
            - job.outbox INSERT
       → Razorpay Orders.create()
       → returns order_id + checkout_url
       → browser → Razorpay hosted checkout
       → Razorpay webhook → /api/v1/webhooks/razorpay (signature verified, idempotent)
       → withTransaction:
            - bill.payment CAPTURED
            - bill.invoice PAID
            - bill.ledger.post([Dr Cash, Cr AR])
            - bill.ledger.post platform fee
            - job.outbox INSERT
       → notify user, priest, vendor
       → GST e-invoice job (PR 12):
            - GSP call → IRN + signed QR
            - bill.invoice IRN stored
```

---

## 5. Network Topology

```
Internet
  └─ Cloudflare (DNS, WAF, DDoS, bot mgmt)
      └─ CloudFront CDN
          └─ ALB (TLS termination, mTLS for internal)
              ├─ Vercel (Next.js, edge functions, ISR)
              ├─ Workers tier (BullMQ on ECS Fargate)
              └─ Lambda (event handlers, cron)
                  └─ VPC
                      ├─ RDS Postgres 16 (Multi-AZ, RLS)
                      ├─ ElastiCache Redis 7 (cluster mode)
                      ├─ OpenSearch (3 nodes, 2 AZs)
                      └─ S3 (encrypted, lifecycle policy)
```

All internal traffic on private subnets. Egress via NAT. Secrets in AWS Secrets Manager. KMS-managed keys.

---

## 6. Deployment Topology

- **Primary region**: `ap-south-1` (Mumbai) for prod. Lowest latency to 90% of users.
- **DR region**: `ap-southeast-1` (Singapore) warm standby. RTO 30m, RPO 5m via Postgres logical replication + S3 cross-region replication.
- **Staging**: `ap-south-1` separate account, scaled down.
- **Dev**: Docker Compose locally; Codespaces for cloud dev.
- **Preview**: per-PR ephemeral Vercel preview with seeded SQLite.

**Deployment strategy:**
- Web: Vercel (Next.js 16 native, ISR, edge functions)
- API: Vercel serverless for read; Fargate for long-running workers
- DB: RDS Multi-AZ + read replica
- Search: OpenSearch 3-node cluster
- Storage: S3 standard + IA + Glacier lifecycle
- CDN: CloudFront
- Migration: Drizzle Kit with `expand-and-contract` pattern (3-phase: add column → dual-write → drop)

**Canary**: 1% → 10% → 50% → 100% over 30 min, watched by automated SLO guard.

**Feature flags**: OpenFeature (Flagd in dev, LaunchDarkly in prod). Every new feature behind a flag.

---

## 7. Multi-Region Strategy

| Tier | Data | Latency target | Strategy |
|---|---|---|---|
| **Hot** | Postgres primary | <50ms to Mumbai | ap-south-1, single primary |
| **Warm** | Postgres replica + Redis replica | <100ms to Delhi/Bangalore | read replica in same region |
| **Standby** | DR region | <200ms | ap-southeast-1, warm standby |
| **Edge** | Static assets | <30ms global | CloudFront PoPs (Mumbai, Delhi, Bangalore, Chennai, Hyderabad) |

**Active-active** deferred to Year 2 (post-10M MAU). Year 1: active-passive with automated failover.

---

## 8. Capacity Planning

### Year-1 targets (locked)

| Metric | Target | Headroom |
|---|---|---|
| MAU | 5M | 3x (15M) |
| Daily bookings | 50,000 | 5x (250k) |
| Concurrent users (peak) | 100,000 | 2x (200k) |
| API RPS (peak) | 5,000 | 3x (15k) |
| Search QPS | 2,000 | 4x (8k) |
| Notifications/day | 1M | 3x (3M) |
| Storage (DB) | 500 GB | 2x (1 TB) |
| Storage (media) | 5 TB | 3x (15 TB) |
| Bandwidth (egress) | 50 TB/mo | 2x (100 TB) |

### Per-service SLOs

| Service | p50 | p95 | p99 | Error rate |
|---|---|---|---|---|
| Web (HTML) | 200ms | 800ms | 2s | <0.1% |
| API (read) | 50ms | 200ms | 500ms | <0.1% |
| API (write) | 200ms | 1s | 3s | <0.5% |
| Search | 80ms | 300ms | 800ms | <0.1% |
| Booking | 300ms | 1.5s | 3s | <0.5% |
| Payment | 500ms | 2s | 5s | <0.05% (5xx) |
| Notification | 1s | 5s | 30s | <1% |

### Capacity calculations

- **Web**: 5,000 RPS × 800ms p95 = 4,000 concurrent requests → 40 vCPU on Vercel (8GB RAM each)
- **API**: 5,000 RPS × 50ms = 250 vCPU-seconds/sec → 25 vCPU at 50% utilization
- **Postgres**: 5,000 QPS × 5ms = 25,000 IOPS → RDS `db.r6g.4xlarge` (30k IOPS provisioned)
- **Redis**: 5,000 QPS × 1ms = 5k ops/sec → `cache.r6g.large`
- **OpenSearch**: 2,000 QPS × 100ms = 200 vCPU → 3× `m6g.4xlarge.search` (8 vCPU each)
- **Workers**: 50k bookings/day × 30s avg = 17 jobs/sec → 10 Fargate tasks (2 vCPU, 4GB)
- **Notifications**: 1M/day = 12/sec, but bursty → 50-task queue, 10 workers
- **Storage**: 500 GB DB + 5 TB media → S3 + RDS gp3

---

## 9. Cost Projections (Year 1)

### Monthly cost (steady state, USD)

| Service | Spec | Unit cost | Qty | Monthly |
|---|---|---|---|---|
| Vercel Pro + Enterprise | per-seat + usage | $250 + usage | 1 + usage | $2,000 |
| RDS Postgres | db.r6g.4xlarge Multi-AZ | $2,400 | 1 | $2,400 |
| RDS Read Replica | db.r6g.2xlarge | $1,200 | 1 | $1,200 |
| ElastiCache Redis | cache.r6g.large × 3 | $400 | 3 | $1,200 |
| OpenSearch | m6g.4xlarge.search × 3 | $1,500 | 3 | $4,500 |
| Fargate Workers | 2 vCPU, 4GB × 10 | $70 | 10 | $700 |
| Lambda | 10M invocations | $0.20/M | 10 | $2 |
| S3 Standard | 5 TB | $0.023/GB | 5,000 | $115 |
| S3 IA | 10 TB | $0.0125/GB | 10,000 | $125 |
| CloudFront | 50 TB egress | $0.085/GB | 50,000 | $4,250 |
| Cloudflare Pro | per domain | $20 | 5 | $100 |
| Razorpay | 2% per txn | varies | — | $10,000 @ 50k/mo |
| ClearTax (GSP) | ₹0.50/invoice | ₹40 | 50,000 | $48 |
| Postmark | 100k emails | $50 | 1 | $50 |
| Pinelabs (SMS) | ₹0.20/SMS | ₹20 | 100,000 | $24 |
| Gupshup (WhatsApp) | ₹0.50/msg | ₹50 | 100,000 | $60 |
| Sentry | Team | $26 | 1 | $26 |
| PagerDuty | Business | $41 | 1 | $41 |
| Grafana Cloud | Pro | $55 | 1 | $55 |
| GitHub | Enterprise | $21/user | 5 | $105 |
| OpenAI (translations) | per call | varies | — | $200 |
| DeepL (translations) | Pro | $60 | 1 | $60 |
| Backups (S3) | 1 TB | $25 | 1 | $25 |
| KMS | 10k ops | $1 | 1 | $1 |
| Secrets Manager | 100 secrets | $5 | 1 | $5 |
| **Total infra** | | | | **~$29,290/mo** |
| **+ Engineering** | 3 × $15k/mo loaded | | | $45,000 |
| **+ Tools/SAAS** | | | | $3,000 |
| **Grand total** | | | | **~$77,290/mo** |

At 50k bookings/mo @ ₹2,500 AOV = ₹12.5 Cr GMV = $1.5M GMV.
- 2% platform fee = $30,000 revenue
- 2% Razorpay fee = $30,000
- **Net contribution**: $30,000 - $29,290 = **$710/mo** (Year 1 burn).
- **Path to break-even**: scale to 100k bookings/mo (2x).

### Cost optimization levers (ranked)
1. Reserved capacity (RDS, OpenSearch): -30% in 12 months
2. Right-sizing: weekly, save ~10% steady-state
3. S3 lifecycle: ~$200/mo
4. CDN caching: reduce origin by 60% → -$2,500/mo
5. Worker concurrency tuning: -$300/mo
6. Log retention: hot 7d, warm 30d, cold 1y → -$100/mo

---

## 10. Architecture Decision Records (ADRs)

### ADR-001: PostgreSQL as the source of truth
**Status:** Accepted. **Date:** 2026-06-04.
**Context:** Need ACID, RLS, JSON, FTS, vector, partitioning, mature.
**Decision:** Postgres 16 + Drizzle ORM. JSON only for SEO read cache.
**Alternatives:** MySQL, MongoDB, DynamoDB, Cloud Spanner.
**Consequences:** Slight learning curve for vector ops (pgvector), operational overhead of self-managed backups.

### ADR-002: Multi-tenant by shared schema with RLS
**Status:** Accepted.
**Decision:** Shared schema, `tenant_id` on every row, Postgres RLS, `app.tenant_id` session var.
**Alternatives:** Schema-per-tenant (heavy ops), DB-per-tenant (heavy cost).
**Consequences:** RLS bypass risk if app forgets to set var → enforced by `withTenant()` wrapper; tested in PR 10.

### ADR-003: Hexagonal architecture
**Status:** Accepted.
**Decision:** domain → application → ports → adapters. Dependency rule enforced by ESLint `import/no-restricted-paths`.
**Consequences:** More files, slower first feature; long-term substitutability (e.g., swap Razorpay for Stripe in 1 PR).

### ADR-004: Drizzle over Prisma
**Status:** Accepted.
**Decision:** Drizzle for type-safe SQL, raw control, edge-friendly, no query engine.
**Alternatives:** Prisma (heavier, generated client, less control), Kysely (no schema), TypeORM (legacy).
**Consequences:** No built-in migration UI (use Drizzle Kit CLI); manual schema files.

### ADR-005: OpenSearch for search
**Status:** Accepted.
**Decision:** OpenSearch 2.x with hybrid BM25 + vector (knn).
**Alternatives:** Meilisearch (limited), Typesense (no vector), Algolia (cost), pg-only (scale ceiling).
**Consequences:** Extra infra ($4.5k/mo). At 10k entities, defer to pg-only FTS.

### ADR-006: Razorpay as PSP
**Status:** Accepted.
**Decision:** Razorpay for cards, UPI, eNACH, UPI AutoPay, payouts. Razorpay Token HQ for RBI tokenization.
**Alternatives:** PayU, Cashfree, Stripe (no UPI at parity), Juspay.
**Consequences:** Vendor lock-in mitigated by `PaymentGateway` port.

### ADR-007: Read-only MCP at launch
**Status:** Accepted.
**Decision:** MCP server exposes only read tools (`search_priests`, `get_panchang`, `get_horoscope`, `get_festival`).
**Consequences:** No booking via AI in v1; less auth surface; faster ship.

### ADR-008: Full ledger, not just payment records
**Status:** Accepted.
**Decision:** Double-entry ledger (`bill_ledger_entry`) is the source of truth; `bill_payment` is a denormalized view.
**Consequences:** Adds 1 PR (11.5) and 2 weeks; pays off in auditability, reconciliation, multi-currency readiness.

### ADR-009: AEO Speakable on every page
**Status:** Accepted.
**Decision:** Every public page renders a `Speakable` JSON-LD block and a `<div data-speakable>`. ≤280 chars. Reviewed by editor.
**Consequences:** 1 PR (0.5) to add to 1,265 pages; 1 PR (14a) for hi-IN translation.

### ADR-010: Feature flags everywhere
**Status:** Accepted.
**Decision:** OpenFeature (Flagd locally, LaunchDarkly prod). Every new feature behind a flag; no exceptions.
**Consequences:** 1 more concept for engineers; safer canary, instant kill switch.

### ADR-011: Idempotency keys on every write
**Status:** Accepted.
**Decision:** `Idempotency-Key` header on every write API and server action. TTL 24h.
**Consequences:** Slight overhead, big win for retries and webhook safety.

### ADR-012: DORA-grade CI/CD
**Status:** Accepted.
**Decision:** Trunk-based, 1 PR per change, ephemeral preview env, canary 1→10→50→100% in 30 min, automated SLO guard.
**Consequences:** Requires mature infra team; pays off in MTTR <1h.

---

## 11. Cross-Cutting Controls (apply to every module)

(See v9 plan, sections 3 and 15-31. Same controls; this v10 expands each.)

---

# PART III — SECURITY, COMPLIANCE, OBSERVABILITY (expanded)

## 12. PII Classification

| Class | Examples | At-rest | In-transit | Access | Retention |
|---|---|---|---|---|---|
| **Public** | marketing copy, public reviews, public profiles | plain | TLS | everyone | indefinite |
| **Internal** | aggregate metrics, internal docs | plain | TLS | employee | 7y |
| **Confidential** | PII (name, phone, email, address, geo, KYC docs) | AES-256 (KMS) | TLS 1.3 | least-privilege, audit | 7y post-account-deletion |
| **Regulated** | PAN, Aadhaar (last 4 only), bank account, IFSC, payment tokens | AES-256 + tokenization where possible | mTLS | privileged roles + dual-control | 7y (RBI), 7y (IT Act) |
| **Secret** | API keys, signing keys, OAuth secrets, DB passwords | KMS-encrypted | mTLS | service identity only | rotated 90d |

**Aadhaar policy:** store only last 4 digits + reference token (UIDAI-issued). Never store full Aadhaar. Tokenization is mandatory.

**PAN policy:** store only last 4 + hash(first 5 + last 4) for duplicate detection. Full PAN only for KYC verification, then discarded.

**Bank account policy:** never store full account number in plaintext. Use Razorpay's fund account + penny-drop verification. Keep only last 4.

---

## 13. Data Retention Matrix

| Table | Hot | Warm | Cold | PII | Delete SLA | Notes |
|---|---|---|---|---|---|---|
| `iam_account` | 7y | 7y | 7y | yes | 30d DPDP | Soft delete, then hard |
| `iam_session` | 30d | 90d | — | no | immediate | Auto-expire |
| `iam_credential` | until revoke | 1y | 1y | hash only | immediate | Argon2id |
| `iam_audit` | 90d | 1y | 7y | no | 7y | Immutable, S3 archive |
| `book_booking` | 7y | 7y | 7y | yes | 7y | Tax retention |
| `book_address` | 7y | 7y | 7y | yes | 7y | Tax retention |
| `bill_invoice` | 7y | 7y | 7y | yes | 7y | Tax retention |
| `bill_payment` | 7y | 7y | 7y | yes | 7y | Tax retention |
| `bill_ledger_entry` | 7y | 7y | 7y | no | 7y | Append-only |
| `comm_email` | 90d | 1y | 7y | yes | 1y | Compliance |
| `comm_sms` | 30d | 90d | 7y | yes | 90d | DLT retention |
| `comm_message` (chat) | 1y | 3y | 7y | yes | 1y | Per policy |
| `dir_review` | indefinite | indefinite | indefinite | yes | 1y post-deletion | Anonymize author |
| `dir_media` | 1y | 3y | — | no | 1y post-owner-delete | S3 lifecycle |
| `cms_content` | indefinite | indefinite | indefinite | no | 7y | Versioned |
| `cms_revision` | 1y | 7y | 7y | no | 1y | Cleanup old |
| `iam_consent` | 7y | 7y | 7y | yes | 7y | Evidence for DPDP |
| `ops_audit` | 1y | 7y | 7y | no | 7y | |
| `sec_threat_event` | 90d | 1y | 7y | no | 7y | SIEM |

**Hot** = Postgres primary. **Warm** = S3 Standard. **Cold** = S3 Glacier. **PII** = encrypted with KMS. **Delete SLA** = time from DPDP/GDPR request to completion.

---

## 14. Encryption & Key Rotation

| Layer | Algorithm | Key mgmt | Rotation |
|---|---|---|---|
| TLS 1.3 (in transit) | AES-256-GCM, ChaCha20 | ACM (cert rotation 90d) | automated |
| RDS (at rest) | AES-256 | KMS | annual |
| S3 (at rest) | AES-256 | KMS | annual |
| Redis (at rest) | AES-256 | KMS | annual |
| App secrets | env → AWS Secrets Manager | KMS | 90d |
| Payment tokens | Razorpay Token HQ | PSP | n/a (RBI) |
| Field-level PII | envelope encryption (DEK per record, KMS CMK) | KMS | annual CMK, per-record DEK |
| JWT signing | RS256 | private key in Secrets Manager | 90d |
| Webhook signing | HMAC-SHA256 | shared secret per gateway | 180d |
| OTel collector | mTLS | cert manager | 90d |

**Key rotation runbook** is owned by Security; quarterly rotation drill, automated via Lambda.

---

## 15. Threat Model (STRIDE summary)

### Spoofing
- **T1** Session hijack → mitigated by httpOnly, secure, sameSite=lax, fingerprint binding
- **T2** OAuth flow interception → mitigated by PKCE, state
- **T3** Webhook spoofing → mitigated by HMAC signature verification per gateway

### Tampering
- **T4** SQL injection → mitigated by parameterized queries (Drizzle), no raw SQL
- **T5** XSS → mitigated by React auto-escape + CSP `script-src 'self' 'nonce-...'` + `Trusted Types`
- **T6** Parameter pollution → mitigated by Zod validation
- **T7** Privilege escalation via role → mitigated by ABAC, server-side checks, four-layer guards

### Repudiation
- **T8** "I didn't do that" → mitigated by append-only `iam_audit`, signed event log, S3 archive
- **T9** Booking denial → mitigated by `book_booking_event` state log

### Information Disclosure
- **T10** IDOR → mitigated by `withTenant()` + perm checks
- **T11** SSRF → mitigated by URL allowlist for outbound HTTP, no user input in fetch
- **T12** PII leak in logs → mitigated by `pii-redact` filter in pino
- **T13** CSPP leakage → mitigated by `Cache-Control: private` on protected responses

### Denial of Service
- **T14** L7 DDoS → mitigated by Cloudflare WAF + rate limit
- **T15** Expensive query → mitigated by query timeout, statement timeout, `pg_stat_statements` monitoring
- **T16** Booking flood → mitigated by idempotency + rate limit per account
- **T17** Search abuse → mitigated by query rate limit + cost-based quota

### Elevation of Privilege
- **T18** Vertical escalation → mitigated by 4-layer guard, ABAC
- **T19** Tenant breakout → mitigated by RLS, integration test
- **T20** Admin compromise → mitigated by MFA mandatory, IP allowlist, time-bound sessions

**Per-module threat model** in `docs/threat-models/<module>.md` (created as part of each PR).

---

## 16. Compliance Control Mapping

| Control area | Framework | Reference | Implementation | Evidence |
|---|---|---|---|---|
| Consent collection | DPDP §6, GDPR Art.6/7 | `iam_consent`, `sec_consent_proof` | PR 3, 11 | `consent_proof` records, audit log |
| Right to access | DPDP §11, GDPR Art.15 | `sec_data_export` | PR 11 | Export job, signed URL |
| Right to erasure | DPDP §12, GDPR Art.17 | `sec_data_delete` | PR 11 | Soft → hard delete, evidence |
| Right to correction | DPDP §13, GDPR Art.16 | profile edit | PR 4 | audit log |
| Data minimization | DPDP §4, GDPR Art.5(1)(c) | Zod schemas | all PRs | schema validation |
| Purpose limitation | DPDP §4 | purpose binding | PR 11 | `iam_consent.purpose` |
| Storage limitation | DPDP §4 | retention matrix | PR 11, 15 | `retention` cron |
| Breach notification | DPDP §8, IT Act 70B | `sec_breach` | PR 11 | 72h notify workflow |
| Cross-border transfer | DPDP §16 | region pinning | PR 10 | `tenant_region` |
| Children's data | DPDP §9, COPPA | age gate | PR 11 | 13+ gate |
| Significant data fiduciary | DPDP §10 | DPIA | PR 11, 15 | `sec_dpia` |
| Aadhaar handling | Aadhaar Act §29 | tokenization | PR 12 | only last 4 + token |
| PAN handling | IT Act 139B | masking | PR 12 | last 4 + hash |
| GST e-invoice | GST §31, e-invoice rules | `bill_invoice.irn` | PR 12 | IRN + QR |
| TDS 194-O | Income Tax Act | `bill_tax_form` | PR 12 | auto-deduct |
| 80G certificate | Income Tax Act | `bill_tax_form` | PR 12 | certificate PDF |
| RBI tokenization | RBI 2021-22 | Razorpay Token HQ | PR 12 | token-only storage |
| PCI DSS | SAQ-A scope | no PAN storage | PR 12 | SAQ-A report |
| Card data handling | PCI 3.4 | tokenization | PR 12 | no PAN in logs |
| Refund policy | Consumer Protection (E-Com) §4(4) | `price.refund_policy` | PR 11 | policy page |
| Cancellation | Consumer Protection §4(7) | `book.cancellation` | PR 11 | state machine |
| Grievance officer | Consumer Protection §4(11) | `sec_legal_doc` | PR 11 | contact + SLA |
| Originator traceability | IT Act 79A | `iam_audit` | PR 3 | audit log |
| CERT-In directions | CERT-In 2022 | `iam_audit` (6mo) | PR 15 | 6-month retention |
| A11y | WCAG 2.2 AA | UI components | PR 0.5, 4 | axe-core CI |
| Content takedown | IT Act 79, MeitY Rules | `cms_takedown` | PR 11 | workflow |
| SOC 2 Type II | (roadmap) | controls above | PR 15 | audit-ready |
| ISO 27001 | (roadmap) | controls above | PR 15 | ISMS in place |

---

## 17. Observability Contract (every module ships with)

- **Logs:** pino JSON with `trace_id`, `span_id`, `tenant_id`, `account_id`, `request_id`, `route`, `latency_ms`. PII redacted via `pii-redact` filter.
- **Metrics:** Prometheus-format. Mandatory per module:
  - `requests_total{module,route,status}`
  - `latency_ms{module,route,quantile}` (t-digest)
  - `errors_total{module,code}`
  - `outbox_lag_ms`
  - `db_pool_*` (active, idle, waiting)
  - `cache_hit_ratio{module}`
  - `rate_limit_drop_total{module,route}`
  - `feature_flag_evaluations_total{flag,variant}`
  - `audit_writes_total{module,action}`
- **Traces:** OTel SDK auto-instr. for Next.js, Postgres (`pg`), Redis, fetch, DNS. Custom spans: `usecase.<name>`, `repo.<name>`, `gateway.<name>`, `outbox.dispatch`.
- **Profiles:** Pyroscope for CPU/memory on workers (bookings, payouts, outbox).
- **RUM:** Web Vitals sent to OTel, sampled at 10%, all paid users 100%.
- **Dashboards:** Grafana JSONs in `infra/grafana/<module>.json` (per module).
- **Alerts:** Alert rules in `infra/alerts/<module>.yaml`.
- **Status page:** auto-derived from `ops_alert` table.

---

## 18. SLO Catalog (Year 1, locked)

| SLI | Service | Target | Window | Error budget | Burn-rate alert | Owner |
|---|---|---|---|---|---|---|
| Availability | All | 99.95% | 30d | 0.05% | 2% in 1h, 5% in 6h | SRE |
| Latency p75 | Web HTML | ≤ 2.0s | 28d | n/a | 1.5x for 30m | SRE |
| Latency p95 | API read | ≤ 200ms | 28d | n/a | 1.5x for 30m | SRE |
| Latency p95 | API write | ≤ 1s | 28d | n/a | 1.5x for 30m | SRE |
| Latency p95 | Search | ≤ 300ms | 28d | n/a | 1.5x for 30m | SRE |
| Error rate | All | < 0.1% (5xx) | 30d | 0.1% | 2% in 1h | SRE |
| Booking completion | `book` | ≥ 99.5% | 30d | 0.5% | 1% in 30m | eng-C |
| Payment success | `bill` | ≥ 99% | 30d | 1% | 2% in 30m | eng-A |
| Notification delivery | `comm` | ≥ 99% in 60s | 30d | 1% | 2% in 1h | eng-C |
| AEO answerable | pages | ≥ 95% | 28d | 5% | n/a | eng-C |
| Lighthouse perf | `/`, `/priests`, `/priests/[slug]`, `/temples/[slug]` | ≥ 95 | 28d | n/a | < 90 for 7d | SRE |
| WCAG AA | all pages | 0 critical | per release | 0 | any critical | eng-B |

**Error budget policy:**
- 50% burn: dev team investigates
- 75% burn: feature freeze, all hands on reliability
- 100% burn: feature freeze, exec escalation, postmortem required

---

## 19. On-Call Runbook Templates (top 20)

Format: Alert → Symptom → Triage (5 min) → Mitigation (15 min) → Rollback → Postmortem.

### RB-001: API 5xx spike
- **Alert:** `errors_total{status=~"5.."}` > 1% for 5m
- **Triage:** check recent deploy, check upstream deps
- **Mitigation:** rollback last release, freeze queue, drain traffic
- **Rollback:** `vercel rollback` or `kubectl rollout undo`
- **Postmortem:** blameless template in `docs/runbooks/postmortem.md`

### RB-002: DB connection pool exhausted
- **Alert:** `db_pool_waiting > 10` for 2m
- **Triage:** check slow queries via `pg_stat_statements`
- **Mitigation:** kill long queries, scale RDS, add read replica
- **Rollback:** reduce RPS via CDN cache bust prevention

### RB-003: Outbox lag > 5 min
- **Alert:** `outbox_lag_ms > 300000` for 5m
- **Triage:** check worker health, check dead-letter queue
- **Mitigation:** scale workers, replay outbox
- **Rollback:** pause non-critical subscribers

### RB-004: Search p95 > 800ms
- **Alert:** `search_p95 > 0.8` for 10m
- **Triage:** check OpenSearch cluster health, check index size
- **Mitigation:** increase replicas, add cache layer
- **Rollback:** disable vector search, fall back to BM25

### RB-005: Payment gateway 5xx
- **Alert:** `razorpay_5xx > 0.5%` for 2m
- **Triage:** check Razorpay status page
- **Mitigation:** enable backup gateway, queue payments
- **Rollback:** retry queue will replay

### RB-006: GST e-invoice job failure
- **Alert:** `gsp_irn_failures_total > 5%` for 5m
- **Triage:** check GSP status, check rate limits
- **Mitigation:** retry with backoff, fallback to alternate GSP
- **Rollback:** mark invoices as `pending_irn` and retry hourly

### RB-007: SMS/WhatsApp delivery failure
- **Alert:** `comm_delivery_rate < 0.95` for 5m
- **Triage:** check provider status, check DLT template
- **Mitigation:** failover to backup provider
- **Rollback:** n/a

### RB-008: Aadhaar/PAN verification failure
- **Alert:** `kyp_verify_failures > 2%` for 10m
- **Triage:** check KYC provider, check rate limit
- **Mitigation:** failover, queue
- **Rollback:** manual review queue

### RB-009: Lighthouse perf regression
- **Alert:** Lighthouse < 90 on `/` for 7d
- **Triage:** check bundle size, image sizes, third-party
- **Mitigation:** revert offending PR, lazy-load more
- **Rollback:** git revert

### RB-010: DLT template rejected
- **Alert:** `dlt_reject_total` > 0
- **Triage:** check template content
- **Mitigation:** re-submit with fix
- **Rollback:** n/a

### RB-011: Tenant RLS bypass attempt
- **Alert:** `rls_blocked_total > 0`
- **Triage:** investigate, log, lock account
- **Mitigation:** rotate session, force re-auth
- **Rollback:** n/a

### RB-012: DPDP rights request SLA breach
- **Alert:** `rights_request_overdue > 0`
- **Triage:** check queue, escalate
- **Mitigation:** assign on-call DPO
- **Rollback:** n/a

### RB-013: Cost anomaly (>3σ)
- **Alert:** `cost_anomaly_total > 0`
- **Triage:** check new resources
- **Mitigation:** tag or remove
- **Rollback:** n/a

### RB-014: CDN cache miss > 40%
- **Alert:** `cdn_miss_ratio > 0.4` for 15m
- **Triage:** check origin health
- **Mitigation:** scale origin
- **Rollback:** n/a

### RB-015: Worker pool exhausted
- **Alert:** `worker_queue_depth > 10000` for 5m
- **Triage:** check job runs
- **Mitigation:** scale workers
- **Rollback:** pause non-critical jobs

### RB-016: Webhook signature failure
- **Alert:** `webhook_sig_failures > 0`
- **Triage:** check provider, check secret rotation
- **Mitigation:** rotate secret
- **Rollback:** n/a

### RB-017: L7 DDoS detected
- **Alert:** Cloudflare DDoS alert
- **Triage:** identify pattern
- **Mitigation:** enable Cloudflare "Under Attack" mode
- **Rollback:** n/a

### RB-018: IRN e-invoice rate limit
- **Alert:** `gsp_rate_limit > 0`
- **Triage:** backoff
- **Mitigation:** queue and retry
- **Rollback:** n/a

### RB-019: 194-O TDS miscalculation
- **Alert:** `tds_audit_mismatch > 0`
- **Triage:** check calculation
- **Mitigation:** halt payouts, fix, reissue
- **Rollback:** n/a

### RB-020: Backup verification failure
- **Alert:** `backup_verify_fail > 0`
- **Triage:** check backup job
- **Mitigation:** re-run
- **Rollback:** n/a

---

## 20. Feature Flag Strategy

- **System:** OpenFeature SDK; Flagd in dev, LaunchDarkly in prod.
- **Naming:** `snake_case`, scoped: `module.feature.variant` (e.g., `directory.priest_map.enabled`).
- **Default:** OFF for new features. Turn on in dev → staging → 1% → 10% → 50% → 100%.
- **Targeting:** account, tenant, role, geo, percentage rollout, custom attribute.
- **Evaluation:** server-side (no client SDK for sensitive flags), memoized per request.
- **Audit:** every flag evaluation logged to `iam_audit` with flag key, account, variant.
- **TTL:** flags older than 90d without traffic are auto-deleted.
- **Kill switch:** every flag has a `kill_switch` variant that disables cleanly.
- **Debt:** no PR is "done" while a flag it shipped is still on by default.

---

## 21. API Versioning & Deprecation Policy

- **URI versioning:** `/api/v1/...`, `/api/v2/...`. Server actions are internal-only and don't need versioning.
- **Deprecation cycle:** announce → 6 months parallel run → 3 months sunset warning → hard cut.
- **Headers:** `Sunset`, `Deprecation`, `Link: <docs/...>; rel="deprecation"`.
- **Tooling:** OpenAPI generated at build, breaking-change detector in CI (oasdiff).
- **Backwards-compat:** any breaking change is a new major version, even if internal.

---

## 22. Idempotency Strategy

- **Header:** `Idempotency-Key` (client-generated UUID v4).
- **TTL:** 24h in Redis, mirrored in Postgres for durability.
- **Scope:** per `(account_id, route, key)`. Same key + different body = 422.
- **Replay:** if same key seen, return original response from store.
- **Failure:** on storage error, fail closed (reject request) — better than double-spend.
- **Webhooks:** inbound webhooks are idempotent on `gateway_event_id`.
- **Coverage:** all write APIs and all server actions.

---

## 23. Rate Limit Policy (per route class)

| Route class | Anonymous | Authenticated | Per-account burst | Per-account sustained | Per-IP | Action on limit |
|---|---|---|---|---|---|---|
| `/api/v1/priests` (search) | 60/min | 600/min | 10/sec | 10/sec | 100/min | 429 + Retry-After |
| `/api/v1/priests/[id]` | 120/min | 1,200/min | 20/sec | 20/sec | 200/min | 429 |
| `/api/v1/booking` (write) | — | 60/min | 1/sec | 0.5/sec | 30/min | 429 |
| `/api/v1/billing/*` | — | 30/min | 0.5/sec | 0.2/sec | 30/min | 429 + lockout if repeated |
| `/api/v1/auth/*` | 10/min | 10/min | n/a | n/a | 5/min | 429 + captcha |
| `/api/v1/search` | 30/min | 300/min | 5/sec | 5/sec | 60/min | 429 |
| `/api/v1/mcp` | — | 120/min | 2/sec | 1/sec | 60/min | 429 |
| `/api/v1/admin/*` | — | 600/min | 10/sec | 5/sec | n/a | 429 |
| `server action: any write` | — | 60/min | 1/sec | 0.5/sec | n/a | inline error toast |

Algorithm: **token bucket** per account+route, **sliding window** per IP.

Storage: Redis with Lua script for atomic check+decrement.

Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`, `Retry-After`.

---

## 24. Caching Strategy (L1/L2/Edge)

| Layer | Tech | TTL | Invalidated by | Use case |
|---|---|---|---|---|
| **Browser** | Service Worker | varies | SW update | static assets, recent pages |
| **CDN** | CloudFront | 5 min HTML, 1y static | purge API | anonymous pages |
| **Edge (Vercel)** | Edge cache | 1 min | revalidate | server-rendered pages |
| **L1 (in-process)** | LRU | 30s | mutation | config, schema |
| **L2 (Redis)** | Redis | 5 min | pub/sub | hot reads, sessions |
| **DB query cache** | pg_hint_plan + Redis | 1 min | write-through | expensive queries |
| **Materialized views** | Postgres matview | hourly cron | refresh | aggregations |
| **Search cache** | OpenSearch + Redis | 1 min | write-through | query results |

**Stale-while-revalidate** (SWR) for SEO pages: serve stale, refresh in background.

**Tagged invalidation:** every cached entry has tags (`priest:123`, `city:delhi`, `tenant:acme`). Purge by tag.

**Cache-Control:**
- Anonymous: `public, max-age=60, s-maxage=300, stale-while-revalidate=86400`
- Authenticated: `private, no-store`
- API: per-route

---

## 25. Performance Budgets (per route archetype)

| Route | HTML (KB) | JS (KB) | CSS (KB) | LCP (p75) | INP (p75) | CLS | TBT |
|---|---|---|---|---|---|---|---|
| `/` (home) | 80 | 120 | 30 | 1.5s | 200ms | <0.1 | <200ms |
| `/priests` (listing) | 60 | 100 | 25 | 1.8s | 200ms | <0.1 | <200ms |
| `/priests/[slug]` (detail) | 100 | 150 | 30 | 2.0s | 250ms | <0.1 | <300ms |
| `/temples/[slug]` | 100 | 150 | 30 | 2.0s | 250ms | <0.1 | <300ms |
| `/pujas/[slug]` (SEO 1,265) | 60 | 80 | 20 | 1.5s | 200ms | <0.05 | <150ms |
| `/book/*` | 80 | 200 | 30 | 2.0s | 300ms | <0.1 | <300ms |
| `/dashboard/*` | 100 | 300 | 30 | 2.0s | 300ms | <0.1 | <300ms |
| `/admin/*` | 100 | 250 | 30 | 2.0s | 300ms | <0.1 | <300ms |

Enforced by Lighthouse CI in PR pipeline. PR fails if budget exceeded.

---

## 26. Bundle Budgets

- **Initial JS:** ≤ 120 KB gzipped per route
- **Total JS per page:** ≤ 300 KB gzipped
- **CSS per page:** ≤ 30 KB gzipped
- **Web fonts:** ≤ 80 KB total
- **Images above-fold:** ≤ 200 KB each, WebP/AVIF, `next/image`
- **Lottie:** ≤ 50 KB per animation
- **3rd-party scripts:** ≤ 2, async/deferred, with SRI

Enforced by `next.config.ts` `bundleAnalyzer` + CI step.

---

# PART IV — ENGINEERING EXCELLENCE

## 27. Team Topology (per Team Topologies)

### Stream-aligned teams (own outcomes)
- **Team Discover** (eng-B + eng-C) — search, directory, AEO, content
- **Team Book** (eng-C) — booking, scheduling, calendar
- **Team Pay** (eng-A) — payments, ledger, invoices, payouts

### Platform teams (internal services)
- **Team Platform** (eng-A) — auth, RBAC, audit, infra, observability, FinOps
- **Team Data** (eng-C) — warehouse, ETL, embeddings, AEO ML

### Enabling teams (cross-cutting)
- **Team Security** (eng-A + outside vendor) — threat model, pentest, compliance
- **Team DX** (rotating) — tooling, docs, on-boarding, build/test perf

### Complicated-subsystem teams
- **Team Ledger** (eng-A + finance) — double-entry, GST, TDS, 194-O
- **Team Search** (eng-C) — OpenSearch, vector, ranking, evaluation

**Team APIs:** each team has clear ownership of modules, on-call rotation, SLOs, runbooks. Inter-team contracts are RFCs with 1-week notice.

---

## 28. DORA Targets (Year 1)

| Metric | Target | Elite |
|---|---|---|
| **Deployment frequency** | Daily per team | On-demand |
| **Lead time for changes** | < 1 day | < 1 hour |
| **Change failure rate** | < 5% | 0–15% |
| **MTTR** | < 1 hour | < 1 hour |
| **MTTD** | < 5 min | < 5 min |

Tracked via Linear + GitHub + PagerDuty, exported to Grafana, reviewed monthly.

---

## 29. DX Targets

| Metric | Target |
|---|---|
| Local dev setup | `make dev` or `npm run dev` ≤ 2 min |
| Cold start (Vercel) | < 1s |
| Hot reload (Next.js) | < 500ms |
| Build (production) | < 3 min |
| Lint | < 30s |
| Typecheck | < 60s |
| Unit tests | < 2 min |
| Integration tests | < 5 min |
| E2E tests | < 15 min |
| CI total | < 10 min |
| Preview deploy | < 3 min |
| Logs to find bug | < 5 min |
| Time to first commit (new dev) | < 1 day |

---

## 30. Test Strategy

### Test pyramid
- **Unit (60%):** domain logic, value objects, invariants, reducers, formatters. Target: ≥90% line coverage.
- **Integration (25%):** adapters (DB, gateway, queue), repo, usecase. Target: ≥85%.
- **E2E (10%):** Playwright per role, critical user journeys. Target: 50+ journeys.
- **Contract (3%):** Pact for adapter↔usecase, OpenAPI diff.
- **Visual (1%):** Chromatic for design system, key pages.
- **Load (1%):** k6 scripts for top 20 endpoints, run weekly.

### Other tests
- **Mutation:** Stryker, weekly on critical modules
- **Property-based:** fast-check on parsers, formatters
- **Fuzz:** payloads, file uploads, JSON parsers
- **A11y:** axe-core in E2E
- **Security:** OWASP ZAP in staging, weekly
- **Performance:** Lighthouse CI per PR, gate on budget
- **Chaos:** Litmus in staging, monthly game day

### Test data
- Factories per aggregate (e.g., `PriestFactory.build({ kyc_pass: true })`)
- No PII in fixtures
- DB snapshot for integration
- Containers (testcontainers) for ephemeral deps
- Migrations run in test setup

---

## 31. Load Test Plan

### Tools
- **k6** for HTTP load
- **Artillery** for scenario-based
- **pgbench** for DB
- **Locust** for browser

### Scenarios (per quarter)
- **Q1 baseline:** 1k RPS, 10k concurrent, 24h soak
- **Q2 capacity:** 5k RPS, 50k concurrent, 1h spike
- **Q3 peak:** 15k RPS, 100k concurrent, Diwali-like spike
- **Q4 disaster:** failover to DR during load

### Critical paths
1. User searches priest → 300ms p95 target
2. User books puja → 1.5s p95 target
3. User pays → 2s p95 target
4. Outbox dispatch → 5s p95 target
5. Webhook ingest → 200ms p95 target
6. Search query → 300ms p95 target

### Reporting
- k6 output → Grafana
- Compare to baseline
- Per-PR: synthetic check in staging
- Per-release: full load in staging
- Per-quarter: full load in prod-like

---

## 32. Chaos Engineering Scenarios (Litmus / Gremlin)

Monthly game day. Runbooks for each.

| Scenario | What we break | Expected recovery | Owner |
|---|---|---|---|
| **C1: Pod kill** | Kill random API pod | Pod respawns, requests succeed | SRE |
| **C2: AZ failure** | Block AZ1 in VPC | Traffic shifts to AZ2, no data loss | SRE |
| **C3: DB failover** | Failover RDS primary | Replica promoted, <30s RPO | SRE |
| **C4: Redis down** | Kill Redis primary | App degrades to DB cache, no data loss | eng-A |
| **C5: OpenSearch down** | Stop OpenSearch cluster | App falls back to DB FTS, slower | eng-C |
| **C6: Worker pool down** | Stop all Fargate workers | Outbox lag grows, no data loss | eng-C |
| **C7: Razorpay 5xx** | Mock 5xx from gateway | Retry queue, user sees "try again" | eng-A |
| **C8: GSP down** | Mock GSP timeout | IRN job retries, invoice marked pending | eng-A |
| **C9: S3 throttling** | Mock 503 from S3 | Retry, fallback to local | eng-C |
| **C10: Email provider down** | Stop Postmark | Queue, fallback SES | eng-C |
| **C11: Network partition** | Block 50% of traffic | Auto-retry, no data loss | SRE |
| **C12: DNS hijack** | Mock DNS failure | Health check fails, page | SRE |
| **C13: Cert expiry** | Force cert rotation | Auto-renew | SRE |
| **C14: Bad deploy** | Deploy a known-bad release | Auto-rollback via SLO guard | SRE |
| **C15: Tenant RLS bypass** | (in test) try cross-tenant query | RLS blocks | eng-A |

---

## 33. Catalogs

### Error Code Catalog

Format: `MODULE_CODE_NUMBER` with HTTP status. Stable across versions.

| Module | Code | HTTP | Message |
|---|---|---|---|
| `IAM` | `IAM_001` | 401 | Unauthenticated |
| `IAM` | `IAM_002` | 403 | Insufficient permission |
| `IAM` | `IAM_003` | 423 | Account locked |
| `IAM` | `IAM_004` | 429 | Too many OTP attempts |
| `TENANT` | `TENANT_001` | 403 | Cross-tenant access denied |
| `BOOK` | `BOOK_001` | 400 | Invalid booking state transition |
| `BOOK` | `BOOK_002` | 409 | Slot already taken |
| `BOOK` | `BOOK_003` | 422 | Cancellation policy violation |
| `BILL` | `BILL_001` | 402 | Payment required |
| `BILL` | `BILL_002` | 402 | Payment failed (gateway) |
| `BILL` | `BILL_003` | 409 | Duplicate payment (idempotency) |
| `BILL` | `BILL_004` | 422 | TDS/GST calculation error |
| `COMM` | `COMM_001` | 502 | SMS provider down |
| `COMM` | `COMM_002` | 502 | Email provider down |
| `SEARCH` | `SEARCH_001` | 503 | Search engine unavailable |
| `DIR` | `DIR_001` | 404 | Priest not found |
| `DIR` | `DIR_002` | 410 | Priest deactivated |
| `SEC` | `SEC_001` | 429 | Rate limit exceeded |
| `SEC` | `SEC_002` | 403 | CSRF check failed |
| `OPS` | `OPS_001` | 503 | Service degraded (degraded mode) |
| `LEGAL` | `LEGAL_001` | 451 | Blocked for legal reasons |

All error responses include `error.code`, `error.message`, `error.trace_id`, `error.docs_url`.

### Event Catalog (Outbox)

Format: `aggregate.event.v1`. Picked up by `outbox-dispatcher`, dispatched to subscribers.

| Aggregate | Event | Payload | Subscribers |
|---|---|---|---|
| `iam.account` | `iam.account.created.v1` | `{account_id, tenant_id, role, phone_hash}` | search, notification, audit |
| `iam.account` | `iam.account.kyc_passed.v1` | `{account_id, kyc_level, doc_type}` | search (boost trust), audit |
| `iam.session` | `iam.session.revoked.v1` | `{account_id, session_id, reason}` | audit |
| `dir.priest` | `dir.priest.created.v1` | `{priest_id, tenant_id, geo, deities}` | search-index, sitemap |
| `dir.priest` | `dir.priest.updated.v1` | `{priest_id, changes[]}` | search-index |
| `dir.priest` | `dir.priest.review_added.v1` | `{priest_id, review_id, rating}` | search-index (re-rank) |
| `dir.temple` | `dir.temple.created.v1` | `{temple_id, geo}` | search-index, sitemap |
| `book.booking` | `book.booking.created.v1` | `{booking_id, customer_id, priest_id, scheduled_at}` | notification, search-index (slot) |
| `book.booking` | `book.booking.confirmed.v1` | `{booking_id, confirmed_at}` | notification (priest), calendar |
| `book.booking` | `book.booking.completed.v1` | `{booking_id, completed_at}` | notification (review prompt), billing |
| `book.booking` | `book.booking.cancelled.v1` | `{booking_id, reason, refund_id?}` | notification, billing, calendar |
| `bill.payment` | `bill.payment.captured.v1` | `{payment_id, amount_inr, gateway_ref}` | ledger, invoice (IRN job), notification, audit |
| `bill.payment` | `bill.payment.failed.v1` | `{payment_id, reason}` | notification, retry |
| `bill.payout` | `bill.payout.initiated.v1` | `{payout_id, payee_id, amount_inr}` | notification (priest) |
| `bill.payout` | `bill.payout.completed.v1` | `{payout_id, utr}` | ledger, notification |
| `bill.invoice` | `bill.invoice.irn_assigned.v1` | `{invoice_id, irn, ack_no}` | notification (customer email PDF) |
| `comm.message` | `comm.message.sent.v1` | `{message_id, channel, recipient_hash}` | audit, analytics |
| `wf.instance` | `wf.instance.state_changed.v1` | `{instance_id, from, to, actor_id}` | notification (assignee), audit |
| `sec.rights_request` | `sec.rights.request.completed.v1` | `{request_id, kind, evidence_url}` | audit, notification (DPO) |

All events include: `event_id` (UUID), `occurred_at`, `tenant_id`, `trace_id`, `producer` (module + version), `schema_version`.

### Job Catalog (BullMQ + Cron)

| Queue | Kind | Concurrency | Rate limit | Retry | DLQ |
|---|---|---|---|---|---|
| `q:outbox.dispatch` | outbox dispatcher | 20 | 200/s | 3 | yes |
| `q:notification.email` | email send | 50 | 100/s | 3 | yes |
| `q:notification.sms` | sms send | 30 | 60/s | 3 | yes |
| `q:notification.whatsapp` | WA send | 20 | 40/s | 3 | yes |
| `q:notification.push` | push | 30 | 100/s | 3 | yes |
| `q:search.index` | reindex | 10 | 20/s | 3 | yes |
| `q:search.embed` | embedding | 5 | 5/s | 3 | yes |
| `q:billing.irn` | GST e-invoice | 5 | 10/s | 5 | yes |
| `q:billing.payout` | payout | 3 | 5/s | 3 | yes |
| `q:billing.tds` | 194-O calc | 3 | 5/s | 3 | yes |
| `q:billing.recon` | reconciliation | 1 | 1/s | 3 | yes |
| `q:kyc.verify` | KYC check | 10 | 20/s | 3 | yes |
| `q:webhook.dispatch` | outbound webhook | 20 | 50/s | 5 | yes |
| `q:webhook.receive` | inbound webhook | 30 | 100/s | 3 | yes |
| `q:audit.archive` | audit to S3 | 1 | 5/s | 3 | yes |
| `q:media.process` | image variants | 5 | 10/s | 3 | yes |
| `q:content.translate` | translation | 3 | 5/s | 3 | yes |
| `q:finops.cost_etl` | cost ingest | 1 | 1/s | 3 | yes |

| Cron | Schedule (IST) | Job | Tenant scope |
|---|---|---|---|
| `c:booking.reminder` | every 5m | send reminders for bookings 24h/1h ahead | all |
| `c:booking.no_show` | hourly | mark no-shows after grace period | all |
| `c:billing.invoice_due` | daily 02:00 | send invoice due reminders | all |
| `c:billing.dunning` | daily 03:00 | dunning for unpaid invoices | all |
| `c:billing.tds_cert` | Apr 1, Jul 1, Oct 1, Jan 1 | generate 194-O/80G certificates | all |
| `c:billing.gst_return` | 11th, 15th, 20th of month | GST return prep | all |
| `c:billing.recon` | daily 04:00 | bank reconciliation | all |
| `c:search.reindex` | daily 03:00 | full reindex | all |
| `c:search.trending` | hourly | trending computation | all |
| `c:finops.cost_etl` | daily 05:00 | cost records ingest | global |
| `c:finops.showback` | 1st of month | per-tenant showback report | global |
| `c:finops.optimize` | weekly Sun 04:00 | right-sizing recommendations | global |
| `c:audit.archive` | daily 01:00 | S3 archive of audit | all |
| `c:backup.verify` | daily 06:00 | backup restore test | global |
| `c:retention.sweep` | daily 07:00 | soft delete + retention cleanup | all |
| `c:feature_flag.gc` | daily 08:00 | flag cleanup (no traffic 90d) | global |
| `c:session.expire` | every 15m | expire stale sessions | all |
| `c:health.deep` | every 5m | deep health checks | global |

### Notification Catalog

| Template key | Channel | Locale | Trigger | Vars |
|---|---|---|---|---|
| `booking.confirmed.customer` | email, sms, push, inapp | en, hi | `book.booking.confirmed.v1` | `{customer_name, priest_name, scheduled_at, address}` |
| `booking.confirmed.priest` | email, sms, push, inapp | en, hi | same | `{priest_name, customer_name, scheduled_at, address}` |
| `booking.reminder.24h` | email, sms, push | en, hi | cron | same as above |
| `booking.reminder.1h` | push, sms | en, hi | cron | same |
| `booking.completed.review_prompt` | email, push | en, hi | `book.booking.completed.v1` | `{priest_name, review_link}` |
| `payment.captured.customer` | email | en, hi | `bill.payment.captured.v1` | `{amount_inr, invoice_pdf_url, txn_id}` |
| `payment.failed.customer` | email, push | en, hi | `bill.payment.failed.v1` | `{reason, retry_link}` |
| `payout.completed.priest` | email, sms, push | en, hi | `bill.payout.completed.v1` | `{amount_inr, utr, bank_last4}` |
| `kyc.approved` | email, push | en, hi | `iam.account.kyc_passed.v1` | `{account_id}` |
| `kyc.rejected` | email | en, hi | (manual) | `{reason, retry_link}` |
| `rights.request.received` | email | en, hi | `sec.rights.request.created.v1` | `{request_id, sla_due_at}` |
| `rights.request.completed` | email | en, hi | `sec.rights.request.completed.v1` | `{evidence_url, expires_at}` |
| `invoice.irn_ready` | email | en, hi | `bill.invoice.irn_assigned.v1` | `{invoice_pdf_url, irn}` |
| `consent.update` | email | en, hi | (manual) | `{consent_kind, version, link}` |
| `newsletter.weekly` | email | en, hi | cron | `{panchang, festivals, top_pujas}` |

### Webhook Catalog (outbound)

| Topic | Subscribers | Payload schema | Signature |
|---|---|---|---|
| `booking.created` | partner apps | `book.booking.created.v1` (subset) | HMAC-SHA256 |
| `booking.completed` | partner apps | `book.booking.completed.v1` (subset) | HMAC-SHA256 |
| `payment.captured` | partner apps | `bill.payment.captured.v1` (subset) | HMAC-SHA256 |
| `payout.completed` | partner apps | `bill.payout.completed.v1` (subset) | HMAC-SHA256 |
| `priest.profile_updated` | aggregators | `dir.priest.updated.v1` (subset) | HMAC-SHA256 |

All webhooks: 3 retries with exponential backoff (1m, 5m, 30m), then DLQ, then 24h hold, then operator review.

---

## 34. Vendor Scorecards

| Vendor | Category | Score (0-10) | Lock-in risk | Replaceable? | Decision |
|---|---|---|---|---|---|
| **Razorpay** | PSP | 9 | Med | Yes (port) | PRIMARY |
| **PayU** | PSP | 7 | Med | Yes | Backup at 10% traffic in PR 12 |
| **ClearTax / IRIS** | GSP | 8 | Low | Yes | PRIMARY (ClearTax), backup IRIS |
| **Postmark** | Email | 9 | Low | Yes | PRIMARY |
| **SES** | Email | 8 | Low | Yes | Backup |
| **Pinelabs** | SMS | 7 | Med | Yes | PRIMARY |
| **Gupshup** | WhatsApp | 8 | Med | Yes | PRIMARY |
| **Wati** | WhatsApp | 7 | Med | Yes | Backup |
| **FCM/APNs** | Push | 9 | High | No | PRIMARY (no real alternative) |
| **Cloudflare** | DNS, WAF, DDoS | 9 | Med | Yes | PRIMARY |
| **CloudFront** | CDN | 9 | Med | Yes | PRIMARY (Vercel-native) |
| **Vercel** | Web hosting | 8 | High | Med | PRIMARY in Year 1, evaluate Year 2 |
| **AWS RDS** | DB | 9 | High | Med | PRIMARY |
| **AWS ElastiCache** | Redis | 8 | High | Med | PRIMARY |
| **OpenSearch** | Search | 7 | High | Med | PRIMARY |
| **S3** | Storage | 9 | Med | Yes | PRIMARY |
| **Sentry** | Error tracking | 9 | Low | Yes | PRIMARY |
| **PagerDuty** | On-call | 9 | Low | Yes | PRIMARY |
| **Grafana Cloud** | Observability | 8 | Med | Yes | PRIMARY |
| **GitHub** | VCS | 9 | Med | Med | PRIMARY |
| **Linear** | Issue tracking | 9 | Low | Yes | PRIMARY |
| **LaunchDarkly** | Feature flags | 8 | Med | Yes (Flagd) | PRIMARY in prod, Flagd in dev |
| **DeepL** | Translation | 8 | Low | Yes | PRIMARY (cheap) |
| **Anthropic** | Translation (fallback) | 9 | Med | Yes | Backup + high-quality pages |
| **OpenAI** | Embeddings | 8 | Med | Yes | Backup |
| **pgvector** | Vector | 8 | None (Postgres) | Yes | PRIMARY |
| **Photon / Nominatim** | Geo | 7 | None (OSM) | Yes | PRIMARY |

---

## 35. Release Train Calendar

| Cadence | Day | Content |
|---|---|---|
| **Trunk** | Continuous | Merge to main, ephemeral preview, automated tests |
| **Daily** | 6:00 IST | Auto-deploy to staging |
| **Twice weekly** | Mon, Thu 10:00 IST | Canary 1% → 10% → 50% → 100% in prod (1h) |
| **Hotfix** | On-demand | Out-of-band, with explicit SRE approval, postmortem in 24h |
| **Feature freeze** | Last 5 days of quarter | No new features, only reliability and security |

**Release train is the default.** Hotfixes are exceptions and tracked.

**Rollback SLA:** < 5 min, automated via SLO guard.

---

# PART V — DEFINITION OF DONE & ACCEPTANCE

## 36. Definition of Done (per PR cluster)

### IAM cluster (PR 1, 2, 3)
- All 6 roles enforced in middleware
- `proxy.ts` blocks all `protected: true` routes
- All write APIs have idempotency key
- All sessions httpOnly, secure, sameSite=lax
- Audit log written on every state change
- ABAC policies tested with 10+ cases
- 0 P0 security findings
- Threat model updated

### Auth UX cluster (PR 4, 5)
- All auth routes have real flows (no stubs)
- Providers wired: Session, Theme, Query, Toast, Tooltip
- 404 and 500 pages exist
- Lighthouse a11y = 100 on all auth routes
- All auth states (success, error, lock, deactivate) tested

### Dashboard cluster (PR 6, 7, 8, 9)
- All 60 sections render real data
- Persistent sidebar with drawer on mobile
- Tests: ≥85% line coverage on dashboards
- Lighthouse perf ≥90 on all dashboards
- E2E Playwright per role (10+ journeys)
- A11y: 0 critical, ≤2 serious

### Directory cluster (PR 10, 11)
- Postgres + Drizzle + RLS, all 13 dir tables seeded
- 100,000+ entities, 10,000+ cities, deterministic
- 12 archetypes live
- AEO on every page
- Map: Leaflet + OSM, dynamic, hidden behind toggle
- Lighthouse ≥90 on directory routes
- Top 20k pre-rendered, rest SSR + edge cache

### Ledger & Billing (PR 11.5, 12)
- Double-entry invariant: `sum(debit) = sum(credit)` proven in test
- Trial balance + P&L work
- Invoice generation (PDF + JSON), GST split, IRN placeholder
- Full Razorpay: orders, payments, refunds, webhooks, mandates
- TDS 194-O auto-deduct
- GST e-invoice via GSP
- T+1/T+2 payouts with penny-drop
- Chargeback workflow
- Bank reconciliation
- Idempotency on every write

### Communication (PR 13)
- 5 channels: email, SMS, WhatsApp, push, in-app
- 15+ templates
- DLT-registered (SMS)
- Outbox exactly-once
- SSE for in-app inbox

### i18n + AI (PR 14a, 14b)
- next-intl en-IN + hi-IN
- 1,265 pages translated with reviewer queue
- Hreflang complete
- MCP server with 4 read tools
- Bilingual llms.txt
- AI plugin manifest
- 20 expert authors with sameAs

### FinOps + Sec + Ops (PR 15)
- OTel SDK, JSON logs, S3 archive
- 6 SLOs measured and within budget
- All 80 modules have runbook
- DR drill passes RTO<30m RPO<5m
- FinOps dashboard live, showback report
- Pentest: 0 P0/P1
- Lighthouse ≥95

---

## 37. Acceptance Criteria (per PR)

For every PR, the following must be true before merge:

### Code
- [ ] Lint passes (`npm run lint`)
- [ ] Typecheck passes (`npm run typecheck`)
- [ ] No new TODO/FIXME without ticket
- [ ] No commented-out code
- [ ] No secrets in code
- [ ] No `any` without justification

### Tests
- [ ] Unit tests added, all pass
- [ ] Coverage meets per-cluster target
- [ ] Integration tests added for new repos/usecases
- [ ] E2E journey added for new user flow
- [ ] Mutation tests for critical modules (where applicable)

### Schema
- [ ] Drizzle migration created, tested up and down
- [ ] `withTenant()` wrapper used in all repos
- [ ] RLS policy added for new tenant-scoped tables
- [ ] Outbox event published in same tx (for writes)
- [ ] Audit log written (for sensitive actions)

### Security
- [ ] `withAuth` + `withTenant` + `withPerm` applied to all server actions
- [ ] Idempotency key required on all writes
- [ ] Zod validation on all input
- [ ] PII fields encrypted
- [ ] No secrets in logs
- [ ] Threat model updated

### Observability
- [ ] Pino JSON logs for all new code
- [ ] OTel spans for usecases/repos
- [ ] Prometheus metrics for new endpoints
- [ ] Alerts defined for new SLOs
- [ ] Grafana dashboard updated

### Feature flag
- [ ] New feature behind a flag
- [ ] Flag has kill_switch variant
- [ ] Default OFF

### Documentation
- [ ] README updated (if module-level)
- [ ] ADR created (if major decision)
- [ ] Runbook updated (if new alert)
- [ ] Threat model updated (if new attack surface)
- [ ] CHANGELOG entry

### Build & Deploy
- [ ] Build passes (`npm run build`)
- [ ] Preview deploy works
- [ ] Canary deploy tested
- [ ] Rollback command documented
- [ ] Migration deploy plan

### Stakeholder
- [ ] PR description has: what, why, how, risk, rollback
- [ ] Demo recorded (if user-facing)
- [ ] SRE notified (if infra change)
- [ ] Legal notified (if compliance change)

---

## 38. Capacity Headroom Targets

- **Steady-state CPU:** 40-60% (40% headroom for spikes)
- **Steady-state memory:** 50-70%
- **DB connections:** 60% of max pool
- **Queue depth:** < 1,000 sustained
- **Outbox lag:** < 30s p99
- **CDN cache hit:** > 80%
- **Error budget burn:** < 50% at month-end

If any metric exceeds target, automatic alert + scale-up.

---

# PART VI — EXECUTION (unchanged from v9)

## 39. 17-PR Execution Plan (locked)

| # | Title | Cluster | Engineer | Week |
|---|---|---|---|---|
| **0.5** | AEO Speakable on 1,265 pages (en) | CMS | C | 0 |
| **1** | Auth contract + session fix | IAM | A | 1 |
| **2** | Middleware enforcement + landing | IAM/Routing | A | 2 |
| **3** | Security primitives (RBAC/ABAC/CSRF/rate/audit) | Sec | A | 3 |
| **4** | AuthRoutePanel + apply pages | Auth UX | B | 4–5 |
| **5** | Providers + error/404/toast | Providers | B | 6 |
| **6** | Dashboard primitives + tests | Dash | B | 7 |
| **7** | Persistent sidebar + drawer | Dash | B | 8 |
| **8** | User / Pandit / Vendor dashboards (30 sections) | Dash | B | 9–10 |
| **9** | Manager / Admin / SuperAdmin dashboards (30 sections) | Dash | A | 9–12 |
| **10** | Postgres Drizzle + tenant + directory schema + seed | DB/Dir | C | 13–14 |
| **11** | `/priests`, `/temples`, `/deities`, AEO, map | Dir | C | 15–17 |
| **11.5** | Ledger foundation (chart-of-accounts, double-entry, IRN-ready invoices) | Billing | A | 18 |
| **12** | Search + Booking + Full PSP (Razorpay, payouts, TDS 194-O, GST e-invoice, refunds, chargebacks, reconciliation, mandates) | Search/Book/Bill | C+A | 19–21 |
| **13** | Communication + Workflows + Outbox + Inbox | Comm/WF | C | 22 |
| **14a** | i18n + Hindi translation of 1,265 pages with human review queue | i18n/CMS | C | 23 |
| **14b** | AI surfaces (MCP read-only, llms.txt, plugin, authors) | AI | C | 24 |
| **15** | FinOps + Security hardening + Observability + DR + SLOs | FinOps/Sec/Ops | A | 25–26 |

**Critical path:** 0.5 → 1 → 2 → 3 → 4 → 8 → 9 → 10 → 11 → 11.5 → 12 → 13 → 14a → 14b → 15

---

## 40. Schedule (3 engineers, 6 months, 26 weeks)

```
Week:  0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26
A:     .  1  2  3  .  .  .  .  .  9  9  9  9  .  .  .  .  .11.5 12 12 12  .  .  . 15 15
B:     .  .  .  .  4  4  5  6  7  8  8  8  8  .  .  .  .  .  .  .  .  .  .  .  .  .
C:    0.5 .  .  .  .  .  .  .  .  .  .  .  . 10 10 11 11 11  .  12 12 12 13 14a14b .  .
```

---

## 41. Verification Matrix (post each PR)

| PR | Lint | Type | Unit | Int | E2E | Build | Lighthouse | Security | FinOps | SLO |
|---|---|---|---|---|---|---|---|---|---|---|
| 0.5 | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — |
| 1 | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — |
| 2 | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | — | — |
| 3 | ✓ | ✓ | ✓ | ✓ | — | ✓ | — | ✓ | — | — |
| 4 | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — |
| 5 | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — |
| 6 | ✓ | ✓ | ✓ (≥90% cov) | ✓ | — | ✓ | — | — | — | — |
| 7 | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — |
| 8 | ✓ | ✓ | ✓ (≥85% cov) | ✓ | ✓ (Playwright per role) | ✓ | — | — | — | — |
| 9 | ✓ | ✓ | ✓ (≥85% cov) | ✓ | ✓ | ✓ | — | ✓ | — | — |
| 10 | ✓ | ✓ | ✓ (≥80% cov) | ✓ (migrations) | — | ✓ | — | ✓ (RLS test) | — | — |
| 11 | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (≥90) | ✓ | — | — |
| 11.5 | ✓ | ✓ | ✓ (ledger invariant) | ✓ | — | ✓ | — | — | — | — |
| 12 | ✓ | ✓ | ✓ (≥85% cov) | ✓ (payment, payout, recon) | ✓ (booking) | ✓ | ✓ | ✓ | — | — |
| 13 | ✓ | ✓ | ✓ | ✓ (outbox exactly-once) | ✓ (inbox) | ✓ | — | — | — | — |
| 14a | ✓ | ✓ | ✓ (translation queue) | ✓ | ✓ (i18n) | ✓ | — | — | — | — |
| 14b | ✓ | ✓ | ✓ (MCP) | ✓ (MCP) | ✓ (assistant smoke) | ✓ | — | — | — | — |
| 15 | ✓ | ✓ | ✓ (≥80% cov) | ✓ (DR drill) | ✓ | ✓ | ✓ (≥95) | ✓ (pentest) | ✓ | ✓ |

---

## 42. Risk Register (top 10, with mitigations)

| # | Risk | Probability | Impact | Mitigation |
|---|---|---|---|---|
| R1 | PCI scope expansion (PAN touch) | Med | High | Use gateway tokens only; SAQ-A scope; annual attestation |
| R2 | RBI/regulatory change in payments | Med | High | Plug-in gateway abstraction; quarterly legal review |
| R3 | DPDP consent UX friction | High | Med | Just-in-time, versioned, A/B tested |
| R4 | Postgres hot spot on `tenant_id` RLS | Med | Med | Composite index, partitioning by `tenant_id` hash |
| R5 | Search quality at 100k+ entities | Med | High | Hybrid BM25+vector, eval harness from PR 12 |
| R6 | AEO answer accuracy | Med | Med | Strict ≤280 char rule, audited templates, never auto-publish |
| R7 | 1,265-page regression during directory work | Med | High | All SEO writes go through `cms_content` adapter; no direct JSON edits in PR ≥ 0.5 |
| R8 | Dashboard perf with 60 sections | Low | Med | Code-split per section; RSC + Suspense; Lighthouse gate |
| R9 | Cost overrun on FinOps-first year | Med | Med | Unit-econ dashboard live by PR 12; per-tenant showback by PR 15 |
| R10 | Vendor lock-in (gateway, search) | Med | Med | Port + adapter pattern; multi-gateway from PR 12 |
| R11 | Bad Hindi translation harms brand | Med | Med | Human review queue mandatory; DeepL draft, reviewer approve |
| R12 | 194-O TDS miscalculation | Low | High | Unit tests, double-entry invariant, periodic audit |
| R13 | GST e-invoice rate limit | Med | Med | Retry with backoff, fallback GSP |
| R14 | Outbox lag under load | Med | High | Outbox lag SLO, scale workers, DLQ |
| R15 | Aadhaar/PAN leak in logs | Low | Critical | PII redaction in pino, encryption at rest, periodic audit |

---

## 43. Cadence

For every PR I will:

1. Create branch `pr/<n>-<short-name>` from `main`
2. Ship the diff
3. Run the verification matrix for that PR
4. Hand you a 1-page summary: files changed, tests added, screenshots/metrics, rollback command
5. **Pause.** You say "next" to continue, or call out fixes.

---

## 44. Notes for future revisions

- Module catalog may grow to 100+ modules as we discover more needs during PRs.
- New PRs may be added between existing ones if scope demands it.
- Engineer assignments assume a 3-person team; if solo, timeline doubles and some PRs (e.g., 8, 9, 12) split.
- This document is the contract. Any change to scope, ordering, or ownership should be reflected here before code lands.
- v10 added 33 new sections on top of v9: stakeholders, OKRs, NSM, anti-goals, hex detail, C4, data flow, network, deployment, multi-region, capacity, cost, ADRs, PII, retention, encryption, threat model, compliance, observability, SLOs, runbooks, feature flags, API versioning, idempotency, rate limit, caching, perf budgets, bundle budgets, team topology, DORA, DX, test strategy, load plan, chaos, error/event/job/notification/webhook catalogs, vendor scorecards, release train, DoD, acceptance criteria, headroom targets, expanded risk register.
- **v11 added 4 new parts on top of v10:**
  - **Part VII — Multi-Perspective Audit:** 4 reviewers (Senior, Principal, CTO, Product Engineer) × 10-11 concerns each = 41 consolidated findings, all with severity + owner + PR mapping. 4 critical (PII logs, Aadhaar/PAN, runway, hex leak). Roadmap split: v1.0 (6 months) + v1.1 (+2 months) + v2 (Y2).
  - **Part VIII — UIDP Integration:** Inhouse UIDP is the identity authority. Our backend is a thin orchestration layer. `UidpPort` interface (50+ methods), our local schema (`profile`, `preference`, `account_role_binding`, `session_mirror`, `saved`, `follow`), 19 webhook events, failure modes, security model (mTLS, KMS, scoped service JWT), PRs 1-4-11-12-14a are UIDP-touched.
  - **Part IX — Dashboard Menu Planning:** 6 roles × 10 sections = 60 sections, each with primary/secondary split, quick actions, mobile bottom nav, empty/loading/error states. Common patterns (layout, header, data display, actions, A11y, performance).
  - **Part X — User Flows and Scenarios:** 10 personas, 4 happy paths (User book, Pandit accept, Vendor fulfill, Manager approve), 43 edge cases (auth/booking/payment/directory/i18n/compliance/operational), 20 error/recovery flows, 10 cross-role flows, 5 mobile-specific scenarios. PR mapping table for every concern.


---

# PART VII — MULTI-PERSPECTIVE AUDIT

Four reviewers, four lenses, one consensus.

---

## 45. Senior Engineer Review (lens: code quality, correctness, performance, security)

### Strengths (keep doing)
1. **Hexagonal architecture** — clean dependency rule, ports/adapters clearly drawn. This will pay off the moment we swap Razorpay for a backup gateway in PR 12.
2. **Type-safe end-to-end** — Zod at boundary + Drizzle in middle + server actions on top. Catches 80% of bugs at compile time.
3. **Outbox pattern** — exactly-once events are non-negotiable for billing. This is the right call.
4. **Idempotency on every write** — webhook safety is hard. This is correct.
5. **AEO contract on every page** — Lighthouse SEO gate is concrete and testable.
6. **Test pyramid is well-defined** — 60/25/10/3/1/1 split is realistic.

### Concerns (must address)

#### C-S1: Soft-delete + RLS interaction
**Issue:** Postgres RLS doesn't know about `deleted_at`. A `SELECT * FROM iam_account` returns soft-deleted rows.
**Fix:** Add `AND deleted_at IS NULL` to every RLS policy OR use a partial index `WHERE deleted_at IS NULL` and ensure all queries filter.
**PR:** PR 10 (with migration).
**Severity:** High (data leak risk).

#### C-S2: OpenSearch vector index size at 100k entities
**Issue:** 100k × 1536-dim float32 = 600 MB raw. With HNSW index, 2-3x. Single-node OpenSearch can hold it, but recall degrades.
**Fix:** Use int8 quantization (pgvector) or OpenSearch `knn_vector` with `index.codec: pq`. Or use pgvector for v1 and defer OpenSearch to v2.
**PR:** PR 11 (decision: pgvector first, OpenSearch at 50k+).
**Severity:** Med.

#### C-S3: Server actions are not HTTP APIs
**Issue:** Server actions are not cacheable, not addressable, not observable as a public API. Internal tools (admin, scripts) need REST.
**Fix:** Server actions for user-facing writes; REST for internal/admin/MCP. Both go through the same usecase layer.
**PR:** PR 10 (REST scaffold), PR 12 (PSP callbacks).
**Severity:** Med.

#### C-S4: Vercel cold starts
**Issue:** Vercel functions have 250ms-2s cold start. With ISR and edge functions we mitigate, but our auth middleware is on every request.
**Fix:** Move auth middleware to edge; usecases stay on Node runtime. Cache session in Redis (sub-ms).
**PR:** PR 2.
**Severity:** Low.

#### C-S5: Drizzle migration in transaction
**Issue:** Drizzle Kit migrations don't run in transactions by default. A failed mid-migration locks the DB.
**Fix:** Wrap migrations in `BEGIN; ... COMMIT;` and test up/down on staging. Add `drizzle/migrate.ts` with `withTransaction`.
**PR:** PR 10.
**Severity:** High.

#### C-S6: Outbox dispatcher is a single point of failure
**Issue:** One `outbox-dispatcher` instance = SPOF.
**Fix:** Use Postgres `LISTEN/NOTIFY` + advisory lock so only one dispatcher runs at a time across N workers. Tested in PR 13.
**PR:** PR 13.
**Severity:** Med.

#### C-S7: Webhook idempotency window mismatch
**Issue:** Idempotency TTL is 24h. Razorpay can replay webhooks after 7 days. Duplicate processing.
**Fix:** Idempotency on `gateway_event_id`, TTL 30 days, mirrored in Postgres for durability.
**PR:** PR 12.
**Severity:** High (financial impact).

#### C-S8: Bundle size for dashboard
**Issue:** 60 dashboard sections × 5 widgets each = 300 widgets. Eager import = 1MB+ JS.
**Fix:** Dynamic import per section; RSC by default; client only for interactive bits. Bundle budget gate.
**PR:** PR 8.
**Severity:** Med.

#### C-S9: No proper contract tests
**Issue:** Adapter↔usecase coupling is implicit. Refactor breaks silently.
**Fix:** Pact for HTTP adapters; in-process contract tests for repos. Required for PR 12 (gateway contract).
**PR:** PR 12.
**Severity:** Med.

#### C-S10: Pino PII filter is opt-in
**Issue:** Easy to forget. PII leaks to logs.
**Fix:** Default-deny: every field must be allowlisted. Lint rule blocks new field additions without allowlist entry.
**PR:** PR 11.
**Severity:** Critical.

### Action items for PRs
- C-S1 → PR 10
- C-S5 → PR 10
- C-S7 → PR 12
- C-S2 → PR 11 (decision: pgvector first)
- C-S3 → PR 10, 12
- C-S4 → PR 2
- C-S6 → PR 13
- C-S8 → PR 8
- C-S9 → PR 12
- C-S10 → PR 11

### Verdict
**APPROVE with the 10 concerns above addressed in their respective PRs.** Plan is technically sound.

---

## 46. Principal Engineer Review (lens: architecture, scalability, patterns, anti-patterns)

### Strengths
1. **Schema-first** — Postgres as contract. Right call for a multi-domain platform.
2. **Multi-tenant by shared schema + RLS** — operations-friendly, cost-efficient. RLS bypass risk is mitigated by `withTenant()` wrapper.
3. **CQRS + outbox** — separates read/write paths, allows independent scaling. Critical for booking (write-heavy) vs search (read-heavy).
4. **Hexagonal** — the right abstraction level. Not too thin (DDD), not too thick (microservice mesh).
5. **Feature flags everywhere** — risk mitigation is built into the process.

### Concerns

#### C-P1: Over-modularization for a 3-person team
**Issue:** 14 clusters, 80+ modules is a lot of code for 3 engineers in 6 months.
**Fix:** Group related modules in a single PR. Don't ship one module per PR. Module boundaries are for code org, not for release.
**PR:** All PRs (composite delivery).
**Severity:** Med (productivity risk).

#### C-P2: Two abstractions for the same concept
**Issue:** `iam_consent` and `sec_consent_proof` overlap. `iam_audit` and `ops_audit` overlap. `iam_tenant_member` and `tenant_member` overlap.
**Fix:** Consolidate. `iam_*` is the UIDP namespace; domain modules reference it. One `consent` table (in `iam`), one `audit` table (in `iam`), one `tenant_member` (in `iam`).
**PR:** PR 3 (consolidation).
**Severity:** Med.

#### C-P3: Outbox + DLQ + Saga — too much for v1
**Issue:** Saga is overkill. We can do compensating transactions inline in the usecase for v1. Sagas add operational burden.
**Fix:** Drop `job.saga` table. Use a `compensation_log` field in the usecase result. Add Sagas in v2.
**PR:** PR 12.
**Severity:** Low.

#### C-P4: No explicit data lifecycle for soft-deleted records
**Issue:** Soft-deleted rows accumulate forever. Indexes bloat.
**Fix:** Hard delete after 90 days. Cron sweep. Tested in PR 10.
**PR:** PR 10, 15.
**Severity:** Med.

#### C-P5: pgvector vs OpenSearch — choose one for v1
**Issue:** Plan says hybrid BM25+vector on OpenSearch. But pgvector can do vector. And Postgres FTS can do BM25.
**Fix:** For v1, use pgvector + pg_trgm (BM25-like with `ts_rank`). OpenSearch when we have > 50k entities or need cross-cluster search. Saves $4.5k/mo and 3-node cluster ops.
**PR:** PR 11 (pgvector), PR 12 (eval harness), PR 15 (OpenSearch if needed).
**Severity:** Med (cost + ops).

#### C-P6: Event schema versioning is implicit
**Issue:** Events have `v1` in name. No schema registry. Breaking change = silent consumer failure.
**Fix:** Schema registry (e.g., Confluent Schema Registry or simpler: Zod schemas in `events/` with `vN` directories). Backward compat check in CI.
**PR:** PR 13.
**Severity:** High.

#### C-P7: No explicit cell-based or sharding plan
**Issue:** Single Postgres primary. At 10k QPS, will struggle.
**Fix:** Year 1 = single primary + read replica + Citus-ready. Year 2 = Citus sharding by `tenant_id` hash. No code change at the application layer (Drizzle + connection router).
**PR:** PR 10 (Citus-ready schema), PR 15 (read replica, monitoring), Year 2 (Citus).
**Severity:** Low for Year 1, Med for Year 2.

#### C-P8: AI plugin manifest is not standardized
**Issue:** OpenAI's plugin manifest is deprecated. ChatGPT plugins are gone. Modern is MCP.
**Fix:** Drop the AI plugin manifest. Ship only MCP. `/.well-known/ai-plugin.json` can be a redirect to MCP discovery.
**PR:** PR 14b.
**Severity:** Low (deprecation cleanup).

#### C-P9: AEO testability is weak
**Issue:** Speakable answer is a string. How do we know it's "good"?
**Fix:** AEO eval harness. 100 test questions, expected entity types, expected entities. Run in CI. Also: human review queue for all 1,265 pages (PR 14a covers translation, but AEO review is a separate queue).
**PR:** PR 0.5 (eval harness), PR 11 (eval at scale).
**Severity:** Med.

#### C-P10: Hexagonal layer is leaky in one place
**Issue:** `lib/auth/mock-session.ts` and `lib/auth/mock-session-client.ts` are not in `domain/` or `application/`. They're in `lib/`, which is shared with UI.
**Fix:** Move all identity code to `infra/uidp/` (adapter layer for the inhouse UIDP). Mock auth in dev, real UIDP in prod. Same interface, both behind a port.
**PR:** PR 1, 2.
**Severity:** High (foundation).

### Action items for PRs
- C-P1 → All PRs
- C-P2 → PR 3
- C-P3 → PR 12
- C-P4 → PR 10, 15
- C-P5 → PR 11, 12, 15
- C-P6 → PR 13
- C-P7 → PR 10, 15
- C-P8 → PR 14b
- C-P9 → PR 0.5, 11
- C-P10 → PR 1, 2

### Verdict
**APPROVE with the 10 concerns addressed.** Plan is architecturally sound for Year 1, with clear Year 2 paths for scale.

---

## 47. CTO Review (lens: strategy, OKRs, risk, cost, team, vendor, roadmap)

### Strengths
1. **North Star Metric is clear** — weekly completed bookings. Aligns team.
2. **OKRs are KR-linked and timeboxed** — no ambiguity.
3. **4 locked decisions are correct** — full PSP, read-only AI, hi-IN translation, 1-PR-per-session cadence. All match the risk profile and market.
4. **Cost projections are honest** — $77k/mo at 50k bookings, with a break-even path. Not over-promised.
5. **Risk register is ranked and mitigated** — R1–R10 + 5 more in v10. All have owners.

### Concerns

#### C-T1: Timeline is aggressive for a 3-person team
**Issue:** 6 months for 17 PRs including full PSP, GST e-invoice, MCP, i18n, AEO, dashboards for 6 roles, ledger, observability, DR, SLOs.
**Fix:** Identify 3 PRs that can be deferred to v1.1 (post-launch) without breaking launch:
- PR 14b (AI surfaces) — can ship 1 month after launch
- PR 13 in-app inbox (not chat) — can ship 1 month after launch
- DR game day in PR 15 — can do read-only backup verification, defer full DR drill to v1.1
**PR:** Roadmap v1.1.
**Severity:** Med.

#### C-T2: Vendor lock-in is a real risk despite ports
**Issue:** Razorpay's market share is high in India but the API surface is custom. Swapping mid-year is a 2-month project.
**Fix:** Year 1: ship on Razorpay. Year 1 Q4: add PayU at 10% traffic as a canary. Year 2: pick a primary based on data.
**PR:** PR 12 (Razorpay), v1.1 (PayU canary).
**Severity:** Med.

#### C-T3: Aadhaar / PAN handling is a regulatory minefield
**Issue:** Storing even last-4 + hash of Aadhaar/PAN has compliance implications. UIDAI 2018 circular restricts storage. RBI requires tokenization for cards.
**Fix:** Use Razorpay's Aadhaar verification API (we never touch Aadhaar). Use Razorpay Token HQ for cards (we never touch PAN beyond what Razorpay returns). Document explicitly. Legal review at PR 12.
**PR:** PR 12.
**Severity:** Critical.

#### C-T4: GST e-invoice is hard
**Issue:** GSPs (ClearTax, IRIS) have rate limits (100 IRN/min for new GSPs), schema quirks, sandbox issues, downtime during filing season. We've never done this.
**Fix:** Two GSPs in parallel (primary + backup), 5 retries with exponential backoff, queue depth SLO, daily reconciliation with GSP. Test mode in PR 11.5, real in PR 12.
**PR:** PR 12.
**Severity:** High.

#### C-T5: 194-O TDS is a complex rule
**Issue:** 0.1% TDS on e-commerce payments above ₹50 lakh/yr per payee. Threshold changes, complex aggregations.
**Fix:** Tax engine as a separate module (`bill.tax`) with rule DSL. Test cases for boundary conditions. Quarterly reconciliation with 26Q. Lawyer-reviewed at PR 12.
**PR:** PR 12.
**Severity:** High.

#### C-T6: 20 expert authors is a content risk
**Issue:** 20 named experts require real credentials, photos, bios, LinkedIn profiles. We don't have them. Risk: SEO/AEO claims not backed by real authors = trust hit.
**Fix:** Phase 1 (PR 0.5–11): use 5 placeholder authors + 1 founding team member. Phase 2 (PR 14b): 15 more as we onboard. Or: use "Editorial Team" for v1, add named authors in v1.1.
**PR:** PR 14b.
**Severity:** Med.

#### C-T7: Hindi translation quality is uncertain
**Issue:** DeepL is good for EU languages, weaker for Hindi. Anthropic/Claude is better but more expensive. Reviewer queue is the bottleneck.
**Fix:** Three-tier pipeline: (1) DeepL draft for 80% of pages, (2) Claude re-translation for top 200 pages (by traffic), (3) reviewer approve/reject with feedback loop into the model. Cost: ₹1,500 DeepL + ₹400 Claude + 40 reviewer-hours = ₹6,000 total.
**PR:** PR 14a.
**Severity:** Med.

#### C-T8: No customer success function in the plan
**Issue:** 60 dashboard sections × 6 roles = 360 surfaces. Users will get lost. Onboarding, in-app guidance, support are absent.
**Fix:** Add: in-app tours (Pendo or DIY), contextual help, support chat widget, status page, NPS survey at day 7 and day 30.
**PR:** PR 8 (User dashboard), PR 11 (in-app), PR 15 (NPS).
**Severity:** Med.

#### C-T9: No explicit go-to-market plan
**Issue:** 1,265 SEO pages, 100k directory entities — but who's going to look at them? SEO is necessary but not sufficient.
**Fix:** GTM plan: (1) Google Search Console + Bing Webmaster Tools day 1, (2) submit to llms.txt aggregators, (3) PR outreach to Hindu/astrology publications, (4) WhatsApp community of priests, (5) partnership with 5 samagri vendors for supply.
**PR:** PR 0.5 (Search Console), PR 14b (llms), PR 15 (GTM playbook).
**Severity:** High (no traffic = no bookings = no revenue).

#### C-T10: No financing / runway plan
**Issue:** $77k/mo burn = $900k/year. We need to know if we have it.
**Fix:** Confirm 18-month runway before PR 12 (when costs jump). Or seed the platform with a free tier that costs less and is break-even at 1k bookings/mo.
**PR:** Roadmap (out of code).
**Severity:** Critical (business).

### Verdict
**APPROVE with 3 PRs deferred to v1.1, Aadhaar/PAN rules tightened, and 2 business items confirmed before PR 12.**

### Roadmap adjustments
- **v1.0 (6 months):** PR 0.5, 1–12, 14a, 15 (core)
- **v1.1 (post-launch, +2 months):** PR 13 (chat), PR 14b (AI), DR drill full, PayU canary
- **v2 (Year 2):** PayU primary, Citus sharding, multi-region active-active, native mobile, more languages

---

## 48. Product Engineer Review (lens: UX, user flows, AEO, conversion, retention)

### Strengths
1. **AEO contract is well-defined** — Speakable + KeyFacts + FAQ. Maps to Google, Bing, AI assistants.
2. **12 page archetypes are concrete** — every page has a template.
3. **Trust score formula is transparent** — users can see why a priest has a Gold badge.
4. **Geocoded directory** — "priests near me" is core to the value prop.

### Concerns

#### C-Pr1: No empty state designs
**Issue:** New users land on an empty dashboard. No bookings, no reviews, no saved items. What do they see?
**Fix:** Empty states with primary CTA: "Book your first puja" / "Find a priest in Delhi" / "Verify your account to start earning". Pre-populated sample data for new users (read-only demo).
**PR:** PR 8 (User), PR 10 (Pandit/Vendor), PR 11 (Priest directory).
**Severity:** High (activation).

#### C-Pr2: Onboarding is unclear
**Issue:** Sign up → ?? New user has no idea what to do.
**Fix:** 3-step onboarding: (1) Verify phone, (2) Pick 1-3 interests (Ganesh puja, marriage, etc.), (3) See personalized recommendations. Or: city + 3 priest recommendations in 1 click.
**PR:** PR 4 (signup), PR 11 (onboarding).
**Severity:** High.

#### C-Pr3: Trust signals are technical, not emotional
**Issue:** "Trust score: 0.87" doesn't move users. "Rajesh has done 1,200 pujas, all 5-star, KYC verified" does.
**Fix:** Human-readable trust: "Verified ✓ • 1,200 pujas • Avg 4.9★ • Replies in 30 min • In your city". Plus photo, video intro, sample mantras.
**PR:** PR 11.
**Severity:** High (conversion).

#### C-Pr4: Pricing transparency missing
**Issue:** Priests can set hourly rate but dakshina is a tradition. What does the user actually pay?
**Fix:** Show breakdown: "Service: ₹2,500 • Samagri: ₹1,200 • Dakshina: optional • Platform fee: ₹250 • Total: ₹3,950". Allow custom tip.
**PR:** PR 11 (listing), PR 12 (booking).
**Severity:** High (trust).

#### C-Pr5: Cancellation policy is hidden
**Issue:** Refund policy is in ToS. Users panic when they need to cancel.
**Fix:** Visible at booking time: "Free cancellation until 48h before. 50% refund 24-48h. No refund within 24h." Reduce support tickets.
**PR:** PR 12.
**Severity:** Med.

#### C-Pr6: No social proof on landing
**Issue:** /priests has 0 reviews shown to a new user. Why trust us?
**Fix:** Aggregate review widget on /: "12,000+ pujas booked, 4.8★ average". Per-priest reviews on detail page.
**PR:** PR 11.
**Severity:** Med.

#### C-Pr7: AEO answers are not user-tested
**Issue:** A 280-char answer is a constraint we imposed on ourselves, not on users. Will they click through? Will it answer the question?
**Fix:** AEO test harness (PR 0.5) + post-launch: monitor `search_priest` MCP tool usage, AEO click-through, "did this answer" feedback. Iterate.
**PR:** PR 0.5, 14b, post-launch.
**Severity:** Med.

#### C-Pr8: Mobile is an afterthought
**Issue:** Plan has Lighthouse perf budget but no mobile-specific design.
**Fix:** Mobile-first design system, bottom nav on mobile, swipe gestures for booking, thumb-reach CTAs, A2HS (Add to Home Screen) prompt.
**PR:** PR 4 (auth mobile), PR 11 (directory), PR 12 (booking).
**Severity:** High (70%+ traffic is mobile in India).

#### C-Pr9: No A/B testing plan beyond "feature flags"
**Issue:** Feature flags ≠ A/B testing. A/B test needs hypothesis, primary metric, sample size, statistical significance.
**Fix:** Experiment framework: hypothesis template, metric catalog, sequential testing. Top 5 experiments pre-launch: (1) trust score visibility, (2) pricing display, (3) review count threshold, (4) "Book Now" vs "View Profile", (5) Hindi vs English default.
**PR:** PR 15.
**Severity:** Med.

#### C-Pr10: Notifications are an afterthought
**Issue:** Plan has notification catalog (15 templates) but no strategy for what triggers a notification, frequency cap, opt-out, time zone.
**Fix:** Notification policy: max 3/day per user, quiet hours 22:00–08:00 local, default opt-in for transactional, opt-in for marketing, batch non-urgent.
**PR:** PR 13.
**Severity:** Med.

### Action items for PRs
- C-Pr1 → PR 8, 10, 11
- C-Pr2 → PR 4, 11
- C-Pr3 → PR 11
- C-Pr4 → PR 11, 12
- C-Pr5 → PR 12
- C-Pr6 → PR 11
- C-Pr7 → PR 0.5, 14b, post-launch
- C-Pr8 → PR 4, 11, 12
- C-Pr9 → PR 15
- C-Pr10 → PR 13

### Verdict
**APPROVE with the 10 concerns folded into the relevant PRs.** Product experience is the most important thing to get right; we'll iterate post-launch.

---

## 49. Consolidated Audit Findings

| # | Concern | Severity | Owner | PR |
|---|---|---|---|---|
| C-S1 | Soft-delete + RLS | High | eng-A | PR 10 |
| C-S2 | OpenSearch vector size | Med | eng-C | PR 11 |
| C-S3 | Server actions ≠ HTTP | Med | eng-A | PR 10, 12 |
| C-S4 | Vercel cold starts | Low | eng-A | PR 2 |
| C-S5 | Drizzle migration tx | High | eng-C | PR 10 |
| C-S6 | Outbox dispatcher SPOF | Med | eng-C | PR 13 |
| C-S7 | Webhook idempotency TTL | High | eng-A | PR 12 |
| C-S8 | Dashboard bundle size | Med | eng-B | PR 8 |
| C-S9 | Contract tests | Med | eng-C | PR 12 |
| C-S10 | PII in logs | Critical | eng-C | PR 11 |
| C-P1 | Over-modularization | Med | all | all |
| C-P2 | Duplicate IAM tables | Med | eng-A | PR 3 |
| C-P3 | Saga overkill | Low | eng-C | PR 12 |
| C-P4 | Soft-delete bloat | Med | eng-A | PR 10, 15 |
| C-P5 | pgvector vs OpenSearch | Med | eng-C | PR 11, 12, 15 |
| C-P6 | Event schema versioning | High | eng-C | PR 13 |
| C-P7 | No sharding plan | Low/Med | eng-A | PR 10, 15, Y2 |
| C-P8 | AI plugin deprecation | Low | eng-C | PR 14b |
| C-P9 | AEO testability | Med | eng-C | PR 0.5, 11 |
| C-P10 | Hex layer leak (auth) | High | eng-A | PR 1, 2 |
| C-T1 | Timeline aggressive | Med | eng-A | roadmap |
| C-T2 | Vendor lock-in | Med | eng-A | PR 12, v1.1 |
| C-T3 | Aadhaar/PAN | Critical | eng-A | PR 12 |
| C-T4 | GST e-invoice hard | High | eng-A | PR 12 |
| C-T5 | 194-O TDS | High | eng-A | PR 12 |
| C-T6 | 20 authors risk | Med | eng-C | PR 14b |
| C-T7 | Hindi quality | Med | eng-C | PR 14a |
| C-T8 | Customer success gap | Med | eng-B | PR 8, 11, 15 |
| C-T9 | No GTM plan | High | eng-C | PR 0.5, 14b, 15 |
| C-T10 | Runway | Critical | founder | pre-PR 12 |
| C-Pr1 | Empty states | High | eng-B | PR 8, 10, 11 |
| C-Pr2 | Onboarding | High | eng-B | PR 4, 11 |
| C-Pr3 | Trust signals | High | eng-C | PR 11 |
| C-Pr4 | Pricing transparency | High | eng-C | PR 11, 12 |
| C-Pr5 | Cancellation policy | Med | eng-A | PR 12 |
| C-Pr6 | Social proof | Med | eng-C | PR 11 |
| C-Pr7 | AEO user-test | Med | eng-C | PR 0.5, 14b |
| C-Pr8 | Mobile-first | High | eng-B | PR 4, 11, 12 |
| C-Pr9 | A/B testing | Med | eng-A | PR 15 |
| C-Pr10 | Notification strategy | Med | eng-C | PR 13 |

**Summary:** 41 concerns. 4 critical (PII logs, Aadhaar/PAN, runway, hex layer). 12 high. 21 medium. 4 low. All have owners and PRs.

---


# PART VIII — UNIFIED IDENTITY CONTROL PANEL (UIDP) INTEGRATION

The UIDP is our **inhouse identity service** that already exists. It is the single source of truth for identity. Our custom backend (the Navdhya application) integrates with UIDP for all identity operations. This section defines the integration contract.

---

## 50. UIDP: What It Is (assumed contract from the inhouse system)

The inhouse UIDP is a managed identity service that exposes:

### Capabilities
- Account creation, retrieval, update, soft-delete, restore, hard-delete
- Phone/email verification (OTP)
- Password, OTP, magic-link, passkey (WebAuthn), OAuth, SSO
- MFA: TOTP, SMS, WhatsApp
- Session management (multi-device, refresh, revoke)
- Device fingerprinting and trust
- RBAC roles and permissions
- ABAC policies (Cedar-style)
- Tenant membership (one home + many guests)
- KYC: Aadhaar (last 4 + token), PAN (last 4 + hash), bank penny-drop
- Background check status (returned, not stored)
- Consent ledger (versioned, granular)
- Audit log (immutable, signed, queryable)
- Account linking (multiple identities → one)
- Account recovery (backup codes, social)
- Bans, suspensions, account locks
- Feature flag per user
- A/B test assignment
- API keys (per service, hashed, scoped)
- Webhooks (in/out)
- Real-time events (SSE / webhook)
- OIDC provider (so we can use UIDP as our IdP)
- Rate limit per identity
- Quotas per tier

### What UIDP is **not** (out of UIDP scope, handled in our backend)
- Profile details (name, photo, address) — stored in our `iam_*` tables, indexed by `uidp_account_id`
- Booking state, payments, content — all in our app
- Per-app preferences (language, theme, A11y) — our app
- Roles specific to Navdhya domain (Priest, Vendor, etc.) — our app
- App-specific feature flags (per-app)

---

## 51. UIDP Integration Architecture

```
   Browser
     │
     ▼
   CDN (Cloudflare)
     │
     ▼
   Navdhya App (Next.js, Vercel)              ◄── our custom backend
     │                                           (this is what we build)
     ├── reads uidp_session cookie
     ├── proxy.ts verifies via uidp-adapter
     │
     ├── application/usecases/*.ts
     │     │
     │     ├── auth.usecase → UIDP (auth API)
     │     ├── profile.usecase → UIDP + local DB
     │     ├── booking.usecase → local DB + UIDP (id only)
     │     └── ...
     │
     ├── infra/uidp/                          ◄── our UIDP integration
     │     ├── adapter.ts                     ◄── implements UidpPort
     │     ├── client.ts                      ◄── HTTP client
     │     ├── types.ts                       ◄── our type-safe Uidp* types
     │     ├── mock.ts                        ◄── local mock for dev
     │     ├── events.ts                      ◄── webhook handlers
     │     └── sso.ts                         ◄── OIDC integration
     │
     └── workers/
           └── uidp-sync.ts                   ◄── pulls user updates from UIDP webhooks

   UIDP (inhouse service)                     ◄── not built by us
     ├── /accounts
     ├── /sessions
     ├── /credentials
     ├── /mfa
     ├── /oauth
     ├── /roles
     ├── /policies
     ├── /tenants
     ├── /kyc
     ├── /consent
     ├── /audit
     └── /webhooks (in/out)
```

**Key principle:** UIDP is the identity authority. Our app **never stores identity-critical data** (passwords, OTP secrets, OAuth tokens, MFA secrets). It stores **profile, content, booking, payment** data, indexed by `uidp_account_id`.

---

## 52. UIDP Ports (our integration interface)

```ts
// ports/uidp.ts

export interface UidpPort {
  // Account
  createAccount(input: CreateAccountInput): Promise<UidpAccount>;
  getAccount(id: UidpAccountId): Promise<UidpAccount | null>;
  updateAccount(id: UidpAccountId, patch: UpdateAccountInput): Promise<UidpAccount>;
  softDeleteAccount(id: UidpAccountId, reason: string): Promise<void>;
  hardDeleteAccount(id: UidpAccountId, evidenceUrl: string): Promise<void>;
  restoreAccount(id: UidpAccountId): Promise<UidpAccount>;

  // Verification
  startPhoneVerification(phone: E164): Promise<{ challengeId: string; ttl: number }>;
  verifyPhone(challengeId: string, code: string): Promise<{ verified: boolean }>;
  startEmailVerification(email: Email): Promise<{ challengeId: string }>;
  verifyEmail(challengeId: string, code: string): Promise<{ verified: boolean }>;

  // Credentials
  setPassword(accountId: UidpAccountId, password: string): Promise<void>;
  verifyPassword(accountId: UidpAccountId, password: string): Promise<boolean>;
  rotatePassword(accountId: UidpAccountId, oldPassword: string, newPassword: string): Promise<void>;
  startPasswordReset(phone: E164): Promise<{ challengeId: string }>;
  completePasswordReset(challengeId: string, code: string, newPassword: string): Promise<void>;

  // OTP
  sendOtp(target: E164 | Email, channel: 'sms' | 'whatsapp' | 'email'): Promise<{ challengeId: string; ttl: number }>;
  verifyOtp(challengeId: string, code: string): Promise<{ verified: boolean; accountId?: UidpAccountId }>;

  // Magic link
  sendMagicLink(email: Email, redirectUrl: string): Promise<{ token: string }>;
  verifyMagicLink(token: string): Promise<{ accountId: UidpAccountId; sessionId: SessionId }>;

  // Passkey
  startPasskeyRegistration(accountId: UidpAccountId): Promise<PublicKeyCredentialCreationOptions>;
  completePasskeyRegistration(accountId: UidpAccountId, attestation: AuthenticatorAttestationResponse): Promise<void>;
  startPasskeyAuthentication(accountId?: UidpAccountId): Promise<PublicKeyCredentialRequestOptions>;
  completePasskeyAuthentication(assertion: AuthenticatorAssertionResponse): Promise<{ accountId: UidpAccountId; sessionId: SessionId }>;

  // OAuth
  getOAuthAuthorizationUrl(provider: 'google' | 'facebook' | 'apple', state: string, redirectUri: string): Promise<string>;
  exchangeOAuthCode(provider: string, code: string, redirectUri: string): Promise<{ accountId: UidpAccountId; sessionId: SessionId }>;

  // SSO (OIDC)
  ssoStart(tenantId: TenantId, redirectUri: string): Promise<{ url: string; state: string }>;
  ssoComplete(state: string, params: Record<string, string>): Promise<{ accountId: UidpAccountId; sessionId: SessionId; tenantId: TenantId }>;

  // MFA
  enrollMfa(accountId: UidpAccountId, factor: 'totp' | 'sms' | 'whatsapp'): Promise<{ secret?: string; qrCodeUrl?: string; factorId: string }>;
  confirmMfa(accountId: UidpAccountId, factorId: string, code: string): Promise<void>;
  verifyMfa(accountId: UidpAccountId, code: string): Promise<{ verified: boolean }>;
  disableMfa(accountId: UidpAccountId, factorId: string): Promise<void>;
  generateBackupCodes(accountId: UidpAccountId): Promise<string[]>;

  // Session
  createSession(accountId: UidpAccountId, device: DeviceFingerprint, ip: string, ua: string): Promise<{ sessionId: SessionId; token: string; expiresAt: Date }>;
  validateSession(token: string): Promise<{ accountId: UidpAccountId; sessionId: SessionId; deviceId: DeviceId; roles: RoleId[]; tenantIds: TenantId[] } | null>;
  refreshSession(token: string): Promise<{ token: string; expiresAt: Date }>;
  revokeSession(sessionId: SessionId, reason: string): Promise<void>;
  revokeAllSessions(accountId: UidpAccountId, exceptSessionId?: SessionId): Promise<void>;
  listSessions(accountId: UidpAccountId): Promise<Session[]>;

  // Device
  trustDevice(deviceId: DeviceId, accountId: UidpAccountId): Promise<{ trustLevel: 'low' | 'medium' | 'high' }>;
  revokeDevice(deviceId: DeviceId): Promise<void>;

  // RBAC
  assignRole(accountId: UidpAccountId, roleId: RoleId, tenantId: TenantId, scope?: ScopeExpr): Promise<void>;
  revokeRole(accountId: UidpAccountId, roleId: RoleId, tenantId: TenantId): Promise<void>;
  listRoles(accountId: UidpAccountId): Promise<RoleAssignment[]>;
  hasRole(accountId: UidpAccountId, roleId: RoleId, tenantId?: TenantId): Promise<boolean>;
  checkPermission(accountId: UidpAccountId, perm: PermissionKey, resource?: Resource): Promise<{ allowed: boolean; reason: string }>;

  // ABAC (Cedar-style)
  evaluatePolicies(accountId: UidpAccountId, action: string, resource: Resource, context: Record<string, unknown>): Promise<{ allowed: boolean; matchedPolicies: string[] }>;

  // Tenant
  addToTenant(accountId: UidpAccountId, tenantId: TenantId, roleId: RoleId): Promise<void>;
  removeFromTenant(accountId: UidpAccountId, tenantId: TenantId): Promise<void>;
  listTenants(accountId: UidpAccountId): Promise<TenantMembership[]>;
  setHomeTenant(accountId: UidpAccountId, tenantId: TenantId): Promise<void>;

  // KYC
  startKyc(accountId: UidpAccountId, kind: 'aadhaar' | 'pan' | 'bank'): Promise<{ verificationId: string; redirectUrl?: string }>;
  getKycStatus(accountId: UidpAccountId, kind: 'aadhaar' | 'pan' | 'bank'): Promise<{ status: 'pending' | 'verified' | 'failed' | 'expired'; last4?: string; verifiedAt?: Date; expiresAt?: Date }>;

  // Consent
  recordConsent(accountId: UidpAccountId, kind: ConsentKind, version: string, granted: boolean, ip: string, ua: string): Promise<void>;
  listConsents(accountId: UidpAccountId): Promise<Consent[]>;
  revokeConsent(accountId: UidpAccountId, kind: ConsentKind): Promise<void>;

  // Audit
  queryAudit(filters: AuditFilters): Promise<AuditEvent[]>;
  exportAudit(accountId: UidpAccountId, format: 'json' | 'csv'): Promise<{ url: string; expiresAt: Date }>;

  // Account linking
  linkIdentity(accountId: UidpAccountId, kind: 'phone' | 'email' | 'oauth', value: string): Promise<void>;
  unlinkIdentity(accountId: UidpAccountId, identityId: IdentityId): Promise<void>;
  listIdentities(accountId: UidpAccountId): Promise<Identity[]>;

  // Bans / locks
  lockAccount(accountId: UidpAccountId, reason: string, until?: Date): Promise<void>;
  unlockAccount(accountId: UidpAccountId): Promise<void>;
  banAccount(accountId: UidpAccountId, reason: string, evidenceUrl: string): Promise<void>;

  // API keys (per service)
  createApiKey(serviceName: string, scopes: string[]): Promise<{ keyId: string; secret: string }>;
  rotateApiKey(keyId: string): Promise<{ secret: string }>;
  revokeApiKey(keyId: string): Promise<void>;

  // Webhooks
  registerWebhook(event: UidpEvent, url: string, secret: string): Promise<void>;
  listWebhooks(): Promise<Webhook[]>;
  rotateWebhookSecret(webhookId: string): Promise<{ secret: string }>;

  // Feature flags & experiments
  getFlag(accountId: UidpAccountId, key: string): Promise<{ value: unknown; variant: string }>;
  getExperiment(accountId: UidpAccountId, key: string): Promise<{ variant: string; inExperiment: boolean }>;

  // OIDC (we are an OIDC client to UIDP)
  getOidcDiscovery(): Promise<OidcDiscovery>;
}
```

---

## 53. Our Local Identity Tables (what we add on top of UIDP)

We never duplicate UIDP. We add **profile, preferences, and domain bindings** that are app-specific.

```ts
// our schema, separate from UIDP

// Profile (1:1 with uidp_account_id)
profile {
  id: uuid pk
  uidp_account_id: uuid not null unique  // FK to UIDP
  display_name: text
  full_name: text
  avatar_url: text
  bio: text
  date_of_birth: date
  gender: text
  locale: text default 'en-IN'
  timezone: text default 'Asia/Kolkata'
  city_id: uuid ref dir_city
  area_id: uuid ref dir_area
  created_at, updated_at, deleted_at
}

// Preferences (1:1 with uidp_account_id)
preference {
  id: uuid pk
  uidp_account_id: uuid not null unique
  theme: text default 'system'
  language: text default 'en'
  notification_email: bool default true
  notification_sms: bool default true
  notification_whatsapp: bool default false
  notification_push: bool default true
  notification_inapp: bool default true
  quiet_hours_start: time
  quiet_hours_end: time
  a11y_high_contrast: bool default false
  a11y_reduce_motion: bool default false
  a11y_font_size: text default 'md'
  marketing_opt_in: bool default false
  created_at, updated_at
}

// Domain binding (1:N with uidp_account_id)
account_role_binding {
  id: uuid pk
  uidp_account_id: uuid not null
  domain_role: text not null
  tenant_id: uuid
  status: text default 'active'
  verified_at: timestamp
  kyc_level: int default 0
  created_at, updated_at
  unique (uidp_account_id, domain_role, tenant_id)
}

// Saved items (User)
saved {
  id: uuid pk
  uidp_account_id: uuid not null
  target_type: text
  target_id: uuid
  collection: text
  created_at
}

// Session mirror (for fast reads; UIDP is source of truth)
session_mirror {
  session_id: uuid pk
  uidp_account_id: uuid
  device_id: uuid
  ip: text
  ua: text
  created_at, expires_at, revoked_at
}

// Following (User → Priest, Temple)
follow {
  id: uuid pk
  uidp_account_id: uuid not null
  target_type: text
  target_id: uuid
  created_at
}
```

**What this gives us:**
- Fast reads (no UIDP round-trip on every page load)
- Domain-specific roles (Priest, Vendor, etc.)
- App-specific preferences
- Saved items and follows (we own this)
- Local cache of UIDP sessions for proxy.ts speed

---

## 54. UIDP Webhook Events (we listen to)

| Event | Payload | Our handler |
|---|---|---|
| `uidp.account.created` | `{accountId, phone, email, createdAt}` | Insert `account_role_binding` with `domain_role='user'`, send welcome email |
| `uidp.account.deleted` | `{accountId, reason, evidenceUrl}` | Soft-delete our profile, mark bookings, queue data export |
| `uidp.account.banned` | `{accountId, reason, evidenceUrl}` | Force logout, mark all bookings as `cancelled` |
| `uidp.account.locked` | `{accountId, reason, until}` | Mark `account_role_binding.status='suspended'`, block login |
| `uidp.account.unlocked` | `{accountId}` | Restore status |
| `uidp.kyc.verified` | `{accountId, kind, last4, verifiedAt}` | Update `account_role_binding.kyc_level`, enable bookings/payouts |
| `uidp.kyc.failed` | `{accountId, kind, reason}` | Notify user, block dependent action |
| `uidp.kyc.expired` | `{accountId, kind, expiredAt}` | Re-verify flow |
| `uidp.session.created` | `{sessionId, accountId, deviceId, ip, ua, createdAt}` | Mirror to `session_mirror` |
| `uidp.session.revoked` | `{sessionId, reason}` | Remove from `session_mirror`, force re-auth |
| `uidp.mfa.enabled` | `{accountId, factor, factorId}` | Audit log |
| `uidp.mfa.disabled` | `{accountId, factorId}` | Audit log |
| `uidp.role.assigned` | `{accountId, roleId, tenantId, scope}` | Update `account_role_binding`, audit log |
| `uidp.role.revoked` | `{accountId, roleId, tenantId}` | Update `account_role_binding`, force re-auth |
| `uidp.consent.granted` | `{accountId, kind, version, ip, ua, ts}` | Audit log |
| `uidp.consent.revoked` | `{accountId, kind}` | Disable dependent features, audit log |
| `uidp.password.changed` | `{accountId, ts}` | Force other sessions to re-auth |
| `uidp.phone.changed` | `{accountId, oldPhoneHash, newPhoneHash, ts}` | Update profile if synced, force re-auth |
| `uidp.email.changed` | `{accountId, oldEmailHash, newEmailHash, ts}` | Same |
| `uidp.flag.changed` | `{accountId, key, value, variant}` | Invalidate feature-flag cache |

Webhook delivery: HMAC-SHA256 signed, retries 3, 24h idempotency on `event_id`.

---

## 55. UIDP Integration in Our Usecases

### Example: sign up

```ts
// application/usecases/auth.usecase.ts
export async function signUp(input: SignUpInput): Promise<SignUpResult> {
  return withTransaction(async (tx) => {
    const uidpAccount = await uidp.createAccount({
      phone: input.phone,
      email: input.email,
      tenantId: input.tenantId,
      metadata: { source: 'web_signup', campaign: input.campaign },
    });

    await tx.insert(profile).values({
      uidp_account_id: uidpAccount.id,
      display_name: input.displayName,
      locale: 'en-IN',
    });

    await tx.insert(accountRoleBinding).values({
      uidp_account_id: uidpAccount.id,
      domain_role: 'user',
      status: 'active',
    });

    await tx.insert(preference).values({
      uidp_account_id: uidpAccount.id,
    });

    const challenge = await uidp.sendOtp(input.phone, 'sms');

    return { uidpAccountId: uidpAccount.id, challengeId: challenge.challengeId };
  });
}
```

### Example: book a priest

```ts
// application/usecases/booking.usecase.ts
export async function bookPriest(input: BookPriestInput, ctx: RequestContext): Promise<Booking> {
  const permCheck = await uidp.checkPermission(ctx.uidpAccountId, 'booking.create', {
    kind: 'booking',
    tenantId: ctx.tenantId,
    priestId: input.priestId,
    scheduledAt: input.scheduledAt,
  });
  if (!permCheck.allowed) throw new ForbiddenError(permCheck.reason);

  return withTransaction(async (tx) => {
    const booking = await tx.insert(bookBooking).values({...}).returning();
    await tx.insert(bookBookingEvent).values({...state: 'DRAFT'...});
    await tx.insert(billQuote).values({...});
    await tx.insert(jobOutbox).values({...event: 'book.booking.created.v1'...});
    return booking[0];
  });
}
```

---

## 56. UIDP Mock for Dev

In dev and tests, we use a local mock that implements `UidpPort` backed by SQLite or in-memory. The mock supports all methods and persists across hot reloads. This lets us develop without a UIDP dependency.

```ts
// infra/uidp/mock.ts
export function createMockUidp(): UidpPort {
  return {
    createAccount: async (input) => ({ id: randomUUID(), ... }),
  };
}
```

In prod, we use the real adapter that calls UIDP over HTTPS. Switch via env var `UIDP_ADAPTER=mock|http`.

---

## 57. UIDP Integration PRs

| PR | What | Notes |
|---|---|---|
| PR 1 | Add `UidpPort` interface, mock impl, our local schema (`profile`, `preference`, `account_role_binding`, `session_mirror`, `saved`, `follow`) | Foundation |
| PR 2 | Real HTTP adapter (with retries, circuit breaker, fallback to cached session), `proxy.ts` reads session, calls UIDP `validateSession` once per request, caches in Redis | Auth enforced |
| PR 3 | Webhook handler for all UIDP events, local mirror sync, ABAC, RBAC, audit | Full integration |
| PR 4 | Sign up, login, OTP, password, magic link, passkey, OAuth, SSO, MFA — all via UIDP ports | Auth UX done |
| PR 5 | Profile, preference, theme, language, A11y settings | User controls |
| PR 8 | Dashboard data reads our `account_role_binding` + UIDP roles | Dashboards work |
| PR 11 | Priest/Vendor signup flow: UIDP account + `account_role_binding` + KYC start | Supply side |
| PR 12 | Payouts require KYC verified (`uidp.kyc.verified` for `bank`) | Compliance |
| PR 14a | Locale preference drives i18n | Hindi |

**UIDP is the foundation. We never bypass it. Our backend is a thin orchestration layer over UIDP + our domain.**

---

## 58. UIDP Failure Modes (and how we degrade)

| Failure | Detection | Mitigation |
|---|---|---|
| UIDP 5xx | circuit breaker, latency spike | Use cached session; show "service degraded"; queue writes |
| UIDP 4xx | per-request | Surface error to user |
| UIDP timeout (>1s) | request timeout | Use cached session; for writes, fail closed with "try again" |
| UIDP webhook missed | reconciliation cron | Pull events from UIDP `/events?since=...` every 5m |
| UIDP rotated signing key | 401 on webhook | Auto-fetch new key from JWKS |
| UIDP full outage | health check | Read-only mode: allow browsing, block writes; show status page |

---

## 59. UIDP Security Model

- **In transit:** mTLS between our backend and UIDP. Certs in Secrets Manager. Rotation 90d.
- **In rest (cached sessions):** AES-256 in Redis, KMS-managed key.
- **Auth to UIDP:** Service-to-service JWT (RS256) with audience `navdhya`. Issued by UIDP. 1h TTL, refresh 30d.
- **Per-user session:** Opaque token, 30-day TTL, refresh-on-use. Stored in Redis.
- **Webhook signature:** HMAC-SHA256. Secret per webhook endpoint, rotated 180d.
- **Least privilege:** Our service account has scopes: `accounts:read, accounts:write, sessions:read, sessions:write, kyc:read, audit:read`. No `*` scope.
- **Audit:** Every UIDP call logged to our `iam_audit` with the UIDP op name.

---


# PART IX — DASHBOARD MENU PLANNING

For each of the 6 roles, the dashboard has a left sidebar (desktop) or bottom sheet drawer (mobile), organized by **frequency of use** and **task value**.

Notation: `[P]` = primary nav, `[S]` = secondary, `[Q]` = quick action (top right), `[A]` = account menu (top right).

---

## 60. User Dashboard Menu

### Top bar (always visible)
- Logo (home link)
- Search (global)
- Notifications bell `[Q]`
- "Book a Puja" CTA `[Q]` (primary, orange)
- Avatar `[A]` (Profile, Settings, Help, Logout)

### Left sidebar — Primary
1. **Overview** (`/user/overview`) — `P`
2. **Bookings** (`/user/bookings`) — `P` — most-used
   - Upcoming | Past | Cancelled | Drafts
3. **Saved** (`/user/saved`) — `P` — collections of priests, temples, services
4. **Inbox** (`/user/inbox`) — `P` — chat with priest, system messages
5. **Payments** (`/user/payments`) — `P` — invoices, refunds, payment methods

### Left sidebar — Secondary
6. **Profile** (`/user/profile`) — `S`
7. **Notifications** (`/user/notifications`) — `S` — granular settings
8. **Support** (`/user/support`) — `S` — tickets, FAQ, contact
9. **Settings** (`/user/settings`) — `S`
   - Account (UIDP-managed)
   - Privacy (consent ledger)
   - A11y
   - Language
   - Theme
   - Data export / delete (DPDP)
   - Sessions
   - Connected apps

### Quick actions (top right)
- `Book a Puja` (primary CTA, orange)
- Notifications
- Avatar

### Mobile bottom nav (5 items)
- Home
- Book
- Inbox
- Saved
- Profile

### Empty states
- **No bookings yet:** "Book your first puja" CTA + 3 recommended priests
- **No saved:** "Browse priests in your city" + curated lists
- **No payments:** "Your payment history will appear here"

### Loading states
- Skeleton cards (matching layout) for all sections
- Skeleton tables for lists
- Skeleton chart for analytics
- Optimistic updates for writes

### Error states
- Inline error on action failure (toast)
- Section-level error: "Couldn't load. Retry."
- Full-page error: 500 page with Sentry ID

---

## 61. Pandit Dashboard Menu

### Top bar
- Logo
- Search (bookings, customers)
- Notifications
- "Update Availability" `[Q]` (today)
- Avatar

### Left sidebar — Primary
1. **Overview** (`/pandit/overview`) — `P` — today's jobs, this week's earnings, alerts
2. **Jobs** (`/pandit/jobs`) — `P` — assigned, requested, in-progress
3. **Calendar** (`/pandit/calendar`) — `P` — availability, slots, exceptions
4. **Earnings** (`/pandit/earnings`) — `P` — daily/weekly/monthly, trend
5. **Payouts** (`/pandit/payouts`) — `P` — pending, completed, bank accounts

### Left sidebar — Secondary
6. **Profile** (`/pandit/profile`) — `S` — bio, photo, video intro, services, deities, languages
7. **Reviews** (`/pandit/reviews`) — `S` — read, respond, average rating trend
8. **KYC & Verification** (`/pandit/kyc`) — `S` — Aadhaar, PAN, bank, status
9. **Availability** (`/pandit/availability`) — `S` — weekly template, exceptions, time off
10. **Settings** (`/pandit/settings`) — `S`
    - Account (UIDP)
    - Service radius
    - Notification preferences
    - Privacy / DPDP
    - Sessions
    - Connected apps

### Quick actions
- "Set out-of-office today" `[Q]`
- "Respond to last review" `[Q]` (if pending)
- Notifications
- Avatar

### Mobile bottom nav
- Home
- Jobs
- Calendar
- Earnings
- Profile

### Empty states
- **No jobs yet:** "Complete your profile to start receiving bookings" + checklist
- **No earnings yet:** "Your earnings will appear after your first completed booking"
- **No payouts yet:** "Add your bank account to receive payouts"

---

## 62. Vendor Dashboard Menu

### Top bar
- Logo
- Search (orders, products)
- Notifications
- "Add Product" `[Q]`
- Avatar

### Left sidebar — Primary
1. **Overview** (`/vendor/overview`) — `P` — orders today, low stock, alerts
2. **Orders** (`/vendor/orders`) — `P` — new, processing, dispatched, delivered
3. **Inventory** (`/vendor/inventory`) — `P` — products, stock levels, low-stock alerts
4. **Finance** (`/vendor/finance`) — `P` — revenue, expenses, margins
5. **Payouts** (`/vendor/payouts`) — `P` — pending, completed

### Left sidebar — Secondary
6. **Staff** (`/vendor/staff`) — `S` — invite, roles
7. **Profile** (`/vendor/profile`) — `S` — business info, photos, GSTIN
8. **Catalog** (`/vendor/catalog`) — `S` — services, samagri, packages
9. **Reports** (`/vendor/reports`) — `S` — sales, top products, customer cohorts
10. **Settings** (`/vendor/settings`) — `S`
    - Account
    - KYC (GSTIN, bank, PAN)
    - Notifications
    - Privacy
    - Sessions
    - API keys

### Quick actions
- "Add Product" `[Q]`
- "Print today's orders" `[Q]`
- Notifications
- Avatar

### Mobile bottom nav
- Home
- Orders
- Inventory
- Finance
- Profile

### Empty states
- **No orders:** "Add your first product" + catalog template
- **No inventory:** "Bulk import" or "Add manually"
- **No payouts:** "Complete KYC to start receiving payouts"

---

## 63. Manager Dashboard Menu

### Top bar
- Logo
- Search (priests, vendors, bookings)
- Notifications
- "Pending Approvals" `[Q]` (badge with count)
- Avatar

### Left sidebar — Primary
1. **Overview** (`/manager/overview`) — `P` — pending queue, escalations, KPIs
2. **Approvals** (`/manager/approvals`) — `P` — KYC, profile, payout
3. **Queue** (`/manager/queue`) — `P` — all open items (priority order)
4. **Disputes** (`/manager/disputes`) — `P` — open, in-mediation, closed
5. **Analytics** (`/manager/analytics`) — `P` — bookings, GMV, trust scores

### Left sidebar — Secondary
6. **Priests** (`/manager/priests`) — `S` — list, filter, search
7. **Vendors** (`/manager/vendors`) — `S`
8. **Escalations** (`/manager/escalations`) — `S` — Tier 2 / Tier 3
9. **Audit** (`/manager/audit`) — `S` — read-only audit log
10. **Settings** (`/manager/settings`) — `S`
    - Notifications
    - On-call rotation
    - Signature for refund approvals
    - Privacy

### Quick actions
- "Approve 5 pending KYC" `[Q]`
- Notifications
- Avatar

### Mobile bottom nav
- Home
- Approvals
- Queue
- Disputes
- Profile

### Empty states
- **No pending approvals:** "All caught up ✓" + last 5 actions
- **No disputes:** "No active disputes"
- **No escalations:** "0 escalations today"

---

## 64. Admin Dashboard Menu

### Top bar
- Logo
- Search
- Notifications
- "Create User" `[Q]`
- Avatar

### Left sidebar — Primary
1. **Overview** (`/admin/overview`) — `P` — system health, pending actions
2. **Users** (`/admin/users`) — `P` — search, list, filter
3. **Roles & Permissions** (`/admin/roles`) — `P` — CRUD, ABAC editor
4. **Content** (`/admin/content`) — `P` — 1,265 pages, drafts, scheduled
5. **Moderation** (`/admin/moderation`) — `P` — reviews, photos, takedowns
6. **Taxonomy** (`/admin/taxonomy`) — `P` — deities, traditions, languages

### Left sidebar — Secondary
7. **Coupons** (`/admin/coupons`) — `S`
8. **Campaigns** (`/admin/campaigns`) — `S`
9. **Audit** (`/admin/audit`) — `S`
10. **Settings** (`/admin/settings`) — `S`
    - System
    - Localization
    - AEO settings
    - Feature flags
    - Privacy
    - DPDP / GDPR

### Quick actions
- "Bulk import priests" `[Q]`
- "Publish scheduled content" `[Q]`
- Notifications
- Avatar

### Mobile bottom nav
- Home
- Users
- Content
- Moderation
- Profile

---

## 65. SuperAdmin Dashboard Menu

### Top bar
- Logo
- Search (global)
- Notifications
- "Switch Tenant" `[Q]`
- Avatar (with tenant badge)

### Left sidebar — Primary
1. **Overview** (`/platform/overview`) — `P` — global health, SLOs, costs
2. **Tenants** (`/platform/tenants`) — `P` — list, create, suspend
3. **Plans & Billing** (`/platform/plans`) — `P` — SKUs, prices, features
4. **Feature Flags** (`/platform/flags`) — `P` — global, per-tenant
5. **Observability** (`/platform/observability`) — `P` — SLOs, alerts, costs

### Left sidebar — Secondary
6. **Incidents** (`/platform/incidents`) — `S`
7. **Audit** (`/platform/audit`) — `S`
8. **System** (`/platform/system`) — `S` — DB, queue, search, cache health
9. **Settings** (`/platform/settings`) — `S`
10. **Compliance** (`/platform/compliance`) — `S` — DPDP, GST, PCI status

### Quick actions
- "Tenant health" `[Q]`
- "Cost report" `[Q]`
- Notifications
- Avatar

### Mobile bottom nav
- Home
- Tenants
- Observability
- Audit
- Profile

---

## 66. Common Patterns Across All Dashboards

### Layout
- **Desktop:** left sidebar (240px collapsible to 64px), top bar (56px), main content (max-width 1200px centered)
- **Tablet:** sidebar collapses to icons
- **Mobile:** bottom nav (5 items), top bar simplified, hamburger for full sidebar (drawer)

### Header in main
- Breadcrumb (Home / Section / Subsection)
- Section title + description
- Primary action button (right-aligned)
- Filters (right-aligned, collapsible)

### Data display
- Cards: 4-col grid on desktop, 2-col on tablet, 1-col on mobile
- Tables: zebra stripes, sticky header, pagination, sort, filter
- Charts: Recharts, tooltips, export CSV, time range picker
- Lists: avatar + primary + secondary + status badge + actions menu

### Actions
- Server actions for writes (form-based)
- Confirmation dialog for destructive actions
- Toast on success/error
- Optimistic UI for fast actions
- Audit log entry for sensitive actions

### Empty / loading / error
- Empty: icon + message + primary CTA
- Loading: skeleton matching final layout
- Error: icon + message + retry button + Sentry ID

### A11y
- All interactive elements keyboard-navigable
- Focus ring visible
- ARIA labels
- High-contrast mode
- Reduce motion
- Font size scaling (sm/md/lg)

### Performance
- All sections code-split (dynamic import)
- RSC by default, client only for interactive bits
- Suspense boundaries per section
- Skeleton during loading
- Stale-while-revalidate for read-heavy sections

---

# PART X — USER FLOWS AND SCENARIOS

## 67. Personas

| # | Persona | Role | Goal | Tech savvy | Device | Key pain |
|---|---|---|---|---|---|---|
| P1 | **Anita, 35, Delhi** | User (householder) | Book a Satyanarayan puja for her family | Med | Mobile | Trust (will the priest be on time? quality?) |
| P2 | **Ramesh, 50, Mumbai** | Pandit | Earn ₹50k/mo, get more bookings | Low | Mobile + tablet | Admin (managing calendar, KYC, payouts) |
| P3 | **Suresh, 40, Bangalore** | Vendor (samagri) | Sell puja supplies, deliver fast | Med | Mobile + desktop | Inventory (stock-outs, returns) |
| P4 | **Priya, 28, Pune** | Manager (ops) | Approve priests, resolve disputes | High | Desktop | Volume (50 approvals/day) |
| P5 | **Kavita, 32, Hyderabad** | Admin (content) | Manage 1,265 pages, AEO | High | Desktop | Quality (Hindi, AEO, authors) |
| P6 | **Vikram, 38, Bangalore** | SuperAdmin | Run platform, plan infra | High | Desktop | Visibility (SLOs, cost, incidents) |
| P7 | **Anita's mother, 65** | User (senior) | Book a puja for the family | Low | Mobile (basic) | UI complexity, English |
| P8 | **Anita's son, 12** | User (child, supervised) | Not yet a user (13+ gate) | n/a | n/a | n/a |
| P9 | **Ramesh's son, 22** | Pandit (apprentice) | Help Ramesh manage calendar | High | Mobile | Permissions (sub-account) |
| P10 | **3rd-party integrator** | API consumer | Build on top (MCP client) | High | API | API quality, docs |

---

## 68. User Happy Path: Book a Puja (P1: Anita)

```
Step 1: Land on /
  - Hero with search bar
  - "Search by puja, priest, temple, or city"
  - 6 popular categories
  - Trust signals: "12,000+ pujas booked, 4.8★ average"
  - AEO answer at top: "Navdhya connects you with verified Vedic pandits..."

Step 2: Search "Satyanarayan Puja in Delhi"
  - Results: 47 priests, 12 temples, 3 puja packages
  - Filters: city, area, date, price, language, ratings
  - Sort: trust score, distance, price, rating

Step 3: Click priest "Pt. Rajesh Sharma" (4.9★, Gold badge)
  - Detail page:
    - Photo, video intro
    - "Verified ✓ • 1,200 pujas • Replies in 30 min"
    - Languages: Hindi, Sanskrit
    - Deities: Vishnu, Krishna
    - Service radius: 25 km from Delhi
    - Reviews (latest 5 + average)
    - Pricing: ₹2,500 base, ₹1,200 samagri, optional tip
    - Calendar (next 7 days)
  - AEO: "Pt. Rajesh Sharma is a verified Vedic pandit in Delhi with 4.9★ from 1,200 pujas..."

Step 4: Pick date Sat 7 June 2026, 10:00 AM
  - Calendar shows availability
  - "Book this slot" button

Step 5: Booking flow (4 steps)
  - Step 1: Address (saved address or new) + pincode
  - Step 2: Samagri (included? buy? skip?)
  - Step 3: Tip (optional, presets ₹200/₹500/₹1000/custom)
  - Step 4: Review
    - "Service ₹2,500 + Samagri ₹1,200 + Platform fee ₹250 = ₹3,950"
    - Cancellation: "Free until 48h before"
    - "Pay & Confirm"

Step 6: Sign-up / Login
  - If not logged in: phone OTP via UIDP
  - If new: 3-step onboarding (verify → interests → recommendations)

Step 7: Payment
  - Razorpay checkout
  - Cards, UPI, NetBanking, Wallets
  - IRN generated for invoice

Step 8: Confirmation
  - Email + SMS + push + in-app
  - Booking in /user/bookings
  - Calendar event added (with consent)
  - Reminder set (24h, 1h)

Step 9: Pre-puja
  - Day before: checklist (samagri, prasad, time, address)
  - Hour before: "Pt. Rajesh is on the way" (when EN_ROUTE)

Step 10: Post-puja
  - Mark complete
  - Review prompt (day after)
  - Invoice + receipt
  - Optional: re-book, follow priest, save to collection
```

**Time to value: 5 min from landing to confirmation. 5 min from confirmation to start of puja. Total 10 min.**

---

## 69. Pandit Happy Path: Accept a Job (P2: Ramesh)

```
Step 1: Sign up
  - Phone OTP via UIDP
  - 5-step onboarding:
    1. Personal: name, DOB, photo
    2. Location: city, area, service radius
    3. Expertise: deities, traditions, languages
    4. KYC: Aadhaar (via UIDP), PAN, bank (penny-drop)
    5. Availability: weekly template, time off

Step 2: Profile review (Manager approves within 24h)
  - Status: pending → approved
  - KYC level: 0 → 1 (basic), 2 (full with bank)

Step 3: Get job request
  - Push + SMS: "Anita booked Satyanarayan, Sat 7 June 10:00 AM, Delhi NCR"
  - In-app: "Accept | Decline (with reason) | Counter-propose"
  - Default: auto-accept if within calendar

Step 4: Pre-puja
  - Day before: checklist, customer details, address
  - Hour before: "Mark EN_ROUTE" → customer notified

Step 5: At venue
  - Mark "Arrived" → customer notified
  - "Start ceremony" → status IN_PROGRESS
  - Optional: photo upload (samagri setup)

Step 6: Complete
  - "Mark complete" → status COMPLETED
  - Tip received (in-app notification)
  - Payout scheduled (T+1)

Step 7: Payout
  - Daily: pending balance
  - T+1: payout initiated
  - T+2: settled in bank (with UTR)
  - Notification + SMS

Step 8: Post-puja
  - Customer review prompt
  - Response to review (1 response per review)
  - Earnings dashboard updates
```

**Time to first booking: 1 day (post-approval). Time to first payout: 3 days post-first-completion.**

---

## 70. Vendor Happy Path: Receive and Fulfill an Order (P3: Suresh)

```
Step 1: Sign up
  - Phone OTP via UIDP
  - 4-step: business info, GSTIN, address, KYC

Step 2: Add product
  - Photo, name, price, stock
  - Variants (size, weight)
  - Category (samagri, flowers, etc.)

Step 3: Receive order
  - Push + SMS + in-app
  - Order detail: customer, items, address, time

Step 4: Pack & dispatch
  - Update status: processing → dispatched
  - Print label
  - Add tracking (if integrated with courier)

Step 5: Delivered
  - Mark delivered
  - Customer confirms
  - Payout scheduled (T+2)

Step 6: Returns / disputes
  - Customer raises issue
  - Manager mediates
  - Refund issued (full/partial/store-credit)
```

---

## 71. Manager Happy Path: Approve a KYC (P4: Priya)

```
Step 1: Login
  - Manager SSO

Step 2: Dashboard
  - "23 pending approvals" badge
  - Top of queue: KYC documents awaiting review

Step 3: Open a case
  - Side-by-side: photo, document, extracted data, sanity check
  - "Approve | Reject (reason) | Request more info"

Step 4: Approve
  - Audit log entry
  - Notify user
  - KYC level updated
  - Next case auto-loads

Step 5: Analytics
  - "Approved 23/23 today, avg 1m 20s per case"
  - "0 fraud flags"
```

---

## 72. Edge Cases (43)

### Auth
- E1: User signs up with phone that already has an account → link, not create
- E2: User enters wrong OTP 3 times → locked for 1h, email sent
- E3: User passes MFA on new device → "Trust this device?" prompt
- E4: Session expired mid-checkout → "Resume" CTA
- E5: Phone number changes → OTP to old + new, link, force re-auth on other devices
- E6: User has 5+ devices → show list, "Revoke all others"
- E7: OAuth provider returns email already in use → link, merge
- E8: Passkey not supported → fall back to OTP
- E9: User with no email → phone-only, but warn for account recovery

### Booking
- E10: Slot taken between view and book → "Just taken, next available: ..."
- E11: Priest cancels → full refund + 3 alternatives + ₹200 coupon
- E12: Customer reschedules 3 times in a week → soft limit, manager review
- E13: Customer no-show → priest paid 50%, no-show fee ₹500
- E14: Priest no-show → full refund + ₹500 coupon + free re-book
- E15: Dispute on quality → manager-mediated, evidence upload, escrow until resolved
- E16: Booking during festival (Diwali) → surge pricing (transparent), priest gets more
- E17: Priest at distance > service radius → block at book time
- E18: Priest not available for this puja → block at book time
- E19: Customer outside India → block (India only v1)

### Payment
- E20: Card declined → retry with different card, UPI fallback
- E21: UPI timeout (rare) → check status, retry
- E22: Webhook arrives before checkout success → idempotency holds
- E23: IRN generation fails → invoice marked `pending_irn`, retry hourly
- E24: TDS threshold crossed (₹50L) → auto-deduct 0.1%, certificate at year end
- E25: 80G certificate → request via dashboard, 7-day SLA
- E26: Refund > payment amount → block (data error)
- E27: Chargeback → evidence upload workflow, 7-day SLA

### Directory
- E28: Priest with no reviews → "New on Navdhya, book with confidence (KYC verified)"
- E29: Priest deactivated (KYC expired) → show with banner, "view only"
- E30: Temple with no deity tagged → show, suggest tagging
- E31: Search returns 0 results → "Be the first to add" or "broaden search"
- E32: Map clusters 100+ pins → cluster at zoom 8, expand at zoom 12

### i18n
- E33: Hindi user in English mode → detect, offer switch
- E34: Browser in hi-IN, no Hindi translation → fall back to English with "Translation pending" badge
- E35: Number format en-IN vs hi-IN → ₹1,00,000 vs ₹100,000 (use en-IN everywhere for consistency)

### Compliance
- E36: DPDP export request → 30-day SLA, signed URL
- E37: DPDP delete request → soft delete, 30-day grace, then hard
- E38: User under 13 → block (DPDP)
- E39: User updates PAN → re-verify, lock dependent actions

### Operational
- E40: Multiple users claim same Aadhaar → block second, support ticket
- E41: Webhook signature mismatch → reject, alert, rotate
- E42: Outbox lag > 5 min → page on-call
- E43: DB connection pool exhausted → scale RDS, slow down, do not 500

---

## 73. Error and Recovery Flows (20)

### Auth failures
- **E-F1: Wrong OTP**
  - Inline: "Wrong code. 2 attempts left."
  - 3 fails: "Locked for 1h. We'll email you a magic link."
- **E-F2: Account locked**
  - Page: "Your account is locked. [Reason]. Contact support."
- **E-F3: Session expired mid-flow**
  - Modal: "Please sign in to continue. Your progress is saved."
  - On re-auth: resume at last step

### Booking failures
- **E-F4: Slot taken**
  - Inline: "Just taken. Next available: Sat 14 June 5 PM. [Book]"
- **E-F5: Priest cancelled**
  - Email + push: "Pt. X cancelled. We've refunded ₹3,950. Pick another:"
  - + 3 alternatives + ₹200 coupon auto-applied
- **E-F6: Payment failed**
  - Razorpay: show reason (insufficient funds, network)
  - "Try again" with same method, or "Use different method"
  - Booking stays in `DRAFT` for 30 min, then auto-cancel
- **E-F7: Priest no-show**
  - Push: "Priest hasn't arrived. [Mark no-show] [Call support]"
  - 30 min after scheduled time: auto-flag
  - Manager intervenes: full refund + free re-book

### Payment failures
- **E-F8: Gateway 5xx**
  - "Payment is being processed. Check status in 2 min."
  - Webhook reconciliation in 5 min
  - If still pending: support ticket
- **E-F9: IRN failed**
  - User-facing: "Your invoice is being generated. Email in 1h."
  - Background: retry 5x with backoff; alert if > 1h

### Dashboard failures
- **E-F10: Data load failed**
  - "Couldn't load. Retry." (per section)
  - Skeleton replaced with error card
  - "Report this" with Sentry ID
- **E-F11: Save failed**
  - Inline: "Couldn't save. Retry." + form values preserved
  - Toast with retry button
- **E-F12: Permission denied**
  - "You don't have access. Contact your admin."

### Network failures
- **E-F13: Offline (PWA)**
  - Banner: "You're offline. Some features limited."
  - Read-only mode; queued writes on reconnect
- **E-F14: Slow network**
  - Skeleton + spinner; no content shift
  - "Loading..." with progress if known
- **E-F15: 5xx**
  - Full-page error with Sentry ID
  - "Try again" / "Go home" / "Contact support"

### Compliance failures
- **E-F16: DPDP export failed**
  - Email: "We're working on it. Will be ready in X days."
  - Support can manually trigger
- **E-F17: KYC expired**
  - In-app: "Your KYC expired on [date]. Re-verify to continue receiving bookings."
  - Block payouts until re-verified

### Operational failures
- **E-F18: Outbox lag**
  - User-facing: "Some updates pending. Refresh in 1 min."
  - SRE: page, scale workers
- **E-F19: Search down**
  - "Search is temporarily unavailable. Browse by [City] instead."
- **E-F20: Map down**
  - Fall back to list view; show banner

---

## 74. Cross-Role Flows (10)

### Flow 1: User books → Pandit accepts → Vendor supplies samagri
- User books puja (with samagri)
- Pandit accepts
- Samagri is auto-allocated to nearest vendor
- Vendor packs and dispatches
- Tracking visible to user and pandit
- Delivery confirmed → puja can start

### Flow 2: User → Manager (dispute) → Resolution
- User marks dispute with reason
- Manager queue: opens case
- Manager requests evidence from both
- Manager decides: refund full/partial/store-credit
- Both parties notified
- Audit log
- User can appeal once (Tier 2 manager)

### Flow 3: Pandit → KYC failure → Manager review
- Pandit submits Aadhaar/PAN
- UIDP returns failed
- Pandit sees "Re-verify" in dashboard
- Re-submits
- If still fails: Manager reviews manually
- Manager approves or rejects (with reason)
- Pandit notified

### Flow 4: Admin publishes content → Translation queue → Reviewer → Live
- Admin edits a page in `cms_content`
- Status: draft
- If Hindi: auto-translate (DeepL draft) → `cms_content_hi` with `status=draft`
- Reviewer sees in queue
- Approve → status=published
- Live at /hi/<slug>

### Flow 5: Payment → TDS threshold → Certificate
- Multiple payouts to a pandit
- Total reaches ₹50 lakh in FY
- TDS 0.1% auto-deducted on subsequent payouts
- Apr 1 next year: 194-O certificate generated
- PDF emailed to pandit
- Audit log

### Flow 6: New tenant → Onboarding
- SuperAdmin creates tenant
- Tenant admin invited via email
- Admin signs up, picks plan
- Payment for plan
- Tenant gets subdomain (e.g., `navdhya-pune.navdhya.com`)
- Tenant configures: branding, regions, allowed features
- Tenant goes live

### Flow 7: User → DPDP delete → All systems honor
- User requests delete
- 30-day grace period (can cancel)
- Day 30: hard delete in UIDP
- Webhook fires: `uidp.account.deleted`
- Our app: soft-delete profile, anonymize reviews, mark bookings as `deleted_user`, remove PII from support tickets
- Ledger entries kept (tax retention) but anonymized
- Audit log
- Email confirmation to user (with right-to-appeal info)

### Flow 8: Webhook missed → Reconciliation
- Webhook to Razorpay fails (network blip)
- Reconciliation cron: queries Razorpay for `payment_id` status
- Detects mismatch
- Retries the webhook handler (idempotent)
- Updates DB
- Logs

### Flow 9: Out-of-band dispute (phone call)
- User calls support
- Support agent opens ticket in our app (impersonates user with consent)
- Agent makes change
- Audit log: `actor: support_agent, impersonated: user, reason: ...`
- User sees in their activity feed
- User can revoke access

### Flow 10: Multi-tenant data leak prevention
- Test: try `SELECT * FROM dir_priest WHERE tenant_id = 'tenant_a'`
- With RLS: returns 0 (RLS filter)
- Without RLS: would return tenant_a's data
- Test in CI: ensure no `tenant_id` filter missing in any repo
- Audit: nightly tenant-data-access report

---

## 75. Mobile-Specific Scenarios (70%+ of India traffic)

### M1: First load on 3G
- PWA: shell + critical CSS in < 2s
- Skeleton, no image until LCP
- Service worker for repeat visits
- A2HS prompt after 2nd visit

### M2: Aadhaar upload (KYC)
- In-app camera
- Auto-crop, auto-enhance
- Compress to < 500 KB
- Show progress, retry on fail
- Save draft, complete later

### M3: Booking on the go
- Bottom nav
- One-thumb reachable CTAs
- Save for later (offline draft)
- Quick re-book from history

### M4: Chat with priest
- In-app inbox
- Push notifications
- Read receipts
- Photo / voice notes
- Quick replies
- Translation (en↔hi) at the message level (v2)

### M5: Payment failure on weak network
- 3x retry with backoff
- UPI intent (deep link to PSP app)
- Fallback: SMS link
- Reconciliation in 2 min if user paid elsewhere

---

## 76. Mapping to PRs (final integration)

| Concern | PR | Notes |
|---|---|---|
| C-S1 Soft-delete + RLS | PR 10 | RLS + retention |
| C-S5 Drizzle migration tx | PR 10 | Migration safety |
| C-S7 Webhook idempotency TTL | PR 12 | 30d TTL |
| C-S10 PII in logs | PR 11 | Pino filter, lint rule |
| C-P2 Duplicate IAM | PR 3 | Consolidation |
| C-P4 Soft-delete bloat | PR 10, 15 | Cron sweep |
| C-P5 pgvector first | PR 11 | OpenSearch at scale |
| C-P6 Event schema versioning | PR 13 | Schema registry |
| C-P10 Hex layer leak (auth) | PR 1, 2 | Move to `infra/uidp/` |
| C-T3 Aadhaar/PAN | PR 12 | Razorpay handles, no storage |
| C-T4 GST e-invoice | PR 12 | Two GSPs, retry |
| C-T5 194-O TDS | PR 12 | Tax engine |
| C-T7 Hindi quality | PR 14a | DeepL + Claude + reviewer |
| C-T8 Customer success | PR 8, 11, 15 | Onboarding, NPS, status |
| C-T9 GTM | PR 0.5, 14b, 15 | Search Console, llms, GTM |
| C-Pr1 Empty states | PR 8, 10, 11 | All sections |
| C-Pr2 Onboarding | PR 4, 11 | 3-step, personalized |
| C-Pr3 Trust signals | PR 11 | Human-readable |
| C-Pr4 Pricing transparency | PR 11, 12 | Full breakdown |
| C-Pr5 Cancellation | PR 12 | Visible at book |
| C-Pr6 Social proof | PR 11 | Aggregate review |
| C-Pr7 AEO test | PR 0.5, 14b | Eval harness |
| C-Pr8 Mobile-first | PR 4, 11, 12 | PWA, bottom nav |
| C-Pr9 A/B | PR 15 | Experiment framework |
| C-Pr10 Notif strategy | PR 13 | Frequency cap, quiet hours |
| **UIDP integration** | PR 1, 2, 3, 4 | Foundation |
| **Dashboard menus** | PR 7, 8, 9 | All 6 roles |
| **User flows** | PR 4, 8, 11, 12, 13 | Implementation |
| **Edge cases** | All PRs | As relevant |
| **Error/recovery** | All PRs | As relevant |
| **Cross-role** | PR 11, 12, 13, 14a | Multi-module |
| **Mobile** | PR 4, 11, 12 | PWA-first |

---

## 77. Final Note

v11 added:
- **4-perspective audit** (Senior, Principal, CTO, Product Engineer) with 41 consolidated findings
- **UIDP integration architecture** (port, local schema, webhooks, failure modes, security)
- **6 dashboard menu plans** (User, Pandit, Vendor, Manager, Admin, SuperAdmin) with quick actions, mobile, empty/loading/error states
- **10 personas, 4 happy paths, 43 edge cases, 20 error/recovery flows, 10 cross-role flows, 5 mobile scenarios**

**Total plan:** 2,500+ lines. Ready for v12 if you want to keep iterating, or say "next" to start PR 0.5.


# PART XI — CI/CD, DEVOPS & DEVEX

## 78. Local Dev Environment (one command)

**Tooling:** Node 22, pnpm 9, Docker 24+, Postgres 16, Redis 7, OpenSearch 2 (optional).

**`pnpm dev` does:**
1. Check Node/pnpm version
2. Spin up Postgres + Redis via `docker-compose.yml`
3. Run Drizzle migrations
4. Seed dev data (10k priests, 100 cities, 20 super admin users)
5. Start UIDP mock (in-process)
6. Start Next.js dev server on :3000
7. Open Vercel tunnel for phone testing (optional)

**Prerequisites script (`scripts/dev-check.sh`):**
- Validates Node, pnpm, Docker
- Warns if Vercel CLI not installed
- Provides mock external services (Razorpay, GSP, OpenAI)

**Codespaces config:** `.devcontainer/devcontainer.json` with Postgres + Redis sidecars, 8-core, 16GB.

**Time to first commit (new dev):** < 1 day. Onboarding doc at `docs/onboarding/`.

---

## 79. CI/CD Pipeline (per PR)

### Stages (target total: < 10 min)

| Stage | Tool | Time | Failure action |
|---|---|---|---|
| 1. Lint | ESLint, Prettier, custom rules | < 30s | Block merge |
| 2. Typecheck | `tsc --noEmit` | < 60s | Block merge |
| 3. Unit tests | Jest | < 2 min | Block merge |
| 4. Integration tests | Jest + testcontainers | < 5 min | Block merge |
| 5. Contract tests | Pact | < 1 min | Block merge |
| 6. Build | `next build` | < 3 min | Block merge |
| 7. Lighthouse | `lhci autorun` | < 2 min | Warn (allow with override) |
| 8. Security | `npm audit`, `gitleaks`, `trivy` | < 1 min | Block P0/P1 |
| 9. Visual regression | Chromatic (changed components) | < 2 min | Block on diff |
| 10. Bundle analyzer | `next-bundle-analyzer` | < 30s | Warn over budget |
| 11. Migration safety | Drizzle Kit dry-run up/down | < 30s | Block on data loss |
| 12. Preview deploy | Vercel | < 3 min | Always |

**Parallel where possible:** stages 1-11 run in parallel after lint passes.

### Branch protection
- `main`: requires 1 approval, all checks green, no force-push
- `pr/*`: ephemeral preview, can be force-pushed

### PR template
```
## What
[1-line description]

## Why
[Problem / opportunity]

## How
[Approach, files changed at module level]

## Risk
[Low/Med/High + specific concerns]

## Rollback
[Single command or revert PR]

## Screenshot / Metric
[If user-facing]

## Tracking
[Linear / GitHub issue]
```

### Required checks (cannot be bypassed)
- Lint, typecheck, unit, integration, build, security, migration safety

### Bypassable (with justification)
- Lighthouse (e.g., a known regression being fixed)
- Visual regression (e.g., intentional redesign)
- Bundle size (e.g., a heavy new feature, with code-split plan)

---

## 80. Deployment Pipeline (per release)

```
1. Merge to main
2. Auto-deploy to staging (6:00 IST daily)
3. Smoke tests on staging (5 min)
4. Canary 1% in prod (10 min, SLO guard)
5. Canary 10% in prod (10 min, SLO guard)
6. Canary 50% in prod (10 min, SLO guard)
7. Full rollout (100%)
8. Post-deploy checks (5 min)
9. Slack notification
10. Auto-rollback if SLO burn > 2x in 30 min
```

**Tools:** Vercel (web), GitHub Actions (orchestration), Cloudflare (WAF), PagerDuty (SLO alerts).

**Hotfix path:** out-of-band deploy, requires 2 approvals (eng + SRE), postmortem in 24h.

---

## 81. Release Train

| Cadence | Day | Content |
|---|---|---|
| **Trunk** | Continuous | Merge to main |
| **Daily** | 6:00 IST | Auto-deploy staging |
| **Twice weekly** | Mon, Thu 10:00 IST | Prod canary |
| **Hotfix** | On-demand | Out-of-band |
| **Feature freeze** | Last 5 days of quarter | Reliability only |

**Versioning:** CalVer (`2026.06.04-1`). Git tags. Auto-generated changelog from conventional commits.

**Release notes:** auto-generated, edited by tech writer, published to `/changelog`, RSS, email digest (opt-in).

---

## 82. Code Conventions

### Naming
- **Files:** `kebab-case.ts` (e.g., `priest-search.usecase.ts`)
- **Classes:** `PascalCase`
- **Functions/vars:** `camelCase`
- **Constants:** `SCREAMING_SNAKE_CASE`
- **Types:** `PascalCase` (no `I` prefix)
- **DB tables:** `snake_case`, plural (e.g., `priest_availabilities`)
- **DB columns:** `snake_case`
- **Routes:** `kebab-case` URLs, dynamic `[param]`
- **API endpoints:** `/api/v1/<resource>` (REST), `/api/mcp/<tool>` (MCP)
- **Event names:** `aggregate.event.vN` (e.g., `book.booking.created.v1`)
- **Feature flags:** `module.feature.variant` (e.g., `directory.priest_map.enabled`)
- **Permissions:** `resource:action` (e.g., `priest:read`)

### File structure
- One usecase per file: `application/<module>/usecases/<name>.usecase.ts`
- One repo per file: `adapters/postgres/repositories/<name>.repo.ts`
- One schema per file: `adapters/postgres/schema/<table>.ts`
- One component per file: `ui/components/<name>.tsx`
- One test per file: `<name>.test.ts(x)` next to source

### Imports
- Absolute paths only (`@/...`)
- ESLint rule: no relative imports beyond `./<same-folder>`
- Sorted by: external → internal → types

### Comments
- **No** comments explaining "what" — code should be self-documenting
- Comments explain "why" only (rationale, gotcha, link to ADR)
- JSDoc on public exports only

### Error handling
- Throw typed errors (`AuthError`, `ValidationError`, `NotFoundError`)
- Caught at the boundary (usecase → HTTP / server action)
- Logged with `pino` and `trace_id`
- Never swallowed

---

## 83. Git Workflow

- **Trunk-based** with short-lived branches (< 3 days)
- Branch names: `pr/<num>-<short-name>` (PR-driven)
- Commits: conventional (`feat:`, `fix:`, `chore:`, `docs:`, `test:`, `refactor:`)
- Squash merge to main
- Linear history (no merge commits)
- Force-push allowed on `pr/*` only

---

## 84. Dependency Management

- **Renovate** bot: weekly PRs, grouped by ecosystem
- **Dependabot** as backup
- **Lockfile** committed (pnpm)
- **Audit** on every CI run
- **License** whitelist (MIT, Apache-2.0, BSD-3-Clause, ISC, MPL-2.0)
- **SBOM** generated per release, archived to S3
- **Vulnerabilities:** P0 fixed in 24h, P1 in 7d, P2 in 30d

---

# PART XII — QUALITY & TESTING

## 85. Test Pyramid (locked)

| Level | Tool | Target coverage | Speed |
|---|---|---|---|
| **Unit** | Jest + RTL | ≥ 90% on `domain`, `application`, `ports`; ≥ 85% on `adapters` | < 2 min |
| **Integration** | Jest + testcontainers (Postgres, Redis) | ≥ 85% on `application/usecases`, `adapters/repositories` | < 5 min |
| **Contract** | Pact (HTTP), Drizzle assertions (DB) | All adapters, all gateways | < 1 min |
| **E2E** | Playwright | 50+ critical journeys | < 15 min |
| **Visual** | Chromatic | All design system + key pages | < 2 min |
| **Mutation** | Stryker | Critical modules (ledger, RBAC, billing) weekly | < 30 min |
| **Property** | fast-check | Parsers, formatters | < 1 min |
| **Fuzz** | jsfuzz, radamsa | JSON parsers, webhook payloads | < 5 min |
| **Load** | k6, Artillery | Top 20 endpoints weekly | < 30 min |
| **A11y** | axe-core in E2E | All pages | part of E2E |
| **Security** | OWASP ZAP, Snyk, Trivy | All deps + all routes | < 10 min |
| **Performance** | Lighthouse CI | All routes per PR | < 2 min |
| **Chaos** | Litmus (staging) | Monthly game day | manual |

---

## 86. Test Data Strategy

### Factories (`/test/factories/`)
- One factory per aggregate
- Build, create, createMany
- Optional traits (`withKyc`, `withReviews`, `withBookings`)
- No PII in fixtures

### Anonymization
- All test data has synthetic PII (e.g., `+919876543210`, `test@example.com`)
- Production data never enters test envs
- Synthetic prod-like data generated for load tests (5M rows)

### Database
- `testcontainers` for ephemeral Postgres per test file
- Migrations run in setup
- Truncate between tests
- Snapshot per scenario

### Seeding
- Dev: 10k priests, 100 cities, 20 admins
- Staging: 100k priests, 10k cities (cloned from prod, anonymized)
- Load test: 1M priests, 100k cities

---

## 87. AEO Test Harness (concrete)

### Eval set (`/aeo/eval/v1/`)
- 500 test questions
- Categories: puja, priest, temple, deity, tradition, area, city, comparison, troubleshooting
- Each has: question, expected entity types, expected entities, expected speakable length, expected keyfacts count

### Metrics
- **Entity recall:** % of expected entities mentioned
- **Speakable length:** must be ≤ 280 chars
- **KeyFacts count:** 3-5
- **FAQ count:** 3-5
- **Tone match:** positive/neutral, formal
- **Schema validity:** Speakable JSON-LD parses
- **Click-through:** post-launch, from GSC

### CI gate
- Entity recall ≥ 0.85
- Length compliance = 100%
- Schema validity = 100%
- Tone match ≥ 0.9

### Run
- `pnpm test:aeo` on every PR
- Full eval nightly
- Re-eval after AEO prompt changes

---

## 88. Translation Pipeline (concrete)

### Stages
1. **Source extraction** (`scripts/translate/extract.ts`): reads `content/pages/<slug>.json`, pulls `title`, `body`, `faqs`, `meta_description`
2. **Glossary application**: enforce translations for sacred terms (Ganesh, puja, mantra)
3. **DeepL draft**: bulk API, 80% of pages, ~₹1,500
4. **Claude re-translation**: top 200 pages by traffic, ~₹400
5. **Reviewer queue**: in `/admin/translations`, with diff UI
6. **Approve / Reject / Edit**: reviewer action
7. **Publish**: status flips, page served at `/hi/<slug>`

### Glossary (`/aeo/glossary/hi.json`)
- 500+ sacred terms with canonical Hindi
- Transliteration rules
- "Never translate" list (Ganesh, Krishna, etc.)

### Quality
- BLEU score ≥ 0.6 (target)
- Reviewer approval rate ≥ 0.9
- Time per page: < 2 min

### Cost
- Total: ₹6,000 for 1,265 pages (DeepL + Claude + reviewer)
- Re-run cost: ₹500 (only changed pages)

---

## 89. Search Quality Eval

### Metrics
- **NDCG@10** ≥ 0.85
- **MRR** ≥ 0.7
- **CTR** ≥ 0.3 (post-launch)
- **Zero-result rate** < 5%
- **Latency p95** < 300ms

### Eval set
- 1,000 queries
- Mix: head (frequent), torso (medium), tail (long-tail)
- Hindi + English
- Geo-aware (city/area/zip)
- Transliteration cases (sharma ↔ शर्मा)

### Pipeline
- `pnpm test:search` weekly
- A/B test changes against eval set
- Track quality drift over time

---

## 90. Funnel Analytics (per persona)

| Funnel | Stages | Tool | Cadence |
|---|---|---|---|
| **Acquisition** | visit → search → click → signup | PostHog + GSC | real-time |
| **Booking** | search → detail → book → pay → complete | PostHog | real-time |
| **Onboarding (User)** | signup → phone verify → interests → first action | PostHog | real-time |
| **Onboarding (Pandit)** | signup → KYC → profile → first job | PostHog | real-time |
| **Supply (Pandit)** | job request → accept → arrive → complete | PostHog | real-time |
| **Payment** | checkout → method → 3DS → success | PostHog + Razorpay | real-time |
| **Refund** | cancel request → review → refund issued | PostHog | real-time |
| **AEO click-through** | search → AEO answer → click | GSC + PostHog | daily |
| **Translation funnel** | English → suggest → translate → review → publish | internal | weekly |

---

# PART XIII — SECURITY & COMPLIANCE (detail)

## 91. Penetration Testing Plan

### Frequency
- **Internal:** quarterly
- **External (vendor):** annual + after major launches

### Scope
- Web app, mobile web, API, MCP, admin
- Auth, RBAC, payment, refund, KYC
- Multi-tenant isolation
- RLS bypass attempts

### Vendors
- Year 1: AppSecure India (₹8L/year)
- Year 2: rotate to BugDazz or Astra

### Deliverables
- Pentest report (P0/P1/P2/P3 findings)
- 30-day remediation SLA for P0/P1
- Re-test after fix

---

## 92. Bug Bounty Program

### Platform
- **HackerOne** or **Bugcrowd** (Year 2, after pentest maturity)

### Scope
- `*.navdhya.com`
- `app.navdhya.com`
- `api.navdhya.com`
- MCP endpoints

### Rewards
- **P0 (auth bypass, RCE, payment bypass):** ₹5L – ₹10L
- **P1 (XSS, IDOR, privilege escalation):** ₹1L – ₹5L
- **P2 (info disclosure, CSRF on low-value action):** ₹25k – ₹1L
- **P3 (rate limit bypass, clickjacking):** ₹5k – ₹25k

### Out of scope
- Denial of service
- Social engineering
- Physical attacks
- Third-party apps

### Triage SLA
- First response: 24h
- Triage decision: 72h
- P0 fix: 7d, bounty: 30d
- P1 fix: 30d, bounty: 60d

---

## 93. Security Training

- **All engineers:** annual secure coding training (OWASP Top 10)
- **Payment engineers:** quarterly PCI training
- **Phishing sim:** monthly
- **Tabletop:** quarterly (incident response)

---

## 94. Data Breach Response (72-hour timeline)

### T+0: Detect
- SIEM alert, customer report, third-party tip
- SRE on-call paged
- Incident declared, severity set (P0/P1)

### T+15min: Contain
- Disable compromised accounts
- Rotate keys/secrets
- Block IPs if needed
- Snapshot logs/audit

### T+1h: Investigate
- Form IR team: SRE lead, security lead, legal, comms
- Scope: who, what, when, how
- Containment: kill switch, rollback, disable feature

### T+4h: Notify internal
- Exec team, legal, DPO
- Prepare customer comms
- Engage external counsel

### T+24h: Notify CERT-In
- Required for breaches affecting Indian users
- Format: prescribed by CERT-In directions 2022
- Include: scope, categories of data, number of users, mitigation

### T+72h: Notify users
- Email + in-app + status page
- For DPDP: explicit consent and right to action
- Provide: what happened, what data, what we're doing, what they should do

### T+7d: Postmortem
- Blameless, root cause
- Action items with owners
- Public postmortem (if material)

### Runbook: `docs/runbooks/breach-response.md`

---

## 95. Compliance Audit Prep

### DPDP audit (annual)
- 30-day prep: gather all consent records, audit logs, retention evidence
- External auditor: data privacy consultant
- Evidence package: consent receipts, DPIA, retention, breach history

### PCI DSS SAQ-A
- Annual self-assessment
- Attestation of compliance (AOC)
- Quarterly ASV scan (Approved Scanning Vendor)

### GST audit
- Monthly GSTR-1 (outgoing), GSTR-3B (summary)
- Annual audit by CA
- e-Invoice archive (6 years)

### RBI audit
- Tokenization certificate (from PSP)
- Mandate logs
- Customer grievance report

---

# PART XIV — DATA & AI PIPELINES

## 96. Image Processing Pipeline

### Stages
1. **Upload** to S3 (`s3://navdhya-media/original/`)
2. **Trigger** via S3 event → SQS → worker
3. **Process** (Sharp):
   - Variants: thumbnail (200x200), small (400x400), medium (800x800), large (1600x1600), hero (2400x1200)
   - Formats: AVIF, WebP, JPEG fallback
   - Strip EXIF (privacy)
4. **Blurhash** + LQIP (low-quality image placeholder)
5. **Store** at `s3://navdhya-media/processed/<size>/<hash>.avif`
6. **CDN** via CloudFront, immutable URLs
7. **DB** stores: original key, processed keys, blurhash, dimensions, mime

### Sizing
- Max 5MB original
- Min 800x800 for priest photos
- Min 1920x1080 for hero images
- Auto-reject if too small, warn if too large

### Cost
- $0.005/GB processed
- 10M images/year × 2MB × 5 variants = $500/year

---

## 97. Email Template System

### Tooling
- **react-email** for templates (TypeScript, components, hot reload)
- **MJML** fallback for marketing emails
- Templates in `infra/comm/templates/<name>.tsx`

### Variables
- Typed props per template
- Validation with Zod
- Locale variants: `<name>.en.tsx`, `<name>.hi.tsx`

### Delivery
- Postmark (transactional), SES (backup)
- Open/click tracking
- Bounce/complaint handling
- Unsubscribe link (CAN-SPAM, IT Act)

### Preview
- Dev: `pnpm email:dev` (live preview server)
- Staging: emails captured to Mailtrap
- Prod: real delivery, with Sentry breadcrumb

---

## 98. Push Notification System

### Providers
- **FCM** (Android, web)
- **APNs** (iOS, web via Push API)

### Architecture
- Token storage in `comm.push_token` (per user per device)
- Worker: `q:notification.push`
- Payload: title, body, data (deep link), image, badge
- Collapse key (latest wins for same type)

### Scheduling
- Immediate (booking confirmations)
- Scheduled (reminders via cron)
- Batched (digest: morning, evening)

### Throttling
- Max 3 push/user/day (configurable)
- Quiet hours per user preference
- Channel fallback: push → email → SMS

---

## 99. Webhook Delivery Infrastructure (outbound)

### Architecture
- Outbox event → `q:webhook.dispatch` worker
- Per subscriber: `webhook_subscriber` config (URL, secret, events, status)
- HTTP POST with HMAC-SHA256 signature
- Retry: 3 attempts, exponential backoff (1m, 5m, 30m)
- DLQ: `q:webhook.dlq`
- Operator UI: list, retry, disable, rotate secret

### Idempotency
- `X-Webhook-Id` header (unique per event)
- `X-Webhook-Timestamp` (5-min window to prevent replay)
- Subscriber validates signature, dedupes on id

### Monitoring
- Delivery success rate per subscriber
- Latency p95
- DLQ depth
- Per-subscriber dashboard

---

## 100. AEO Embedding Pipeline

### Stages
1. **Trigger:** outbox event `cms.content.published.v1`
2. **Worker:** `q:search.embed`
3. **Chunking:** split body into 512-token chunks with 50-token overlap
4. **Embedding:** OpenAI `text-embedding-3-small` (1536-dim, $0.02/M tokens)
5. **Storage:** pgvector table `ai.embedding`
6. **Index:** HNSW (m=16, ef_construction=64)
7. **Query:** hybrid BM25 + vector, weighted (0.4 BM25, 0.6 vector)

### Re-embed
- When source content changes
- Quarterly batch for all content
- Cost: $0.50 per 1,265 pages

---

## 101. Data Warehouse

### Tech
- **Snowflake** or **BigQuery** (Year 1: BigQuery for cost)
- **dbt** for transformations
- **Fivetran/Airbyte** for ingestion

### Sources
- Postgres (production) → BigQuery via CDC (Debezium)
- S3 (logs) → BigQuery via Firehose
- Razorpay (webhooks) → BigQuery
- GSC, GA4 → BigQuery
- PostHog → BigQuery

### Models
- `mart.bookings` (denormalized)
- `mart.priest_performance` (revenue, ratings, retention)
- `mart.unit_economics` (CAC, LTV, payback)
- `mart.fraud_signals` (velocity, chargeback rate)
- `mart.search_quality` (NDCG, CTR, zero-result)
- `mart.aoe_performance` (click-through, citations)

### Consumers
- Looker (BI dashboards)
- FinOps dashboard
- ML features
- Regulatory reports

---

# PART XV — OPERATIONS & INCIDENT RESPONSE

## 102. On-Call Rotation

### Roles
- **Primary SRE** (1 person, 7 days, 24x7)
- **Secondary SRE** (1 person, backup)
- **Tertiary** (eng-A or eng-C if SRE pool is small)

### Schedule
- Week-long shifts, handover Mon 10:00 IST
- PagerDuty schedule
- Compensatory time off after shift

### Tools
- PagerDuty (alerts)
- Slack `#oncall` (channel)
- Sentry (errors)
- Grafana (metrics)
- AWS Console (infra)
- Runbooks (`docs/runbooks/`)

### Response SLAs
- **P0 (full outage, security, data loss):** 5 min ack, 30 min mitigation
- **P1 (degraded, payment, KYC):** 15 min ack, 1h mitigation
- **P2 (slow, single-feature):** 1h ack, 4h mitigation
- **P3 (minor, cosmetic):** next business day

---

## 103. Incident Response Plan

### Severity matrix
| Sev | Impact | Example | Response |
|---|---|---|---|
| **P0** | Full outage, data loss, security | DB down, breach | All-hands, hourly update |
| **P1** | Major feature down, payment | Payment failing | On-call + eng lead |
| **P2** | Minor feature down, slow | Search slow | On-call |
| **P3** | Cosmetic, single user | Typo on a page | Next business day |

### Process
1. **Detect** (alert, customer report, monitoring)
2. **Page** (PagerDuty, severity set)
3. **Triage** (15 min, decide severity)
4. **Mitigate** (stop the bleeding)
5. **Communicate** (status page, customer email, internal Slack)
6. **Resolve** (root cause fix)
7. **Postmortem** (within 5 business days, blameless)
8. **Action items** (tracked, 30-day SLA)

### Communication templates
- Internal: `#inc-<id>` channel, hourly updates
- Status page: `<severity> | <one-line> | <ETA or investigating>`
- Customer email: warm, accountable, specific, actionable

---

## 104. Postmortem Template (blameless)

```markdown
# Incident <id>: <title>

**Severity:** P0 / P1 / P2
**Started:** <ts>
**Detected:** <ts>
**Mitigated:** <ts>
**Resolved:** <ts>
**Duration:** <duration>
**Affected:** <users, %, services, regions>

## Summary
[2-3 sentences: what happened, what we did]

## Timeline (IST)
- T+0  ...
- T+5min ...
- T+1h ...
- T+4h ...

## Impact
- Users: <count> (<% of MAU>)
- Bookings lost: <count>
- Revenue lost: ₹<amount>
- Refunds issued: ₹<amount>
- SLO burn: <% of error budget>

## Root Cause
[5 Whys or fishbone, technical, no blame]

## What Went Well
- [Things that worked]

## What Went Wrong
- [Gaps, blind spots]

## Action Items
| Action | Owner | Due | Status |
|---|---|---|---|
| ... | ... | ... | ... |

## Lessons
[2-3 takeaways, shared org-wide]
```

**Storage:** `docs/postmortems/<yyyy>/<id>-<slug>.md`
**Sharing:** published internally, major ones public

---

## 105. Disaster Recovery (detailed)

### Backup strategy
- **Postgres:** PITR enabled, WAL archived to S3 every 5 min
- **Full snapshot:** daily at 02:00 IST, 7 daily, 4 weekly, 12 monthly, 7 yearly
- **Cross-region:** S3 replication to ap-southeast-1
- **RPO:** 5 minutes (PITR)
- **RTO:** 30 minutes (warm standby)

### DR test
- **Quarterly:** failover to DR region in staging
- **Annually:** full DR drill in prod (scheduled maintenance window, user comm)
- **Verified by:** automated script that measures RTO/RPO

### Runbook (`docs/runbooks/dr-failover.md`)
1. Declare incident
2. Update DNS (Route53 health check)
3. Promote DR database (RDS read replica → primary)
4. Verify writes
5. Update secrets (region-specific)
6. Communicate

---

# PART XVI — PEOPLE & PROCESS

## 106. Engineering Ladder (career growth)

| Level | Title | Scope | Years (avg) |
|---|---|---|---|
| L3 | Software Engineer | Single module, mentored | 0-2 |
| L4 | Senior Engineer | Multiple modules, leads small features | 2-5 |
| L5 | Staff Engineer | Cross-module, leads major features, mentors L3/L4 | 5-8 |
| L6 | Principal Engineer | Architecture, cross-team, defines patterns | 8-12 |
| L7 | Distinguished Engineer | Industry-recognized, sets technical vision | 12+ |

**Expectations per level:** technical, leadership, communication, impact.

**Review cadence:** bi-annual (Apr, Oct).

**Comp bands:** market-anchored, transparent, percentile-based.

---

## 107. Onboarding for New Engineers

### Day 1
- Laptop setup, accounts, codespaces
- Tour of repo, run `pnpm dev`
- Read `docs/onboarding/day-1.md`

### Week 1
- Read PRDs (1,265 pages, modules, ADRs)
- Pair on a small PR
- Read 5 most-recent postmortems
- Meet team

### Month 1
- Own a small feature (single module, with PR review)
- Ship 2-3 PRs
- Read all runbooks

### Month 3
- Own a feature end-to-end (multiple modules)
- Ship 5-10 PRs
- Lead a small incident or postmortem

### Mentor
- Every new engineer paired with a senior for 3 months
- Weekly 1:1 for first month, bi-weekly thereafter

---

## 108. Capacity Forecasting (12-month)

| Quarter | Booking target | Engineers | Infra cost | GMV (INR) | Revenue |
|---|---|---|---|---|---|
| Q1 | 1k/mo | 3 | $5k/mo | ₹2.5Cr | ₹50L |
| Q2 | 10k/mo | 4 | $15k/mo | ₹25Cr | ₹5Cr |
| Q3 | 50k/mo | 5 | $40k/mo | ₹125Cr | ₹25Cr |
| Q4 | 100k/mo | 6 | $80k/mo | ₹250Cr | ₹50Cr |

**Hiring plan:**
- Q1: 3 eng (current)
- Q2: +1 eng-B, +1 designer, +1 content
- Q3: +1 eng-A, +1 SRE, +1 support
- Q4: +1 eng-C, +1 data eng, +1 PM

---

## 109. Customer Support

### Tools
- **Zendesk** or **Freshdesk** (Freshdesk for India pricing)
- Slack integration
- Help center at `/help` (built on Sanity or custom CMS)

### Tiers
- **L1:** general inquiries, password reset, booking changes
- **L2:** disputes, refunds, KYC issues
- **L3:** technical bugs, account issues, escalated by L2

### SLAs
- First response: 2 hours
- Resolution: 24h (L1), 72h (L2), 7d (L3)
- Customer satisfaction: ≥ 4.5/5 (CSAT)

### Channels
- In-app chat (post-launch)
- Email
- Phone (Mon-Sat 9-9 IST, post-launch)
- WhatsApp (post-launch)

---

# PART XVII — DOCUMENTATION & KNOWLEDGE

## 110. Documentation Site

### Structure (`docs.navdhya.com`)
- **Engineer docs**
  - Architecture (C4, ADRs, data flow)
  - Modules (per-module overview, ports, repos, usecases)
  - Runbooks
  - Postmortems
  - Onboarding
- **Partner docs**
  - API reference (OpenAPI)
  - MCP tools
  - Webhooks
  - SDKs (TS, Go, Python)
  - Sample apps
  - Sandbox
- **User docs**
  - Help center
  - FAQ
  - How-to videos (Hindi, English)

### Tools
- **Mintlify** or **Docusaurus** for static site
- **OpenAPI** generated from Zod
- **MCP** generates from port definitions
- **README** per repo
- **ADRs** in `docs/adr/`, immutable

---

## 111. SDK Design (3 languages)

### TypeScript (primary)
- NPM package: `@navdhya/sdk`
- Typed client for REST and MCP
- Auto-generated from OpenAPI
- Sub-packages: `@navdhya/sdk-react`, `@navdhya/sdk-node`

### Go
- `github.com/navdhya/navdhya-go`
- For partner backend integrations

### Python
- `pip install navdhya`
- For data science, scripts

### Coverage
- All REST endpoints
- All MCP tools
- Webhook verification helpers
- OAuth flow
- Error handling

---

## 112. Sandbox Environment

### Purpose
- Partner testing
- Demos
- Documentation examples

### URL
- `sandbox.navdhya.com`
- Separate database, separate UIDP
- Pre-seeded with test data (5k priests, 1k users)
- Reset daily
- Test API keys (rate-limited)

### Features
- Mock Razorpay (test mode)
- Mock GSP
- Mock email/SMS (capture to inbox)
- Mock UIDP (mock MFA, OTP)

---

## 113. Knowledge Sharing

- **Eng all-hands:** weekly Mon 10:00 IST
- **Demo Friday:** bi-weekly, show what's shipping
- **Tech talks:** monthly, 1 engineer presents
- **Book club:** monthly, 1 chapter
- **Conferences:** budget ₹2L/engineer/year
- **Open source:** contribution time (10%)

---

## v12 Notes

- v12 added 7 new parts (XI–XVII) with 36 numbered sections (78–113).
- Total plan: 113 sections across 17 parts.
- New parts cover: CI/CD + DevOps + DevEx (78–84), Quality + Testing (85–90), Security + Compliance detail (91–95), Data + AI pipelines (96–101), Operations + Incident response (102–105), People + Process (106–109), Documentation + Knowledge (110–113).
- Plan is now MNC-grade and ready for v1.0 build.
