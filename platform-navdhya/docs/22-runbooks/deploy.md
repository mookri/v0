# Runbook — Deploy & Scheduler

**Status:** ACTIVE · **Date:** 2026-10-02
Day-to-day deploy procedure for staging/production. Tenant
provisioning lives in [provisioning](./provisioning.md); incidents in
[incident response](./incident-response.md).

---

## 1. First deploy (or fresh environment)

Order matters — keys before traffic, migrations before seed:

1. **Environment** — copy `.env.example`, set `APP_KEY`
   (`php artisan key:generate`), database credentials, `APP_URL`
   (canonical base — discovery, JWKS, and redirect validation derive
   from it), `IDENTITY_HMAC_SECRET` (pending VAULT-001, env file).
2. **Migrations** — `php artisan migrate --force`. Our migrations are
   timestamp-ordered; the stock Passport `oauth_*` tables
   (`043234`–`043238`) and the lookup index (`043239`) run after the
   `oauth_client_profiles` table (`000001`) by timestamp — no manual
   ordering needed, but never run `--step` selectively in prod.
3. **Passport keys** — `php artisan passport:keys --force` (RS256
   pair for JWT issuance + JWKS). Keys are local files in
   `storage/`; production key material moves to vault storage under
   VAULT-001 — until then, back up `storage/oauth-*.key` with the
   environment backup. Missing keys surface as 503 on
   `/.well-known/jwks.json` (fail-closed, never unsigned tokens).
4. **Scheduler cron** — install the single entry point (see §2).
   Without it, retention hygiene never fires and tables grow
   unbounded.
5. **Smoke** — `php artisan api:compose --check` (16 fragments /
   134 paths), `GET /up` → 200, `GET /ready` → 200,
   `GET /.well-known/oauth-authorization-server` → 200.

## 2. Scheduler (required)

`routes/console.php` defines the retention schedule:

| Command | Cadence | Retains |
|---------|---------|---------|
| `cleanup:expired --force` | daily | expired OTP challenges, invites, sessions, step-up proofs |
| `passport:purge --hours=168` | weekly | revoked/expired OAuth tokens + auth codes (168 h forensics window) |
| `outbox:drain` | every minute | dispatches pending outbox events to registered handlers (F2); rows whose `event_type` has no consumer yet stay pending untouched |

Install **one** cron entry (system crontab, not per-command timers):

```cron
* * * * * cd /path/to/platform-navdhya && php artisan schedule:run >> /dev/null 2>&1
```

Verify with `php artisan schedule:list` — all three commands must appear.
To run a sweep ad hoc: `php artisan cleanup:expired --force`.

## 2.1 Queue worker (required once consumers enqueue)

Notification dispatch and mail enqueue on the **database** queue
(`QUEUE_CONNECTION=database`). HTTP requests never run jobs — without a
worker they sit in the `jobs` table unprocessed. Supervisor unit (or
any process manager):

```ini
[program:navdhya-worker]
command=php artisan queue:work --sleep=1 --tries=3 --max-time=3600
directory=/path/to/platform-navdhya
autostart=true
autorestart=true
user=www-data
```

`--max-time=3600` bounds memory drift (the supervisor restarts).
Verify: rows in `jobs`, or `php artisan queue:monitor jobs:pending`.
The scheduler cron drives `outbox:drain`; handlers that enqueue work
hand off to this worker. The app has no container image in this repo —
worker and scheduler run on the host (compose carries postgres/redis
only).

## 3. Every deploy

1. `git pull` the release tag (no remote yet — see GIT-001).
2. `composer install --no-dev --optimize-autoloader`.
3. `php artisan migrate --force` (additive-only per constitution;
   a migration that drops/renames is a reject at review).
4. `php artisan config:cache && php artisan route:cache`.
5. Health: `/up` → 200, `/ready` → 200 before adding the node
   back to the load balancer. The web BFF ships its own probes:
   `/api/healthz` (liveness, static) and `/api/readyz` (readiness —
   probes this `/up` with a 2 s budget when `CUSTOMER_BACKEND=laravel`).
6. Watch P1 alert classes post-deploy (audit chain, refresh reuse,
   isolation failure) per [SRE operations](../20-sre/sre-operations.md#alert-classes--response).

## 4. Rollback

Releases are additive migrations + backward-compatible code, so
rollback is code-only: check out the previous tag, re-run step 4
above. Never `migrate:rollback` in staging/prod (down-migrations
exist for dev/test only).
