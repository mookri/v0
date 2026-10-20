# Secrets

**Status:** Current for storage rules and inventory; production secret manager is **Target**
**Scope:** Frontend, backend, CI and operational secrets.

This page defines where secrets live, which values are public by design, how to rotate them, and what to do after a leak.

> A secret is any value that grants access, signs a session, authenticates to a provider, or decrypts data. If in doubt, treat it as a secret and keep it server-side.

---

## 1. Rules

1. **Never commit a secret** — not in `.env`, source, tests, docs, screenshots, issue bodies or build logs.
2. **Never use `NEXT_PUBLIC_*` for a secret.** Public variables are embedded in the browser bundle.
3. **One environment, one credential.** Do not reuse production keys in staging or preview.
4. **Least privilege.** Use a dedicated database user, bucket, provider key and SSH key per environment.
5. **Rotate on a schedule and after any suspected exposure.** Rotation means "new credential active, old credential revoked", not just "value changed locally".
6. **Record ownership.** Every production secret has a named owner and a last-rotated date outside this repository.
7. **Fail closed.** Missing OTP/payment/session secrets must stop the operation, not silently downgrade to a test path.

---

## 2. Where secrets live

| Environment | Storage |
| --- | --- |
| Local | `.env.local` in each app; `platform-navdhya/.env`. Both are gitignored. |
| Preview | Vercel project environment variables (Preview scope). Prefer mock mode and no real credentials. |
| Staging | GitHub Environment `staging` secrets and the staging host environment/secret manager |
| Production | GitHub Environment `production` secrets and the production host environment/secret manager |
| CI | GitHub Actions secrets; `GITHUB_TOKEN` is automatic and scoped per job |
| Backend host | `.env` readable only by the service user, or the target secret manager |

The repository's `.gitignore` ignores `.env*` except `.env.example`. Example files contain names and placeholders only.

### Current CI wiring

`.github/workflows/security.yml` references only:

- `secrets.GITHUB_TOKEN` — automatic.
- `secrets.GITLEAKS_LICENSE` — optional, only needed for an organisation-owned repository.

The release deploy job currently passes only `TARGET_ENV` and `RELEASE_TAG` to `scripts/deploy/<env>.sh`. It does **not** export `STAGING_SSH_KEY` or `PRODUCTION_SSH_KEY`. The deploy-script implementation must wire those environment secrets (or use a short-lived SSH agent) before `DEPLOY_ENABLED` is set to `true`.

---

## 3. Secret inventory

### 3.1 Customer app (server-only)

| Name | Purpose | Notes |
| --- | --- | --- |
| `SESSION_SIGNING_KEY` | Signs the customer session cookie | Must equal the backend `IDENTITY_HMAC_SECRET`; generate with `openssl rand -base64 32` |
| `CUSTOMER_AUTH_CLIENT_SECRET` | OIDC client secret when federation is enabled | Only for `CUSTOMER_AUTH_MODE=federated` |
| `CUSTOMER_DPOP_KEY_FILE` | Private key file for DPoP-bound tokens | File path, not the key itself; protect the file |
| `CUSTOMER_TENANT_ID` | Tenant context | Not a cryptographic secret, but environment-specific and not public |

`CUSTOMER_BACKEND`, `NAVDHYA_API_BASE`, `CUSTOMER_AUTH_MODE`, `CUSTOMER_AUTH_ISSUER`, `CUSTOMER_AUTH_CLIENT_ID`, `CUSTOMER_AUTH_PAR` and `SESSION_COOKIE_SECURE` are configuration, not secrets.

### 3.2 Web app (server-only)

| Name | Purpose |
| --- | --- |
| `SIGNALS_HMAC_SECRET` | Collector → ingestion service authentication |
| `SIGNALS_CONSENT_TOKEN_SECRET` | Per-batch consent-token signing |
| `SIGNALS_INGEST_URL` | Ingestion endpoint; treat as internal configuration |
| `GA4_ACCESS_TOKEN` | Ops-only privacy erasure token (minted externally) |
| `DATABASE_URL` | Ops-only Postgres URL for privacy erasure |
| `SIGNALS_AUDIENCE_TOKEN` | Bearer token for audience removal (optional) |

Public-by-design values (not secrets): `NEXT_PUBLIC_GA4_MEASUREMENT_ID_*`, `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB`, `NEXT_PUBLIC_SIGNALS_COLLECTOR_URL`, `NEXT_PUBLIC_SIGNALS_ENABLED`.

### 3.3 Backend (server-only)

| Name | Purpose | Notes |
| --- | --- | --- |
| `APP_KEY` | Laravel encryption/signing key | Generate once per environment; back it up |
| `CENTRAL_DB_PASSWORD` | Central MySQL user password | Dedicated user, least privilege |
| `TENANT_DB_PASSWORD` | Tenant-shared MySQL user password | May be the same server, different database/user |
| `REDIS_PASSWORD` | Redis auth if enabled | `null` only for local unauthenticated Redis |
| `MAIL_PASSWORD` | SMTP credential | Use a provider-specific app password |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | S3-compatible storage | Prefer an instance/task role over static keys |
| `MSG91_AUTH_KEY` | Server-side widget token verification | Boot fails closed without it |
| `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` | Payment API | Live values only in production |
| `RAZORPAY_WEBHOOK_SECRET` | Webhook signature verification | Required for the webhook route |
| `IDENTITY_HMAC_SECRET` | Backend session/token signing | Must equal the customer `SESSION_SIGNING_KEY` |
| `storage/oauth-*.key` | Passport RS256 key pair | Files, not env values; back up and restrict permissions |

`MSG91_WIDGET_ID` and `MSG91_WIDGET_TOKEN_AUTH` are public-by-design widget credentials served to the browser; they are not a substitute for `MSG91_AUTH_KEY`.

### 3.4 CI and release

| Name | Purpose | Required |
| --- | --- | --- |
| `GITHUB_TOKEN` | Workflow API access | Automatic |
| `GITLEAKS_LICENSE` | gitleaks action for organisation-owned repos | Only for org repos |
| `STAGING_SSH_KEY` | Staging host deploy | **Target**; not yet wired |
| `PRODUCTION_SSH_KEY` | Production host deploy | **Target**; not yet wired |
| `TURBO_TOKEN` / `TURBO_TEAM` | Optional remote cache | Optional |

Repository variables (`CI_STRICT`, `CI_ADVANCED_SECURITY`, `DEPLOY_ENABLED`, `E2E_ENABLED`, `PERF_ENABLED`, `MUTATION_ENABLED`) are switches, not secrets.

---

## 4. Generation and storage checklist

- [ ] Generate with a cryptographically secure source (`openssl rand`, provider console, password manager).
- [ ] Store the value in the target environment's secret store, not in a file tracked by Git.
- [ ] Set the value in every place that needs it, including the paired service.
- [ ] Deploy or restart the process that reads it.
- [ ] Verify the dependent flow (login, payment, mail, queue).
- [ ] Record owner, environment, purpose and rotation date in the team's secret register.
- [ ] Revoke the previous value.
- [ ] Confirm no copy remains in shell history, CI logs or chat.

---

## 5. Rotation

### General procedure

1. **Announce and scope.** Identify every consumer (frontend, backend, worker, CI, provider webhook).
2. **Create the replacement** while the old credential is still valid.
3. **Deploy the new value** to all consumers.
4. **Verify** the dependent flow end to end.
5. **Revoke the old credential** at the provider and in the secret store.
6. **Record** the rotation date and any incident link.
7. **Watch** for 401/403/5xx spikes for at least one traffic window.

### Special cases

| Secret | Extra care |
| --- | --- |
| `APP_KEY` | Rotating it breaks encrypted columns and existing sessions. Plan a data migration/re-encryption, not a casual rotation. |
| `IDENTITY_HMAC_SECRET` + `SESSION_SIGNING_KEY` | Rotate both together. All existing customer sessions become invalid; partner actor tokens signed with the same secret also invalidate, so plan for partner re-authentication. Communicate a re-login. |
| Passport keys | Publish the new key in JWKS before removing the old one; keep the old key until issued tokens expire. Back up the key pair. |
| Database password | Create the new credential, deploy it, verify connections, then revoke the old one. |
| SSH deploy key | Add the new public key to the host, deploy, then remove the old key. |
| MSG91 / Razorpay | Rotate in the provider console, update the server, run a test-mode verification, then revoke the old key. |
| Webhook secret | Update the provider and the backend in a coordinated window; old signatures fail closed. |

---

## 6. Detection and response

### Local and CI

- The pre-commit hook runs gitleaks and detect-secrets when those binaries are installed.
- `pnpm secrets:scan` scans staged changes; `pnpm secrets:scan:all` scans history.
- CI runs gitleaks on pull requests and a full-history scan on a schedule.
- The hook is best-effort: a missing binary must not be treated as a clean scan. CI is the enforceable gate.
- GitHub native secret scanning and push protection depend on the repository plan; they are not assumed here.

### If a secret is exposed

1. **Revoke or rotate first.** Do not wait for a history rewrite; the credential is already public.
2. **Assess scope.** Which environment, which permissions, what data could be reached, and for how long?
3. **Contain.** Disable the provider key, invalidate sessions/tokens, rotate the paired secret.
4. **Preserve evidence.** Keep the commit hash, log excerpt and timeline; redact the secret itself.
5. **Fix the source** (code, workflow, fixture or docs) and add a regression check.
6. **Clean history only with approval.** A history rewrite changes every downstream commit and must be coordinated; rotation is mandatory regardless.
7. **Record an incident** and update this page if the inventory was incomplete.

Do not paste a live secret into an issue, PR, chat or postmortem. Use a redacted fingerprint and the secret's name.

---

## 7. Known gaps

- Production secret storage is not finalized: the backend runbook currently uses a host `.env`; `PROD-ENV.md` describes a Vault-based target.
- The release deploy job does not yet pass SSH credentials to the deploy script.
- There is no automated secret-rotation reminder; ownership is manual.
- GitHub native secret scanning is not assumed on the current private/Free repository.
- `GITLEAKS_LICENSE` is only needed for organisation-owned repositories; keep it unset for a personal repository.

See [`../DOCUMENTATION-PLAN.md`](../DOCUMENTATION-PLAN.md) for owners and priorities.

---

## 8. Related

- [`SECURITY.md`](../../SECURITY.md) — reporting policy
- [`../SECURITY-AND-THREAT-MODEL.md`](../SECURITY-AND-THREAT-MODEL.md) — threat model
- [`ENVIRONMENTS.md`](ENVIRONMENTS.md) — environment matrix
- [`DEPLOYMENT.md`](DEPLOYMENT.md) — release and deploy path
- [`BACKEND.md`](BACKEND.md) — backend configuration
- [`../development/ENVIRONMENT-VARIABLES.md`](../development/ENVIRONMENT-VARIABLES.md) — variable reference
