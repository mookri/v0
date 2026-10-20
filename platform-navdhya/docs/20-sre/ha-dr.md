# High Availability & Disaster Recovery

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 13 §8; doc 00 §6.1 (ARCH-015)

v1 is a single-region deployment with stateless app nodes, MySQL primary + replica, Redis, and object storage. Availability and durability targets (99.9% API availability, RPO ≤ 5 min, RTO ≤ 1 h) are registered as ARCH-015 in the [architecture master](../02-architecture/architecture-master.md); operational alerting lives in [SRE operations](./sre-operations.md); failover and restore procedures live in the [incident response runbook](../22-runbooks/incident-response.md).

---

## High availability & disaster recovery

### 8.1 Architecture (v1, single region)

- **Stateless app tier**: n ≥ 2 web nodes behind LB; zero-downtime deploys
  (rolling, health-gated via `/ready`).
- **MySQL**: primary + 1 async replica; replica serves the analytics
  connection ([intelligence §1.2](../16-analytics/analytics.md)); semi-sync replication recommended for the
  central DB.
- **Redis**: cache/queue/session in prod; persistence (AOF everysec) for
  queue durability; Redis loss degrades throttling/cache but never data
  truth (queue falls back to database driver procedure in runbook).
- **Object storage**: versioned buckets, cross-region replication operator-
  configured for DR.
- **Backups**: MySQL binlog + nightly snapshots (central + tenant shared +
  every dedicated DB); backup encryption; restore drills quarterly.

### 8.2 RPO/RTO targets (per [architecture master §6.1](../02-architecture/architecture-master.md))

| Scenario | RPO | RTO |
|----------|-----|-----|
| App node failure | 0 | seconds (LB removes node) |
| MySQL primary failure | ≤ 5 min (binlog PITR) | ≤ 1 h (failover + verify) |
| Redis failure | n/a (cache) | minutes (restart / fallback) |
| Full region loss | ≤ 15 min (cross-region backups) | ≤ 4 h (v1: manual rebuild runbook) |

### 8.3 Failure modes & graceful degradation

| Failure | Degradation |
|---------|-------------|
| Outbox relay down | events queue in outbox (no loss); lag alert; relay restart resumes |
| Webhook target down | retry schedule ([API platform §7.3](../15-integrations/integration-framework.md)); platform unaffected |
| Search driver down | `q` filters return 503 `search_unavailable`; structured filters still work |
| Notification provider down | queued; delivery log shows `failed`; retry |
| Upstream IdP down | federated users blocked (clear error); password users unaffected unless domain enforced |
| LLM provider down | copilot/agents disabled gracefully (`copilot.provider.error_logged`); business APIs unaffected |

### 8.4 Multi-region path (post-v1)

Documented, not built: DNS-based failover → warm standby region with
replicated MySQL + object storage; tenant promotion (dedicated DBs, [tenancy & data](../06-tenancy/tenancy-and-data.md)
§7) is the migration unit for moving tenants between regions; issuer URL
and JWKS per region require client re-attestation — hence v1 stays
single-region.

---
---

## Drills & evidence

- **Quarterly restore drill**: restore an encrypted backup of a fixture tenant, run the tenant smoke suite against it, and record measured RTO in a signed report. The report is GATE-4 evidence in the [evidence register](../23-evidence/gates-and-milestones.md).
- **Failover rehearsal**: the database-failover runbook (§9.2 item 4 of the incident response runbook) is rehearsed in staging per release candidate; audit-chain continuity is asserted on the new primary.
- **Blast-radius drills**: degradation rows above are exercised on the SRE schedule (Delta matrix row → chaos drill mapping, [delta isolation](../03-delta/delta-isolation.md)).