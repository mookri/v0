# Navdhya Platform API

Laravel 12 modular monolith for Navdhya: identity, tenancy, authorization, bookings, catalog, payments, onboarding, search and platform operations.

This is the backend half of the Navdhya monorepo. The frontend apps and cross-platform documentation live at the repository root.

---

## Stack

| Item | Value |
| --- | --- |
| PHP | ^8.2 (CI uses 8.3) |
| Laravel | ^12 |
| Auth | Laravel Passport (RS256, JWKS) |
| Production DB | MySQL 8 (central + tenant-shared) |
| Local/test DB | SQLite |
| Queue | Database by default; worker required |
| Scheduler | One cron entry running `schedule:run` |
| Frontend assets | Vite |
| Container | None in this repository; host-based deploy |

---

## Quick start

```sh
composer install
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve             # http://localhost:8000
```

Generate the shared session secret and add it to `.env`:

```sh
openssl rand -base64 32
```

```dotenv
IDENTITY_HMAC_SECRET=<generated value>
```

The customer app's `SESSION_SIGNING_KEY` must use the same value.

Optional worker and scheduler:

```sh
php artisan queue:work
php artisan schedule:work
```

Local defaults use the SQLite files in `database/`. For MySQL parity, set `CENTRAL_DB_DRIVER=mysql`, `TENANT_DB_DRIVER=mysql` and the matching `*_DB_*` values.

> **Tenant migrations:** `php artisan migrate` runs the central schema. The data-architecture doc describes a `migrate:tenants` command, but it is not registered in the current checkout's `artisan list`. Confirm the module/tenant execution path with the backend owner before relying on a tenant-specific command. Never run `migrate:fresh` on shared/e2e databases.

---

## Modules

The application is organized under `app/Modules/` and `app/Platform/`:

- `Booking`, `Catalog`, `Customers`, `Onboarding`, `Pandit`, `Payment`, `Search`, `SeoContent`, `Temple`, `Vendor`
- Platform concerns: identity, tenancy, authorization, audit, outbox, API composition

Architecture boundaries are enforced by Deptrac and PHPStan. See `deptrac.yaml`, `phpstan.neon` and the platform docs.

---

## Tests and static analysis

```sh
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
./vendor/bin/deptrac analyse --no-cache
php artisan api:compose --check
php artisan platform:doctor --json
```

MySQL parity lane:

```sh
# after provisioning the test databases named in phpunit.mysql.xml
php artisan test --configuration=phpunit.mysql.xml
```

The backend CI specification is `.github/workflows/ci.yml` in this directory. It is **dormant** in the monorepo because GitHub only reads `.github/workflows/` at the repository root. Until the backend is split into its own repository or the jobs are moved/called from the root workflow, run the equivalent commands locally or on the host.

---

## API composition

The API is composed from module fragments:

```sh
php artisan api:compose --check
```

The runbook expects 16 fragments and 134 paths. Treat a drift failure as a contract change, not a formatting error.

---

## Deploy

Canonical procedure: [`docs/22-runbooks/deploy.md`](docs/22-runbooks/deploy.md).

Operator summary: [`../docs/operations/BACKEND.md`](../docs/operations/BACKEND.md).

First deploy order:

1. Environment: `APP_KEY`, `APP_URL`, database credentials, `IDENTITY_HMAC_SECRET`.
2. Migrations: `php artisan migrate --force`.
3. Passport keys: `php artisan passport:keys --force`; back up `storage/oauth-*.key`.
4. Scheduler cron: `php artisan schedule:run` every minute.
5. Queue worker under Supervisor/systemd.
6. Smoke: `/up`, `/ready`, `/.well-known/oauth-authorization-server`, `api:compose --check`.

Every deploy:

```sh
git pull <release-tag>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
# restart PHP-FPM and the queue worker
```

Rollback is code-only. Never run `migrate:rollback` in staging or production.

`docs/22-runbooks/provisioning/PROD-ENV.md` describes a **target** Vault/ECS-style environment, not the current host deploy. Do not follow it as the current procedure.

---

## Health

| Endpoint | Purpose |
| --- | --- |
| `GET /up` | Laravel liveness |
| `GET /ready` | Readiness: DB, Redis, queue, outbox per the SRE contract |
| `GET /.well-known/jwks.json` | Signing keys |
| `GET /.well-known/oauth-authorization-server` | OAuth discovery |

The web BFF's `/api/readyz` probes `/up` with a 2-second budget when wired.

---

## Security

- Do not commit `.env`, provider keys or `storage/oauth-*.key`.
- `MSG91_AUTH_KEY` and payment secrets are server-only.
- Report vulnerabilities via the root [`SECURITY.md`](../SECURITY.md).
- Secret inventory and rotation: [`../docs/operations/SECRETS.md`](../docs/operations/SECRETS.md).

---

## Documentation

| Document | Description |
| --- | --- |
| [`../docs/operations/BACKEND.md`](../docs/operations/BACKEND.md) | Operator summary |
| [`docs/22-runbooks/deploy.md`](docs/22-runbooks/deploy.md) | Deploy, scheduler and queue |
| [`docs/22-runbooks/incident-response.md`](docs/22-runbooks/incident-response.md) | Incident ladder |
| [`docs/22-runbooks/provisioning.md`](docs/22-runbooks/provisioning.md) | Tenant provisioning |
| [`docs/20-sre/sre-operations.md`](docs/20-sre/sre-operations.md) | Targets, health and alert classes |
| [`docs/20-sre/ha-dr.md`](docs/20-sre/ha-dr.md) | RPO/RTO and restore drills |
| [`docs/13-data/data-architecture.md`](docs/13-data/data-architecture.md) | Data and migration model |
| [`../docs/architecture/`](../docs/architecture/) | Cross-platform architecture |
| [`../docs/README.md`](../docs/README.md) | Full documentation index |
