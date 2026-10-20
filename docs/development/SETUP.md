# Local Setup

**Status:** Current
**Scope:** Clone-to-running for the Next.js apps, the Laravel backend, tests and the optional local infrastructure.

This guide assumes Windows PowerShell or a POSIX shell. Commands are shown from the repository root unless a step says otherwise.

---

## 1. Prerequisites

| Tool | Version | Notes |
| --- | --- | --- |
| Node.js | **20.x** | `.nvmrc` and `package.json` engines; newer Node may work but is not the supported baseline |
| pnpm | **9.15.4** | `packageManager` field; `corepack enable` is the simplest install |
| Git | Current | Hooks are configured by `pnpm install` |
| PHP | **8.2+** | Backend only; CI uses 8.3 |
| Composer | 2.x | Backend only |
| Docker Desktop | Optional | Only for the local Postgres/Redis stack |
| MySQL 8 | Optional | Backend parity; the default local backend uses SQLite |

PHP extensions used by the backend: `mbstring`, `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `sodium`, `intl`.

---

## 2. Clone and install

```sh
git clone <repository-url>
cd v0-navdhya-ritual-services
corepack enable
pnpm install
```

`pnpm install` runs the root `prepare` script, which points Git at `.githooks`. The pre-commit hook is best-effort: it runs gitleaks/detect-secrets when installed, but a missing binary does not block the commit. CI is the enforceable secret scan.

If Corepack is unavailable:

```sh
npm install --global pnpm@9.15.4
```

---

## 3. Frontend environment files

Mock mode is the default. You do not need any environment file to boot the apps.

To wire the customer app to a local backend:

```powershell
Copy-Item apps/customer/.env.example apps/customer/.env.local
```

```sh
cp apps/customer/.env.example apps/customer/.env.local
```

Set at least:

```dotenv
CUSTOMER_BACKEND=laravel
NAVDHYA_API_BASE=http://localhost:8000
CUSTOMER_TENANT_ID=<tenant-ulid>
SESSION_SIGNING_KEY=<same value as backend IDENTITY_HMAC_SECRET>
```

- `NAVDHYA_API_BASE` is an **origin only**. The BFF appends `/api/v1`; do not include it yourself.
- `SESSION_SIGNING_KEY` must equal the backend's `IDENTITY_HMAC_SECRET`.
- `CUSTOMER_TENANT_ID` must come from the backend's seed/provisioning flow, not a made-up string.
- Leave `CUSTOMER_AUTH_MODE` unset/`direct` unless you are testing federation.
- Never set `DEV_CUSTOMER_SESSION` in production.

The web app's `.env.example` contains public analytics/signals settings and server-only Signals secrets. Copy it only if you need those integrations:

```sh
cp apps/web/.env.example apps/web/.env.local
```

The partner and dashboard apps default to their local mock data and do not currently ship `.env.example` files.

---

## 4. Run the frontend apps

All apps through Turbo:

```sh
pnpm dev
```

Or individually:

```sh
pnpm --filter @navdhya/web dev          # http://localhost:3000
pnpm --filter @navdhya/customer dev     # http://localhost:3001
pnpm --filter @navdhya/partner-app dev  # http://localhost:3002
pnpm --filter @navdhya/dashboard dev    # http://localhost:3003
```

Stop a port conflict by finding the process rather than changing the app's documented port.

---

## 5. Backend setup

```sh
cd platform-navdhya
composer install
```

Create the environment file:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

```sh
cp .env.example .env
php artisan key:generate
```

Generate the shared session secret and put the same value in the customer app's `SESSION_SIGNING_KEY`:

```sh
openssl rand -base64 32
```

Set it in `.env`:

```dotenv
IDENTITY_HMAC_SECRET=<generated value>
```

Run the central migrations:

```sh
php artisan migrate
```

The default local configuration uses the SQLite files in `platform-navdhya/database/`. If those files are missing, create them before migrating:

```powershell
New-Item -ItemType File -Force database/central.sqlite
New-Item -ItemType File -Force database/tenant_shared.sqlite
```

> **Tenant migration note:** the backend data-architecture doc describes a `php artisan migrate:tenants` command, but that command is not registered in the current checkout's `artisan list`. Central migrations run with `php artisan migrate`; confirm the module/tenant execution path with the backend owner before relying on a tenant-specific command. Do not use `migrate:fresh` on an e2e or shared database.

Optional seed data:

```sh
php artisan db:seed
```

Start the API:

```sh
php artisan serve          # http://localhost:8000
```

Optional worker and scheduler (separate terminals):

```sh
php artisan queue:work
php artisan schedule:work
```

For MySQL parity, set `CENTRAL_DB_DRIVER=mysql`, `TENANT_DB_DRIVER=mysql` and the corresponding `*_DB_*` values. Use a local database only.

---

## 6. Optional local Postgres/Redis

The compose stack is for the Node platform's optional adapters, not the Laravel app's production database:

```sh
docker compose up -d postgres redis
docker compose ps
docker compose down          # keeps volumes
docker compose down -v       # deletes volumes
```

Connection defaults are documented in [`../operations/local-platform.md`](../operations/local-platform.md). They are development credentials only.

---

## 7. Verify the install

```sh
pnpm verify                 # typecheck + lint + test through Turbo
pnpm check:architecture     # architecture gates + domain manifests
```

Targeted runs:

```sh
pnpm --filter @navdhya/customer test
pnpm --filter @navdhya/web test
pnpm --filter @navdhya/partner-app test
pnpm --filter @navdhya/dashboard test
pnpm exec vitest run tests/api/customer-contract.test.ts
```

Backend:

```sh
cd platform-navdhya
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
./vendor/bin/deptrac analyse --no-cache
php artisan api:compose --check
```

Full-stack E2E (real MySQL, real Laravel, real BFF) is documented in [`../../e2e/README.md`](../../e2e/README.md):

```powershell
powershell -ExecutionPolicy Bypass -File e2e\run.ps1
```

Do not point E2E at production MSG91, Razorpay or customer data. The Linux runner `e2e/run.sh` is not present yet; the nightly e2e job is disabled until it is.

---

## 8. Common problems

| Problem | Fix |
| --- | --- |
| `pnpm: command not found` | `corepack enable`, then `pnpm install` |
| Wrong Node version | `nvm use 20` (or the Windows equivalent), then reinstall |
| `EPERM`/symlink error during a local build | Set `NEXT_STANDALONE=0` for local verification; never for CI/deploy |
| Port already in use | Find and stop the conflicting process; keep the documented port |
| `php artisan` cannot connect to SQLite | Ensure `database/central.sqlite` and `database/tenant_shared.sqlite` exist and are writable |
| `could not find driver` | Enable `pdo_sqlite`/`pdo_mysql` and `sqlite3` in PHP |
| Composer memory exhaustion | Re-run with `php -d memory_limit=-1 composer install` |
| Customer login fails in wired mode | Check `SESSION_SIGNING_KEY` = `IDENTITY_HMAC_SECRET`, `NAVDHYA_API_BASE` origin, and tenant ID |
| Backend 500 after clone | Run `php artisan key:generate`; check `storage/` and `bootstrap/cache/` permissions |
| Hook does not scan secrets | Install gitleaks/detect-secrets; CI still scans |

---

## 9. Next steps

- [`TESTING.md`](TESTING.md) — test layers and CI mapping.
- [`ENVIRONMENT-VARIABLES.md`](ENVIRONMENT-VARIABLES.md) — full variable reference.
- [`../architecture/dependency-rules.md`](../architecture/dependency-rules.md) — boundaries before adding imports.
- [`../../CONTRIBUTING.md`](../../CONTRIBUTING.md) — branch, PR and review expectations.
- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) — when you are ready to ship.
