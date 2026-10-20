# Backend (Laravel)

**Status:** Current (host-based procedure); some infrastructure is **Target**
**Scope:** Deploying and operating `platform-navdhya/` for frontend/operator readers.

The canonical backend procedure is [`platform-navdhya/docs/22-runbooks/deploy.md`](../../platform-navdhya/docs/22-runbooks/deploy.md). This page is the operator summary and the bridge to the frontend deployment docs. If the two disagree, the backend runbook wins for backend steps.

---

## 1. Stack

| Item | Value |
| --- | --- |
| Framework | Laravel **12** |
| PHP | **^8.2**; CI uses **8.3** |
| Production database | **MySQL 8** (central + tenant-shared connections) |
| Local/test database | SQLite (`database/central.sqlite`, `database/tenant_shared.sqlite`) |
| Queue | Laravel **database** queue by default |
| Scheduler | One system cron entry running `schedule:run` every minute |
| Auth | Laravel Passport (RS256 keys, JWKS) |
| OTP | MSG91 widget verification; server-only `MSG91_AUTH_KEY` |
| Payments | Razorpay; test mode by default |
| Cache/session | Database by default; Redis available |
| Container image | **None in this repository**; host-based deploy |

There is no Dockerfile and no application container in `docker-compose.yml`. The compose file carries optional Postgres/Redis for Node adapters only. The queue worker and scheduler run on the host.

---

## 2. Deployment model

```
release tag ──► host checkout
                 ├─ composer install --no-dev --optimize-autoloader
                 ├─ php artisan migrate --force
                 ├─ php artisan config:cache && php artisan route:cache
                 ├─ restart PHP-FPM
                 └─ restart the queue worker
```

The Next.js release workflow does **not** deploy the backend. Backend and frontend are promoted separately, with the compatibility rule: deploy backend-compatible code before frontend code that depends on it.

The backend has its own CI specification at `platform-navdhya/.github/workflows/ci.yml`. It is **dormant** in this monorepo because GitHub only reads `.github/workflows/` at the repository root. It becomes active if the backend is split into its own repository or its jobs are moved/called from the root workflow. Until then, run the equivalent commands locally/on the host.

---

## 3. First deploy

Follow the detailed runbook. The order is:

1. **Environment** — create `.env` from `.env.example`; set `APP_KEY`, `APP_URL`, database credentials and `IDENTITY_HMAC_SECRET`.
2. **Migrations** — `php artisan migrate --force`. Migrations are timestamp-ordered; never use `--step` selectively in production.
3. **Passport keys** — `php artisan passport:keys --force`. Back up `storage/oauth-*.key`. Missing keys surface as a 503 on `/.well-known/jwks.json`.
4. **Scheduler** — install one cron entry:
   ```cron
   * * * * * cd /path/to/platform-navdhya && php artisan schedule:run >> /dev/null 2>&1
   ```
   Verify with `php artisan schedule:list`.
5. **Queue worker** — run under Supervisor or the host process manager:
   ```ini
   [program:navdhya-worker]
   command=php artisan queue:work --sleep=1 --tries=3 --max-time=3600
   directory=/path/to/platform-navdhya
   autostart=true
   autorestart=true
   user=www-data
   ```
6. **Smoke** — `php artisan api:compose --check`, `GET /up` → 200, `GET /ready` → 200, `GET /.well-known/oauth-authorization-server` → 200.

Do not seed production with demo data. Do not run `migrate:fresh` on any environment that holds data.

---

## 4. Every deploy

```sh
git pull <release-tag>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
# restart PHP-FPM and the queue worker
# verify /up and /ready before returning the node to traffic
```

Additional rules:

- `php artisan migrate` runs the **central** schema. The data-architecture doc describes `migrate:tenants`, but that command is not registered in the current checkout; confirm the tenant/module execution path with the backend owner before relying on it.
- **Additive migrations only.** A migration that drops or renames is rejected at review.
- **Never `migrate:rollback` in staging/production.** Down-migrations exist for development/tests only.
- **Restart the worker** after changing job code, queue configuration or failed-job behavior.
- **Clear/rebuild caches** if configuration or routes changed; otherwise stale caches mask the deploy.
- **Watch alert classes** after deploy: audit chain, refresh-token reuse, isolation failures, payment verification and queue backlog. See the backend SRE operations doc.

---

## 5. Configuration essentials

Use [`../development/ENVIRONMENT-VARIABLES.md`](../development/ENVIRONMENT-VARIABLES.md) for the full reference. The production-critical values are:

| Variable | Notes |
| --- | --- |
| `APP_ENV=production`, `APP_DEBUG=false` | Debug output must never be public |
| `APP_KEY` | Generate once per environment; back it up |
| `APP_URL` | Canonical origin used by discovery, JWKS and redirect validation |
| `DB_CONNECTION=central` | Central connection is the Laravel default |
| `CENTRAL_DB_*` | MySQL 8 central database |
| `TENANT_DB_*` | MySQL 8 tenant-shared database |
| `IDENTITY_HMAC_SECRET` | Must equal the customer app's `SESSION_SIGNING_KEY` |
| `QUEUE_CONNECTION=database` | Requires a running worker |
| `SESSION_DRIVER`, `CACHE_STORE` | Database by default; Redis optional |
| `MSG91_AUTH_KEY` | Server-only; boot fails closed without it |
| `PAYMENTS_DRIVER`, `PAYMENTS_TEST_MODE` | Test mode by default; live requires approval |
| `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET` | Live values only in production |
| `MAIL_*` | Real provider in production; log/array in tests |
| `AWS_*` | Only if S3-compatible storage is enabled |

`SESSION_SIGNING_KEY` (customer app) and `IDENTITY_HMAC_SECRET` (backend) are a matched pair. Rotate them together.

---

## 6. Health and smoke checks

| Check | Expected |
| --- | --- |
| `GET /up` | 200 (Laravel bootstrap health) |
| `GET /ready` | 200 (readiness) |
| `GET /.well-known/jwks.json` | 200 with key set |
| `GET /.well-known/oauth-authorization-server` | 200 |
| `php artisan api:compose --check` | Composed API artifact is current |
| `php artisan schedule:list` | All scheduled commands present |
| `php artisan queue:monitor jobs:pending` | Queue is draining |

The web BFF's `/api/readyz` probes `/up` with a 2-second budget when `CUSTOMER_BACKEND=laravel`.

---

## 7. Local backend

```sh
cd platform-navdhya
composer install
cp .env.example .env        # PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve            # http://localhost:8000
php artisan queue:work       # optional, second terminal
php artisan schedule:work    # optional, second terminal
```

Local defaults use SQLite files already present under `platform-navdhya/database/`. For MySQL parity, set `CENTRAL_DB_DRIVER=mysql` and `TENANT_DB_DRIVER=mysql` with local credentials. Do not point local tests at production.

Tests and static analysis:

```sh
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
./vendor/bin/deptrac analyse --no-cache
php artisan api:compose --check
php artisan platform:doctor --json
```

---

## 8. Rollback

Rollback is **code-only**:

1. Check out the previous release tag.
2. `composer install --no-dev --optimize-autoloader`.
3. Rebuild config/route caches.
4. Restart PHP-FPM and the queue worker.
5. Verify `/up` and `/ready`.

Do not down-migrate. If a release made an incompatible schema change, that is a process failure: restore from the pre-deploy backup per the incident plan and fix forward with a new additive migration.

Full procedure: [`ROLLBACK.md`](ROLLBACK.md).

---

## 9. Known gaps

- `platform-navdhya/docs/22-runbooks/provisioning/PROD-ENV.md` describes a **target** Vault/ECS-style environment, not the current host deploy. A banner now says so.
- Backend CI is dormant in the monorepo.
- Production secret storage (host `.env` vs Vault) is not fully decided; see [`SECRETS.md`](SECRETS.md).
- There is no container image or Kubernetes manifest. Do not plan a container deploy from this repository without adding one.
- Database backup/restore has no dated drill yet.

---

## 10. Related

- [`platform-navdhya/README.md`](../../platform-navdhya/README.md) — backend entry point
- [`platform-navdhya/docs/22-runbooks/deploy.md`](../../platform-navdhya/docs/22-runbooks/deploy.md) — canonical deploy procedure
- [`platform-navdhya/docs/22-runbooks/incident-response.md`](../../platform-navdhya/docs/22-runbooks/incident-response.md) — incidents
- [`platform-navdhya/docs/20-sre/sre-operations.md`](../../platform-navdhya/docs/20-sre/sre-operations.md) — alert classes
- [`../development/ENVIRONMENT-VARIABLES.md`](../development/ENVIRONMENT-VARIABLES.md) — env reference
- [`DEPLOYMENT.md`](DEPLOYMENT.md) — frontend + release path
- [`ROLLBACK.md`](ROLLBACK.md) — recovery
