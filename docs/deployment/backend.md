# Backend API Deployment

**Status:** Current — host-based procedure exists
**App:** Laravel 12 modular monolith (`platform-navdhya/`)
**Port:** 8000
**Production origin:** `api.navdhya.com` (documented target)
**Owner:** `@eng-c`

---

## 1. Overview

The backend is a Laravel 12 modular monolith handling identity, tenancy, authorization, bookings, catalog, payments, onboarding, search, and platform operations. It is deployed separately from the frontend apps.

### 1.1 Key characteristics

- No Dockerfile; host-based deployment.
- MySQL 8 in production (central + tenant-shared).
- SQLite for local/test.
- Database queue; worker required.
- Scheduler cron required.
- Passport RS256 keys (JWKS).

### 1.2 Canonical procedure

The canonical deploy procedure is [`platform-navdhya/docs/22-runbooks/deploy.md`](../../platform-navdhya/docs/22-runbooks/deploy.md). The operator summary is [`../operations/BACKEND.md`](../operations/BACKEND.md).

---

## 2. First deploy

1. **Environment** — create `.env` from `.env.example`; set `APP_KEY`, `APP_URL`, database credentials, `IDENTITY_HMAC_SECRET`.
2. **Migrations** — `php artisan migrate --force` (central schema).
3. **Passport keys** — `php artisan passport:keys --force`; back up `storage/oauth-*.key`.
4. **Scheduler** — install one cron entry: `* * * * * cd /path/to/platform-navdhya && php artisan schedule:run`.
5. **Queue worker** — run under Supervisor or systemd.
6. **Smoke** — `php artisan api:compose --check`, `/up`, `/ready`, `/.well-known/oauth-authorization-server`.

Do not seed production with demo data. Do not run `migrate:fresh` on any environment that holds data.

---

## 3. Every deploy

```sh
# The backend has no configured remote in some environments (GIT-001).
# Fetch the tag from the configured remote, then check it out explicitly:
git fetch origin tag <release-tag> --no-tags
git checkout <release-tag>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
# restart PHP-FPM and the queue worker
# verify /up and /ready before returning the node to traffic
```

### 3.1 Rules

- Migrations are **additive only** in staging/production.
- Never run `migrate:rollback` in staging/production.
- Restart the worker after changing job code.
- Clear/rebuild caches if config or routes changed.
- `php artisan migrate` runs the **central** schema. The `migrate:tenants` command is documented but not registered in the current checkout; confirm the tenant execution path before relying on it.

---

## 4. Health checks

| Endpoint | Purpose | Expected |
| --- | --- | --- |
| `GET /up` | Liveness | 200 |
| `GET /ready` | Readiness | 200 (DB, Redis, queue, outbox) |
| `GET /.well-known/jwks.json` | Signing keys | 200 with key set |
| `GET /.well-known/oauth-authorization-server` | OAuth discovery | 200 |
| `php artisan api:compose --check` | API composition | Composed artifact current |
| `php artisan schedule:list` | Scheduler | All commands present |
| `php artisan queue:monitor jobs:pending` | Queue | Draining |

---

## 5. Rollback

Code-only:

```sh
git checkout <previous-release-tag>
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
# restart PHP-FPM and the queue worker
```

Do not down-migrate. Restore from pre-deploy backup if a schema change is incompatible.

---

## 6. Related

- [`../../platform-navdhya/README.md`](../../platform-navdhya/README.md)
- [`../../platform-navdhya/docs/22-runbooks/deploy.md`](../../platform-navdhya/docs/22-runbooks/deploy.md)
- [`../../platform-navdhya/docs/22-runbooks/incident-response.md`](../../platform-navdhya/docs/22-runbooks/incident-response.md)
- [`../operations/BACKEND.md`](../operations/BACKEND.md)
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md)
- [`../operations/SECRETS.md`](../operations/SECRETS.md)
