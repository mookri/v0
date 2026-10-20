# Deployment

**Status:** Current for the documented paths; the host deploy path is **Target**
**Scope:** Frontend apps, Laravel backend, release artefacts and first-response verification.

This is the canonical deployment page. It separates what is implemented from what is planned:

| Path | State | Where |
| --- | --- | --- |
| Vercel project integration | Configured; previews currently red | [`VERCEL.md`](VERCEL.md) |
| Release artefact build + attestation | Implemented | `.github/workflows/release.yml` |
| Host deploy from release artefacts | **Target** — scripts missing | `scripts/deploy/README.md` |
| Laravel host deploy | Implemented procedure; host access required | [`BACKEND.md`](BACKEND.md), [`platform-navdhya/docs/22-runbooks/deploy.md`](../../platform-navdhya/docs/22-runbooks/deploy.md) |
| Backend CI enforcement | **Target** — workflow dormant in the monorepo | `platform-navdhya/.github/workflows/ci.yml` |

---

## 1. Topology

```
Browser
  │
  ├─ www.navdhya.com .............. apps/web (Next.js, public)
  ├─ app.navdhya.com .............. apps/customer (Next.js, authenticated)
  ├─ partner.navdhya.com .......... apps/partner (Next.js, authenticated)
  └─ dashboard (private) .......... apps/dashboard (Next.js, internal)
          │
          │  same-origin BFF route handlers
          ▼
  Laravel API (api.navdhya.com)
          │
          ├─ MySQL 8: central + tenant_shared
          ├─ database queue  ──► queue worker (host)
          └─ scheduler cron  ──► outbox:drain, retention, Passport purge
```

| Component | Runtime | Notes |
| --- | --- | --- |
| Four Next.js apps | Node.js 20.x | `NEXT_STANDALONE=1` for deploy builds |
| BFF | Next.js route handlers | Server-side calls use cookies; browser calls stay relative |
| Laravel API | PHP 8.2+ (CI uses 8.3) | Laravel 12 modular monolith |
| Database | MySQL 8 in production | SQLite is the local/test fast lane |
| Queue | Laravel database queue | A worker is required once jobs are enqueued |
| Scheduler | One system cron entry | Runs `schedule:run` every minute |
| Object storage | Local disk by default | S3-compatible configuration exists in `.env.example` |
| OTP | MSG91 widget | Never use live MSG91 from preview or tests |
| Payments | Razorpay | Test mode by default; live keys require approval |

---

## 2. Prerequisites

### Workstation

- Node.js **20.x** (see `.nvmrc` and `package.json` engines).
- pnpm **9.15.4** (`packageManager` field).
- Git and the repository's hooks (`pnpm install` configures `core.hooksPath`).
- Optional: Docker Desktop for the local Postgres/Redis stack.
- Optional: PHP **8.2+**, Composer 2, and the PHP extensions used by the backend (`mbstring`, `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `sodium`, `intl`).

### Release access

- GitHub write access to create tags and approve environments.
- Vercel access if deploying through Vercel.
- SSH/host access and the process-manager account if deploying the release artefacts or backend.
- DNS/TLS access for domain changes.
- Secret-manager access for the target environment. See [`SECRETS.md`](SECRETS.md).

### Repository variables and secrets

| Name | Where | Purpose |
| --- | --- | --- |
| `DEPLOY_ENABLED` | GitHub repository variable | Must be `'true'` before the release deploy job runs |
| `CI_ADVANCED_SECURITY` | GitHub repository variable | Enables GHAS-only security jobs |
| `CI_STRICT` | GitHub repository variable | Promotes selected soft gates |
| `STAGING_SSH_KEY` | GitHub `staging` environment secret | Target deploy credential |
| `PRODUCTION_SSH_KEY` | GitHub `production` environment secret | Target deploy credential |
| `TURBO_TOKEN` / `TURBO_TEAM` | GitHub secrets/variables | Optional remote cache |

Do not add these to `.env.example` or any tracked file.

---

## 3. Build the frontend artefacts

From the repository root:

```sh
pnpm install --frozen-lockfile
NEXT_STANDALONE=1 pnpm --filter @navdhya/web run build
NEXT_STANDALONE=1 pnpm --filter @navdhya/customer run build
NEXT_STANDALONE=1 pnpm --filter @navdhya/dashboard run build
NEXT_STANDALONE=1 pnpm --filter @navdhya/partner-app run build
```

PowerShell: set `$env:NEXT_STANDALONE='1'` before the build; `next.config.ts` reads it at build time.

On Windows, standalone tracing can fail with `EPERM`; local verification uses `NEXT_STANDALONE=0`. That escape hatch must never be used for a deployable build.

Outputs:

```
apps/<app>/.next/standalone/   # server bundle
apps/<app>/.next/static/       # client chunks, CSS, fonts
apps/<app>/public/             # static public files
apps/<app>/.next/BUILD_ID
```

### Release artifact contract

`release.yml` currently packages **only** `.next/standalone`:

```
dist/navdhya-<app>-<tag>.tar.gz   # contains standalone/
dist/sha256sums-<app>.txt
```

> **Known blocker:** a Next.js standalone directory is not self-contained. The server also needs `.next/static` and `public` copied beside/inside the standalone output. The current release tarball does not include them, and the deploy job checks out source rather than built assets. A host deploy must add those files before starting the server, or the release workflow must be amended to package them. Until then, the host path is not production-ready even after `scripts/deploy/*.sh` exist.

`NEXT_PUBLIC_*` values are baked at build time. Set them before `next build`; changing them later requires a rebuild. Server-only variables are read at runtime.

---

## 4. Frontend deployment

### Option A — Vercel (configured integration)

Use one Vercel project per app, with the app's directory as **Root Directory**. Do not point both projects at the repository root.

| Vercel project | App | Root Directory | Production domain |
| --- | --- | --- | --- |
| `navdhya` | Web | `apps/web` | `www.navdhya.com` (verify assignment) |
| `navdhya-0` | Customer or another app | Verify in Vercel | Verify |
| Additional project | Partner | `apps/partner` | `partner.navdhya.com` |
| Additional project | Dashboard | `apps/dashboard` | TBD/private |

Exact project-to-app mapping and build logs require Vercel access. The two known project names are `navdhya` and `navdhya-0`; both preview checks are currently failing. Follow [`VERCEL.md`](VERCEL.md) to capture the error and fix the settings.

### Option B — release artefact on a host (target)

The intended flow:

1. Create a `v*` tag. `release.yml` verifies the commit through `ci.yml`, builds four standalone artefacts, attests provenance and publishes a GitHub Release.
2. A release operator downloads the artefacts and checksums.
3. `scripts/deploy/<env>.sh` (not yet implemented) verifies the checksums and deploys each app to its host.
4. The script restarts the app process and runs smoke checks.

There is **no Dockerfile** in this repository. Host deployment is a Node process managed by the host's process manager, not a container image.

The required script contract is documented in [`scripts/deploy/README.md`](../../scripts/deploy/README.md). The release job fails loudly if the script is absent:

```text
scripts/deploy/<env>.sh is required before DEPLOY_ENABLED can be true
```

---

## 5. Backend deployment

The backend is a Laravel application in `platform-navdhya/`. It is **not** deployed by the Next.js release job. The canonical procedure is [`platform-navdhya/docs/22-runbooks/deploy.md`](../../platform-navdhya/docs/22-runbooks/deploy.md); the operator summary is [`BACKEND.md`](BACKEND.md).

### First deploy (order matters)

1. Create the environment file from `.env.example`; set `APP_KEY`, `APP_URL`, database credentials and `IDENTITY_HMAC_SECRET`.
2. Install dependencies: `composer install --no-dev --optimize-autoloader`.
3. Run `php artisan migrate --force`.
4. Generate Passport keys with `php artisan passport:keys --force`; back them up.
4. Install the scheduler cron entry.
5. Start the queue worker under Supervisor or the host process manager.
6. Run `php artisan api:compose --check`.
7. Smoke `GET /up`, `GET /ready` and `GET /.well-known/oauth-authorization-server`.

### Every deploy

```sh
git pull <release-tag>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
# restart PHP-FPM and the queue worker
```

Rules:

- Migrations are **additive only** in staging/production.
- Never run `migrate:rollback` in staging/production.
- Restart the queue worker after deploying code that changes jobs.
- Verify `/up` and `/ready` before returning the node to traffic.

---

## 6. Release workflow

`.github/workflows/release.yml` runs on a `v*` tag or manual dispatch.

```
tag v* ──► verify (ci.yml) ──► artifacts ×4
                                  ├─ tarball + SHA-256
                                  ├─ SLSA build provenance
                                  └─ GitHub Release
                                        └─ deploy (staging, production)
                                             environment approval
                                             scripts/deploy/<env>.sh
```

Key conditions:

- `verify` reuses `ci.yml`; a tag cannot bypass the PR gate.
- `artifacts` uses `NEXT_STANDALONE=1` and uploads `release-<app>`.
- `attest` creates build provenance for the tarballs.
- `publish` creates/updates the GitHub Release with notes and checksums.
- `deploy` only runs when `vars.DEPLOY_ENABLED == 'true'` and the event is a tag push (or a manual dispatch with `dry_run=false`).
- Deploy environments are `staging` and `production`; configure required reviewers and branch restrictions in GitHub.
- The deploy job calls `scripts/deploy/${{ matrix.environment }}.sh` with `TARGET_ENV` and `RELEASE_TAG`.
- Write credentials belong only in the deploy job/environment; do not expose them to build jobs.

### Tagging

```sh
git checkout main
git pull --ff-only
git tag -a v0.1.0 -m "Navdhya v0.1.0"
git push origin v0.1.0
```

Do not tag a commit that has not passed `ci.yml` and `security.yml`. A tag is immutable; if the release is wrong, create a new tag rather than moving it.

---

## 7. Pre-deploy checklist

- [ ] The target commit is green on `ci-gate` and `security-gate`.
- [ ] The release tag exists and the GitHub Release contains all four tarballs and checksums.
- [ ] The target environment's variables/secrets are present and match the environment's provider (test vs live).
- [ ] `SESSION_SIGNING_KEY` equals the backend `IDENTITY_HMAC_SECRET`.
- [ ] `NAVDHYA_API_BASE` is an origin only (no `/api/v1`).
- [ ] Backend migrations in the release are additive and reviewed.
- [ ] A database backup/PITR point exists before a production backend deploy.
- [ ] The queue worker and scheduler will be restarted if the backend changes.
- [ ] The previous frontend release/tag is known and can be restored.
- [ ] The rollback owner and communication channel are known.
- [ ] No live MSG91 or Razorpay call is made from preview/staging.

---

## 8. Post-deploy verification

Run these against the deployed origin (replace the host):

| Check | Expected |
| --- | --- |
| `GET https://www.navdhya.com/` | 200, correct build |
| `GET https://www.navdhya.com/api/healthz` | 200, `status: ok` |
| `GET https://www.navdhya.com/api/readyz` | 200; `backend: mock` or `backend: up` |
| `GET https://app.navdhya.com/api/health` | 200, `product: customer` |
| `GET https://app.navdhya.com/api/version` | 200, build metadata |
| `GET https://partner.navdhya.com/api/health` | 200, `product: partner` |
| `GET https://api.navdhya.com/up` | 200 |
| `GET https://api.navdhya.com/ready` | 200 |
| `GET https://api.navdhya.com/.well-known/jwks.json` | 200 with a key set |
| `GET https://api.navdhya.com/.well-known/oauth-authorization-server` | 200 |

Then exercise the critical journeys:

1. Public home and a puja/discovery page load.
2. Login/OTP in the target environment (test provider unless production).
3. Customer bookings list and detail.
4. Partner task list.
5. Payment test checkout/webhook (test mode in staging).
6. Admin read-only page (if the dashboard is part of the release).

Watch logs and error rates for at least 15 minutes after production traffic returns.

---

## 9. Rollback

The short version:

- **Vercel:** promote the previous successful deployment and re-alias the domain.
- **Host frontend:** restore the previous release directory/symlink and restart the process.
- **Backend:** check out the previous tag, reinstall, re-cache config/routes and restart PHP-FPM/worker. Do not run `migrate:rollback`.
- **Feature flag:** turn the cutover flag off; web routes remain deployed during the strangler period.

Full procedure: [`ROLLBACK.md`](ROLLBACK.md).

---

## 10. Troubleshooting

| Symptom | Likely cause | Action |
| --- | --- | --- |
| Vercel build fails in seconds | Wrong Root Directory, framework preset or Node version | See [`VERCEL.md`](VERCEL.md) |
| Vercel says `No Next.js version detected` | Project root is the repository root | Set Root Directory to the app |
| Build passes locally, fails in CI/Vercel | Node 20 vs newer, case-sensitive paths, missing env | Reproduce with Node 20 and the same env |
| Frontend loads but JS/CSS 404 | Standalone artefact missing `.next/static`/`public` | Fix packaging before deploying |
| `/api/readyz` returns 503 | Backend unreachable or `/up` failing | Check backend host, DNS, TLS and firewall |
| Customer login loops or 401s | `SESSION_SIGNING_KEY` ≠ `IDENTITY_HMAC_SECRET` | Align the keys and redeploy the customer app |
| Backend `503` on JWKS | Passport keys missing/unreadable | Restore `storage/oauth-*.key`, check permissions |
| Queue jobs never run | Worker not running | Start/restart the supervisor unit |
| Scheduler never runs | Cron entry missing | Install `schedule:run` and verify `schedule:list` |
| Migration fails mid-deploy | Non-additive or incompatible migration | Stop, restore from backup if needed, fix forward with a new migration |

---

## 11. Known gaps

| Gap | Impact | Owner action |
| --- | --- | --- |
| `scripts/deploy/staging.sh` / `production.sh` missing | Release deploy cannot run | Implement the contract |
| Release tarball omits `.next/static` and `public` | Hosted app would serve broken assets | Amend `release.yml` packaging or deploy script |
| Vercel previews red | No preview signal | Capture logs and fix project settings |
| Backend CI dormant in monorepo | Backend regressions are not gated by GitHub | Move/call jobs from the root workflow |
| `e2e/run.sh` missing | Nightly e2e cannot run on Linux | Port the PowerShell runner |
| No branch protection | Green checks are not enforced | Enable when repository settings allow |

See [`../DOCUMENTATION-PLAN.md`](../DOCUMENTATION-PLAN.md) for the full register.

---

## 12. Related

- [`ENVIRONMENTS.md`](ENVIRONMENTS.md) — environment and domain matrix
- [`VERCEL.md`](VERCEL.md) — Vercel setup and logs
- [`BACKEND.md`](BACKEND.md) — Laravel deployment summary
- [`SECRETS.md`](SECRETS.md) — secret inventory and rotation
- [`MONITORING.md`](MONITORING.md) — health checks and alerts
- [`ROLLBACK.md`](ROLLBACK.md) — recovery procedures
- [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md) — deploy-script contract
- [`../CI-CD-PIPELINE-PLAN.md`](../CI-CD-PIPELINE-PLAN.md) — target pipeline design
