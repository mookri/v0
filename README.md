# Navdhya

Navdhya is a spiritual-services platform for booking and managing Vedic rituals, pandit services, payments and partner operations. This repository is the production monorepo: four Next.js applications, a Laravel platform API, shared capability packages, and the CI/operations tooling that ships them.

---

## Applications

| App | Package | Local port | Documented origin | Purpose |
| --- | --- | --- | --- | --- |
| Web | `@navdhya/web` | 3000 | `www.navdhya.com` | Public discovery, puja pages, booking and account surfaces |
| Customer | `@navdhya/customer` | 3001 | `app.navdhya.com` | Authenticated customer workspace |
| Dashboard | `@navdhya/dashboard` | 3003 | Private/TBD | Internal operations |
| Partner | `@navdhya/partner-app` | 3002 | `partner.navdhya.com` | Pandit/partner task workflow |
| API | Laravel 12 | 8000 | `api.navdhya.com` (target) | Identity, tenancy, bookings, payments and platform API |

The apps consume `@navdhya/*` packages. They do not import each other. See [`docs/architecture/monorepo.md`](docs/architecture/monorepo.md) and [`docs/architecture/dependency-rules.md`](docs/architecture/dependency-rules.md).

---

## Stack

- **Frontend:** Next.js 16, React 19, TypeScript, Tailwind CSS 4, Turborepo, pnpm 9.15.4.
- **Backend:** Laravel 12, PHP 8.2+, MySQL 8 in production, SQLite for local/test, Laravel Passport.
- **Testing:** Jest (web), Vitest (apps/packages/contracts), PHPUnit (backend), Playwright (full-stack E2E).
- **CI/CD:** GitHub Actions — `ci.yml`, `security.yml`, `nightly.yml`, `release.yml`.
- **Deployment:** Vercel project integration (preview checks currently failing), tag-driven release artefacts, and a host-based backend path. Current status and blockers are in [`docs/operations/DEPLOYMENT.md`](docs/operations/DEPLOYMENT.md).

---

## Quick start

Requirements: Node.js **20.x**, pnpm **9.15.4**, Git. PHP 8.2+ and Composer 2 are needed only for the backend.

```sh
corepack enable
pnpm install
pnpm dev
```

Open `http://localhost:3000` for web, `3001` for customer, `3002` for partner and `3003` for dashboard.

The apps default to mock mode and need no environment file. To wire the customer app to a local Laravel API, follow [`docs/development/SETUP.md`](docs/development/SETUP.md).

### Backend

```sh
cd platform-navdhya
composer install
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
php artisan key:generate
# SQLite files must exist before migrating (fresh checkout has none):
# PowerShell: New-Item -ItemType File -Force database/central.sqlite, database/tenant_shared.sqlite
# POSIX: touch database/central.sqlite database/tenant_shared.sqlite
php artisan migrate
php artisan serve             # http://localhost:8000
```

Optional worker and scheduler:

```sh
php artisan queue:work
php artisan schedule:work
```

---

## Common commands

```sh
pnpm dev                 # all apps through Turbo
pnpm build               # all apps
pnpm typecheck
pnpm lint
pnpm test
pnpm verify              # typecheck + lint + test
pnpm check:architecture  # architecture gates + domain manifests
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

Full-stack E2E is documented in [`e2e/README.md`](e2e/README.md). It uses real MySQL/Laravel/BFF and must never point at production.

---

## How it fits together

```
apps/web ─┐
apps/customer ─┼─► @navdhya/* packages ─► BFF route handlers ─► Laravel API
apps/partner ─┤                                             ├─ MySQL 8
apps/dashboard ┘                                             ├─ queue worker
                                                             └─ scheduler
```

Key rules:

- The BFF stays same-origin; browser calls are relative.
- Server Components and route handlers call the backend with the session cookie.
- BFF paths must resolve to real Laravel routes; the contract-test allow-list is empty.
- Migrations are additive in staging/production; rollback is code-only.
- No secret is ever exposed through `NEXT_PUBLIC_*`.

---

## Documentation

Start at [`docs/README.md`](docs/README.md).

| Need | Read |
| --- | --- |
| Local setup | [`docs/development/SETUP.md`](docs/development/SETUP.md) |
| Tests and CI | [`docs/development/TESTING.md`](docs/development/TESTING.md) |
| Environment variables | [`docs/development/ENVIRONMENT-VARIABLES.md`](docs/development/ENVIRONMENT-VARIABLES.md) |
| Deploy and release | [`docs/operations/DEPLOYMENT.md`](docs/operations/DEPLOYMENT.md) |
| Environments | [`docs/operations/ENVIRONMENTS.md`](docs/operations/ENVIRONMENTS.md) |
| Vercel | [`docs/operations/VERCEL.md`](docs/operations/VERCEL.md) |
| Backend operations | [`docs/operations/BACKEND.md`](docs/operations/BACKEND.md) and [`platform-navdhya/README.md`](platform-navdhya/README.md) |
| Secrets | [`docs/operations/SECRETS.md`](docs/operations/SECRETS.md) |
| Rollback | [`docs/operations/ROLLBACK.md`](docs/operations/ROLLBACK.md) |
| Architecture | [`docs/architecture/`](docs/architecture/) |
| API | [`docs/api/overview.md`](docs/api/overview.md) |
| CI/CD plan | [`docs/CI-CD-PIPELINE-PLAN.md`](docs/CI-CD-PIPELINE-PLAN.md) |
| Documentation plan | [`docs/DOCUMENTATION-PLAN.md`](docs/DOCUMENTATION-PLAN.md) |

The backend's deep design/specification set lives in [`platform-navdhya/docs/`](platform-navdhya/docs/).

---

## Contributing

Read [`CONTRIBUTING.md`](CONTRIBUTING.md) before opening a PR. PR titles must follow Conventional Commits (`feat`, `fix`, `docs`, `ci`, …). High-risk areas — auth, payments, tenancy, deploy workflows — need explicit review.

## Security

Do not report vulnerabilities in a public issue. Follow [`SECURITY.md`](SECURITY.md). Never commit real credentials; see [`docs/operations/SECRETS.md`](docs/operations/SECRETS.md).

---

## Current state

This is an active platform, not a finished product:

- Vercel preview checks for the known projects are currently failing; the cause requires Vercel log access. See [`docs/operations/VERCEL.md`](docs/operations/VERCEL.md).
- Host deploy scripts (`scripts/deploy/staging.sh`, `production.sh`) are not implemented. The release workflow is intentionally disabled until they are.
- Backend CI is specified but dormant in the monorepo; see [`docs/operations/BACKEND.md`](docs/operations/BACKEND.md).
- `e2e/run.sh` is missing, so the Linux nightly E2E job is disabled.
- Branch protection and required checks depend on the GitHub plan/settings; a green PR is not physically enforced by the repository alone.

The authoritative gap register is [`docs/DOCUMENTATION-PLAN.md`](docs/DOCUMENTATION-PLAN.md).
