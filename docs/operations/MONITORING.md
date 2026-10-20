# Monitoring

**Status:** Current for health checks and log locations; alert routing is **Target**
**Scope:** Liveness/readiness, logs, alert classes, and the current observability gaps.

The backend's normative SRE targets and alert classes live in [`platform-navdhya/docs/20-sre/sre-operations.md`](../../platform-navdhya/docs/20-sre/sre-operations.md). This page is the cross-platform operator view.

---

## 1. Health checks

| Surface | Endpoint | Meaning |
| --- | --- | --- |
| Web BFF | `GET /api/healthz` | Process is alive; no dependency checks |
| Web BFF | `GET /api/readyz` | Ready for traffic; probes backend `/up` with a 2 s budget when wired |
| Customer | `GET /api/health` | Returns `{ data: { status: 'ok', product: 'customer' }, requestId }` |
| Customer | `GET /api/version` | Returns `{ data: { version, commitSha, buildAt, environment }, requestId }` |
| Partner | `GET /api/health` | Returns `{ data: { status: 'ok', product: 'partner' }, requestId }` |
| Partner | `GET /api/version` | Version, commit, build time, environment |
| Backend | `GET /up` | Laravel liveness (process/bootstrap) |
| Backend | `GET /ready` | Readiness: central DB, tenant DB, Redis, queue and outbox per the backend contract |
| Backend | `GET /.well-known/jwks.json` | Signing keys are present and readable |
| Backend | `GET /.well-known/oauth-authorization-server` | OAuth discovery is available |

Use `curl -i` and check both the status and the body. A 200 with `status: unavailable` is a failure. Do not use a marketing page as a health check.

```sh
curl -fsS https://www.navdhya.com/api/healthz
curl -fsS https://www.navdhya.com/api/readyz
curl -fsS https://app.navdhya.com/api/health
curl -fsS https://app.navdhya.com/api/version
curl -fsS https://partner.navdhya.com/api/health
curl -fsS https://api.navdhya.com/up
curl -fsS https://api.navdhya.com/ready
```

---

## 2. Logs

| Source | Where | Notes |
| --- | --- | --- |
| Vercel builds/runtime | Vercel dashboard → Deployment → Logs | Requires project access |
| GitHub Actions | Actions run → job → step | Build, test, security and release evidence |
| Backend application | `platform-navdhya/storage/logs/` | Laravel log channel; structured fields per backend contract |
| PHP-FPM/web server | Host logs | 5xx, timeouts, TLS and upstream errors |
| Queue worker | Supervisor/systemd logs | Job failures, retries, memory restarts |
| Scheduler | Cron/systemd journal | **Gap:** the documented cron entry sends output to `/dev/null`; change it to a log file or journal |
| Audit trail | Backend audit store | Actor, action, target, reason and timestamp for sensitive actions |

Backend log fields expected by the SRE contract include `request_id`, `tenant_id`, `user_id` and `client_id`. Use the request ID from an error envelope to correlate the frontend and backend lines.

### Redaction

Never log or paste:

- Session cookies, OTP codes, access/refresh tokens or DPoP private keys.
- Razorpay secrets, MSG91 auth keys or webhook signatures.
- Full customer records, payment instrument data or partner documents.
- Database URLs containing passwords.

Redact to a fingerprint (for example, the first six characters of a hash) when discussing a secret in an incident.

---

## 3. Alert classes

The backend defines four classes. Use them consistently across frontend and backend incidents.

| Class | Examples | Response |
| --- | --- | --- |
| **P1 security** | Audit chain broken, refresh-token reuse, tenant isolation failure in production | Page immediately; open an incident; preserve evidence |
| **P1 availability** | `/ready` failing on more than 50% of nodes; database unreachable | Page; failover/rollback |
| **P2** | Outbox lag over 60 s, queue-depth spike, dead letters, webhook breaker cluster-wide | Business-hours operations; 4-hour ack |
| **P3** | Flag churn, long reindex, quota warnings, certificate under 14 days, medium advisory | Dashboard; weekly review |

Full severity ladder: [`platform-navdhya/docs/22-runbooks/incident-response.md`](../../platform-navdhya/docs/22-runbooks/incident-response.md).

---

## 4. Targets

The backend SRE document defines v1 targets. They are **targets**, not proof of current measurement:

| Target | Value |
| --- | --- |
| API availability | 99.9% monthly |
| RPO | ≤ 5 minutes (binlog PITR) |
| RTO | ≤ 1 hour |
| Token issuance p95 | 300 ms |
| Simple read p95 | 120 ms |
| List/search p95 | 250 ms |
| Write p95 | 300 ms |
| Complex operation p95 | 1.5 s |
| Pre-aggregated report p95 | 2 s |

Frontend SLOs are **not defined**. Add them only after a measurement baseline exists (Vercel analytics or an external probe). Do not invent a number and call it an SLO.

---

## 5. Dashboards and checks

Current evidence sources:

- **CI:** `ci-gate`, `security-gate`, nightly workflow, release workflow.
- **Vercel:** deployment status and runtime logs (when accessible).
- **Backend:** `/up`, `/ready`, JWKS, OAuth discovery, queue monitor.
- **Database:** MySQL health, replication/PITR status, backup age.
- **Scheduler:** `php artisan schedule:list` plus the cron journal.
- **Queue:** `php artisan queue:monitor jobs:pending` and worker logs.

There is no APM, metrics backend or paging provider recorded in this repository. Treat the table above as the minimum manual dashboard until one is chosen. Record the actual provider, URLs and rota in this page and in the documentation plan.

### Suggested first alerts (Target)

These are proposed, not configured:

1. `/ready` non-200 for two consecutive probes on any production node.
2. 5xx rate above 1% for five minutes on the API or a frontend.
3. Queue pending jobs above a baseline for ten minutes.
4. Outbox heartbeat older than 120 seconds.
5. Scheduler last-run older than five minutes.
6. TLS certificate expiring within 14 days.
7. Backup age above the RPO.
8. Any P1 security signal from the backend audit/identity checks.

---

## 6. Incident flow

1. **Detect** via a health check, alert, customer report or CI failure.
2. **Declare** severity using the table above.
3. **Mitigate** with rollback, flag-off, failover or provider failover. Prefer the smallest reversible action.
4. **Communicate** status, impact, start time and next update time.
5. **Preserve evidence** (request IDs, logs, audit excerpts) before cleaning up.
6. **Resolve** and verify with the post-deploy checks.
7. **Postmortem** within five business days for P1; add a runbook change before closing.

See [`ROLLBACK.md`](ROLLBACK.md) for the technical steps.

---

## 7. Current gaps

| Gap | Risk | Action |
| --- | --- | --- |
| Scheduler output goes to `/dev/null` in the documented cron | Silent retention/outbox failures | Redirect to a log file or journal and alert on non-zero exit |
| No APM/metrics/paging provider recorded | Detection depends on manual checks | Choose a provider and document it |
| Vercel logs require access | Preview/runtime failures are opaque to the repo | Obtain a token/login or use an external probe |
| Backend CI dormant | Backend regressions are not gated | Wire the backend jobs into the root workflow |
| No backup restore drill | RPO/RTO are unproven | Schedule and record a restore drill |
| Frontend SLOs undefined | No agreed availability target | Measure first, then publish |

Tracked in [`../DOCUMENTATION-PLAN.md`](../DOCUMENTATION-PLAN.md).

---

## 8. Related

- [`DEPLOYMENT.md`](DEPLOYMENT.md) — post-deploy checks
- [`ROLLBACK.md`](ROLLBACK.md) — recovery
- [`SECRETS.md`](SECRETS.md) — leak response
- [`BACKEND.md`](BACKEND.md) — backend operations
- [`platform-navdhya/docs/20-sre/sre-operations.md`](../../platform-navdhya/docs/20-sre/sre-operations.md) — normative SRE targets
- [`platform-navdhya/docs/22-runbooks/incident-response.md`](../../platform-navdhya/docs/22-runbooks/incident-response.md) — incident ladder
