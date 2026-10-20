# Environment Variables

**Status:** Current
**Scope:** Frontend, backend, CI and deployment variables.

This is a reference for **names and intent**, not a place to store values. The source files are:

- `apps/customer/.env.example`
- `apps/web/.env.example`
- `platform-navdhya/.env.example`
- `.github/workflows/*.yml` for CI variables

If a value is missing from an example file but referenced by code, treat that as a documentation gap and fix the example.

---

## 1. How variables are loaded

| Context | Source | Rebuild needed? |
| --- | --- | --- |
| Local Next.js | `apps/<app>/.env.local` | Restart dev server; `NEXT_PUBLIC_*` needs rebuild |
| Vercel | Project → Settings → Environment Variables | Redeploy for `NEXT_PUBLIC_*`; runtime vars can be picked up on a new deployment |
| Local Laravel | `platform-navdhya/.env` | `php artisan config:clear` or re-cache |
| Hosted Laravel | Host `.env` or secret manager | Restart/re-cache after change |
| CI | Workflow `env`, repository variables (`vars.*`), environment/repository secrets | New workflow run |

`NEXT_PUBLIC_*` values are embedded at build time. Everything else is read at runtime on the server. A secret must never be prefixed with `NEXT_PUBLIC_`.

---

## 2. Customer app

Source: `apps/customer/.env.example`.

| Variable | Required | Public? | Purpose |
| --- | --- | --- | --- |
| `CUSTOMER_BACKEND` | Wired mode | No | Set to `laravel` to enable the wired BFF; unset = mock mode |
| `NAVDHYA_API_BASE` | Wired mode | No | Laravel **origin only**, no trailing slash and no `/api/v1` (the BFF appends it) |
| `CUSTOMER_TENANT_ID` | Wired mode | No | Tenant ULID for tenant-scoped calls |
| `SESSION_SIGNING_KEY` | Wired production | **Secret** | Signs the customer session cookie; must equal backend `IDENTITY_HMAC_SECRET` |
| `CUSTOMER_AUTH_MODE` | No | No | `direct` (default) or `federated` |
| `CUSTOMER_AUTH_ISSUER` | Federated | No | OIDC issuer |
| `CUSTOMER_AUTH_CLIENT_ID` | Federated | No | OIDC client ID |
| `CUSTOMER_AUTH_CLIENT_SECRET` | Federated | **Secret** | OIDC client secret |
| `CUSTOMER_AUTH_PAR` | No | No | RFC 9126 pushed authorization requests: `off` or `required` |
| `CUSTOMER_DPOP_KEY_FILE` | Federated DPoP | **Secret file** | Private key file for DPoP token binding |
| `SESSION_COOKIE_SECURE` | Production | No | Force `Secure` cookies when TLS terminates upstream |
| `DEV_CUSTOMER_SESSION` | Local only | No | Dev-only mock session; **never** set in production |

Example wired local values:

```dotenv
CUSTOMER_BACKEND=laravel
NAVDHYA_API_BASE=http://localhost:8000
CUSTOMER_TENANT_ID=<tenant-ulid>
SESSION_SIGNING_KEY=<same as backend IDENTITY_HMAC_SECRET>
CUSTOMER_AUTH_MODE=direct
```

---

## 3. Web app — Signals and analytics

Source: `apps/web/.env.example`.

| Variable | Public? | Purpose |
| --- | --- | --- |
| `NEXT_PUBLIC_GA4_MEASUREMENT_ID_WEB` | Yes | GA4 measurement ID for web |
| `NEXT_PUBLIC_GA4_MEASUREMENT_ID_CUSTOMER` | Yes | GA4 measurement ID for customer |
| `NEXT_PUBLIC_GA4_MEASUREMENT_ID_PARTNER` | Yes | GA4 measurement ID for partner |
| `NEXT_PUBLIC_GA4_MEASUREMENT_ID_DASHBOARD` | Yes | GA4 measurement ID for dashboard |
| `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB` | Yes | GTM web container; marketing surfaces only |
| `NEXT_PUBLIC_SIGNALS_COLLECTOR_URL` | Yes | First-party collector path, default `/api/ae/v1/batch` |
| `NEXT_PUBLIC_SIGNALS_ENABLED` | Yes | Global Signals kill switch; default `false` |
| `SIGNALS_HMAC_SECRET` | **No — secret** | Collector → ingestion service authentication |
| `SIGNALS_CONSENT_TOKEN_SECRET` | **No — secret** | Consent-token signing |
| `SIGNALS_INGEST_URL` | No | Ingestion endpoint (internal configuration) |

### Ops-only privacy erasure

Used only by `npx tsx tooling/scripts/privacy-erasure.ts --live`; the default is a dry run.

| Variable | Purpose |
| --- | --- |
| `SIGNALS_BQ_PROJECT` | GCP project hosting `signals_raw` |
| `SIGNALS_BQ_MAX_BYTES_BILLED` | Erasure cost cap in bytes (default 10 GiB) |
| `GA4_PROPERTY_ID` | GA4 property for `properties.deleteUserdata` |
| `GA4_ACCESS_TOKEN` | OAuth token with GA4 edit permission |
| `DATABASE_URL` | Postgres URL for identity mapping/privacy tables |
| `SIGNALS_CACHE_PURGE_URL` | Cached-projection purge endpoint |
| `SIGNALS_AUDIENCE_URL` | FCM/audience removal endpoint |
| `SIGNALS_AUDIENCE_TOKEN` | Bearer token for the audience endpoint |

Do not run the live erasure command without the owner's approval.

---

## 4. Backend (Laravel)

Source: `platform-navdhya/.env.example`.

### Application

| Variable | Purpose |
| --- | --- |
| `APP_NAME` | Display name |
| `APP_ENV` | `local`, `testing`, `staging`, `production` |
| `APP_KEY` | Encryption/signing key; generate once per environment |
| `APP_DEBUG` | Must be `false` in production |
| `APP_TIMEZONE` | Default `UTC` |
| `APP_URL` | Canonical origin used by discovery, JWKS and redirect validation |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Localization |
| `APP_MAINTENANCE_DRIVER` | Maintenance-mode storage |
| `BCRYPT_ROUNDS` | Password hashing cost |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`, `LOG_DEPRECATIONS_CHANNEL` | Logging |
| `IDENTITY_HMAC_SECRET` | Shared session/token signing secret; must equal `SESSION_SIGNING_KEY` in the customer app |

### Database

| Variable | Purpose |
| --- | --- |
| `DB_CONNECTION` | Default connection; `central` in this app |
| `CENTRAL_DB_DRIVER` | `sqlite` locally, `mysql` in production |
| `CENTRAL_DB_DATABASE` | `database/central.sqlite` or the MySQL database name |
| `CENTRAL_DB_HOST`, `CENTRAL_DB_PORT` | MySQL host/port |
| `CENTRAL_DB_USERNAME`, `CENTRAL_DB_PASSWORD` | Dedicated credentials |
| `TENANT_DB_DRIVER` | `sqlite` locally, `mysql` in production |
| `TENANT_DB_DATABASE` | `database/tenant_shared.sqlite` or the MySQL database name |
| `TENANT_DB_HOST`, `TENANT_DB_PORT` | MySQL host/port |
| `TENANT_DB_USERNAME`, `TENANT_DB_PASSWORD` | Dedicated credentials |

Production uses two MySQL 8 databases: central (identity, authz, registry) and tenant-shared. Dedicated tenant databases are a later topology; do not assume they exist.

### Sessions, cache, queue, files

| Variable | Purpose |
| --- | --- |
| `SESSION_DRIVER` | `database` by default |
| `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_DOMAIN` | Cookie/session behavior |
| `BROADCAST_CONNECTION` | `log` by default |
| `FILESYSTEM_DISK` | `local` by default; S3-compatible optional |
| `QUEUE_CONNECTION` | `database` by default; requires a worker |
| `CACHE_STORE`, `CACHE_PREFIX` | Cache backend and namespace |
| `MEMCACHED_HOST` | Only if Memcached is used |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` | Redis connection (optional) |

### Mail

| Variable | Purpose |
| --- | --- |
| `MAIL_MAILER` | `log` locally; real provider in production |
| `MAIL_HOST`, `MAIL_PORT` | SMTP endpoint |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP credentials |
| `MAIL_ENCRYPTION` | TLS/SSL setting |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Sender identity |

### Object storage

| Variable | Purpose |
| --- | --- |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | S3-compatible credentials; prefer instance roles where available |
| `AWS_DEFAULT_REGION`, `AWS_BUCKET` | Bucket target |
| `AWS_USE_PATH_STYLE_ENDPOINT` | Compatibility for non-AWS S3 |

### OTP / MSG91

| Variable | Public? | Purpose |
| --- | --- | --- |
| `MSG91_AUTH_KEY` | **No — secret** | Server-side widget token verification; boot fails closed without it |
| `MSG91_WIDGET_ID` | Yes (widget) | Public widget ID |
| `MSG91_WIDGET_TOKEN_AUTH` | Yes (widget) | Public widget token auth |
| `MSG91_VERIFY_BASE_URL` | No | Verify endpoint; override only for e2e stubs |
| `MSG91_RESEND_CHANNELS` | No | Resend channel rotation (SMS/voice/email/WhatsApp) |

### Payments

| Variable | Public? | Purpose |
| --- | --- | --- |
| `PAYMENTS_DRIVER` | No | `razorpay` (default) or `fake` for offline tests |
| `PAYMENTS_TEST_MODE` | No | `true` by default; live requires approval |
| `RAZORPAY_KEY_ID` | No | API key ID |
| `RAZORPAY_KEY_SECRET` | **No — secret** | API secret |
| `RAZORPAY_WEBHOOK_SECRET` | **No — secret** | Webhook signature verification |
| `RAZORPAY_API_BASE` | No | API base; override only for tests |

### Passport keys

Passport RS256 keys are files under `storage/oauth-*.key`, not environment values. Back them up with the environment secrets and restrict permissions. Missing keys surface as a 503 on `/.well-known/jwks.json`.

---

## 5. CI variables and secrets

### Repository variables

| Name | Purpose | Current |
| --- | --- | --- |
| `CI_STRICT` | Promote selected soft gates | Unset/soft |
| `CI_ADVANCED_SECURITY` | Enable GHAS-only jobs | Unset; free substitutes run |
| `DEPLOY_ENABLED` | Enable release deploy job | Unset; deploy disabled |
| `E2E_ENABLED` | Enable nightly e2e | Unset; `e2e/run.sh` missing |
| `PERF_ENABLED` | Enable nightly perf | Unset |
| `MUTATION_ENABLED` | Enable nightly mutation | Unset; no Stryker config |

### Secrets

| Name | Purpose | Current |
| --- | --- | --- |
| `GITHUB_TOKEN` | Workflow API access | Automatic |
| `GITLEAKS_LICENSE` | gitleaks action for organisation-owned repos | Optional; not needed for a personal repo |
| `STAGING_SSH_KEY` | Staging host deploy | **Target**; not wired into `release.yml` yet |
| `PRODUCTION_SSH_KEY` | Production host deploy | **Target**; not wired yet |
| `TURBO_TOKEN` / `TURBO_TEAM` | Optional remote cache | Not referenced by current workflows |

The release deploy job currently passes only `TARGET_ENV` and `RELEASE_TAG` to the deploy script. See [`../operations/SECRETS.md`](../operations/SECRETS.md).

### Build metadata

The customer/partner `/api/version` route reads:

| Variable | Purpose |
| --- | --- |
| `APP_VERSION` | Release version (`dev` if unset) |
| `GIT_SHA` | Commit SHA (`dev` if unset) |
| `BUILD_AT` | Build timestamp (`unknown` if unset) |
| `APP_ENV` | Environment label |

Set these in the deploy pipeline if you want meaningful version output. They are public-safe; never put a secret in them.

---

## 6. Invariants

1. `NAVDHYA_API_BASE` = origin only. The BFF appends `/api/v1`.
2. `SESSION_SIGNING_KEY` = `IDENTITY_HMAC_SECRET`.
3. `CUSTOMER_BACKEND=laravel` is required for wired mode.
4. `CUSTOMER_TENANT_ID` is required for tenant-scoped calls.
5. `NEXT_PUBLIC_*` is public and build-time.
6. `APP_DEBUG=false` and `APP_ENV=production` in production.
7. `MSG91_AUTH_KEY`, payment secrets and SMTP credentials are server-only.
8. Preview/staging never use production credentials or data.
9. `DEV_CUSTOMER_SESSION` is never set in production.
10. `PAYMENTS_TEST_MODE=true` outside production unless a live-payment approval explicitly says otherwise.

---

## 7. Rotation

See [`../operations/SECRETS.md`](../operations/SECRETS.md) for storage, rotation and leak response. In particular:

- Rotate `SESSION_SIGNING_KEY` and `IDENTITY_HMAC_SECRET` together; all sessions invalidate.
- Rotate Passport keys with a JWKS overlap.
- Rotating `APP_KEY` requires a data/session migration plan.
- Update Vercel/host variables and redeploy after rotation.

---

## 8. Related

- [`SETUP.md`](SETUP.md) — local values and commands
- [`../operations/ENVIRONMENTS.md`](../operations/ENVIRONMENTS.md) — per-environment matrix
- [`../operations/SECRETS.md`](../operations/SECRETS.md) — secret handling
- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) — release variables
- [`../operations/BACKEND.md`](../operations/BACKEND.md) — backend configuration
