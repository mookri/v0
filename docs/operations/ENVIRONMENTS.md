# Environments

**Status:** Current (matrix); some hosts and approvals are **Target**
**Scope:** Local, Vercel preview, staging and production.

This page is the environment contract. The deploy procedure is in [`DEPLOYMENT.md`](DEPLOYMENT.md); the Vercel-specific setup is in [`VERCEL.md`](VERCEL.md).

---

## 1. Environment matrix

| | Local | Preview | Staging | Production |
| --- | --- | --- | --- | --- |
| **Purpose** | Development and tests | Per-PR review | Release rehearsal | Live customers |
| **Frontend host** | `localhost` ports 3000–3003 | Vercel-generated preview URL | Partly documented; `app-staging.navdhya.com` for customer, others TBD | `www.navdhya.com`, `app.navdhya.com`, `partner.navdhya.com` |
| **Backend host** | `http://localhost:8000` | Mock, or a dedicated test backend | Staging API host TBD | `api.navdhya.com` (documented target) |
| **Frontend backend mode** | Mock by default; wired opt-in | Mock by default | Wired to staging backend | Wired to production backend |
| **Database** | SQLite (`database/central.sqlite`, `database/tenant_shared.sqlite`) | None by default; never production | MySQL 8 (dedicated) | MySQL 8 (central + tenant_shared) |
| **Cache/queue** | `database` defaults; `sync` in tests | Not applicable | Redis/DB per backend config | Database queue by default; Redis optional |
| **Queue worker** | Optional; run manually | Not applicable | Required | Required |
| **Scheduler** | `php artisan schedule:work` | Not applicable | Required (cron) | Required (cron) |
| **Payments** | Fake/test mode | Never live | Test mode | Live keys; approval required |
| **OTP** | Test/drill provider only | Never live MSG91 | Test identity | Live MSG91 |
| **Deploy trigger** | Manual | Pull request / push | Release tag + manual approval | Release tag + manual approval |
| **Approval** | None | PR review | GitHub `staging` environment | GitHub `production` environment |
| **Data rules** | Local fixtures | No customer data | Synthetic/test tenant only | Real data; least privilege |

> Approval rules are configured in GitHub → Settings → Environments, not in
> this repository. The release workflow references `staging` and `production`;
> verify the actual required reviewers, wait timers and branch restrictions
> before enabling `DEPLOY_ENABLED`.

---

## 2. Application ports and documented origins

| App | Package | Dev port | Production origin | Health |
| --- | --- | --- | --- | --- |
| Web | `@navdhya/web` | 3000 | `https://www.navdhya.com` | `GET /api/healthz`, `GET /api/readyz` |
| Customer | `@navdhya/customer` | 3001 | `https://app.navdhya.com` | `GET /api/health`, `GET /api/version` |
| Dashboard | `@navdhya/dashboard` | 3003 | TBD / private | Verify the deployed route |
| Partner | `@navdhya/partner-app` | 3002 | `https://partner.navdhya.com` | `GET /api/health`, `GET /api/version` |
| Backend API | Laravel | 8000 | `https://api.navdhya.com` (documented target) | `GET /up`, `GET /ready` |

The staging drill document uses the production domains as placeholders for a local drill. That is historical; do not treat it as a live staging inventory.

---

## 3. Domain status

| Domain | Intended use | Status |
| --- | --- | --- |
| `www.navdhya.com` | Public web app | Documented |
| `app.navdhya.com` | Customer workspace | Documented |
| `partner.navdhya.com` | Partner app | Documented |
| `api.navdhya.com` | Laravel API | Documented target; verify DNS/TLS before go-live |
| `app-staging.navdhya.com` | Customer staging | Documented in `apps/customer/README.md`; verify |
| `www-staging.navdhya.com` | Web staging | Not documented; TBD |
| `partner-staging.navdhya.com` | Partner staging | Not documented; TBD |
| Dashboard domain | Internal operations | Not documented; TBD |

DNS, TLS certificates and Vercel custom-domain assignments are **not stored in this repository**. Record the actual values in the deployment record before a release.

---

## 4. Configuration sources

| Environment | Frontend configuration | Backend configuration | Deploy credentials |
| --- | --- | --- | --- |
| Local | `.env.local` in each app, copied from `.env.example` | `platform-navdhya/.env`, copied from `.env.example` | None |
| Preview | Vercel Project → Settings → Environment Variables (Preview) | Not provisioned by default | None |
| Staging | Vercel/host environment + GitHub Environment `staging` | Staging host `.env` (or secret manager target) | `STAGING_SSH_KEY` (target) |
| Production | Vercel/host environment + GitHub Environment `production` | Production host `.env` (or secret manager target) | `PRODUCTION_SSH_KEY` (target) |

Never commit `.env`, `.env.local`, private keys, JWKS private material, provider tokens or customer data. See [`SECRETS.md`](SECRETS.md).

---

## 5. Cross-service invariants

These must hold in every wired environment:

1. **`NAVDHYA_API_BASE` is an origin, not an API prefix.** The customer client appends `/api/v1` itself. Use `https://api.navdhya.com`, not `https://api.navdhya.com/api/v1`.
2. **`SESSION_SIGNING_KEY` equals the backend `IDENTITY_HMAC_SECRET`.** A mismatch produces valid-looking sessions that the backend rejects.
3. **`CUSTOMER_BACKEND=laravel` is required for wired mode.** Unset means mock mode.
4. **`CUSTOMER_TENANT_ID` is present in wired mode.** Missing tenant context fails closed.
5. **Public variables are build-time.** `NEXT_PUBLIC_*` changes require a rebuild, not just a runtime restart.
6. **Secrets are server-only.** Never prefix a secret with `NEXT_PUBLIC_`.
7. **Preview and staging never point at production MySQL, MSG91, or Razorpay live keys.**

---

## 6. Promotion path

```
feature branch
   ├─ PR checks (ci.yml + security.yml)
   ├─ Vercel preview (when green/available)
   └─ review + merge to main
        ├─ staging release (tag + approval)
        │    ├─ backend migrate (additive)
        │    ├─ frontend deploy
        │    └─ smoke + soak
        └─ production release (tag + approval)
             ├─ backend code deploy (no down migrations)
             ├─ frontend deploy
             └─ smoke + monitoring window
```

Backend and frontend releases can be promoted separately, but the compatibility rule is: **deploy backend-compatible code before frontend code that depends on it**, and **remove frontend dependencies before removing backend fields**.

---

## 7. Adding a new environment

- [ ] Name it and add it to the matrix above.
- [ ] Define its domain(s), DNS owner and TLS owner.
- [ ] Define its database, bucket, queue and mail identities. Never reuse production credentials.
- [ ] Add its variables to the correct secret store; do not add values to the repository.
- [ ] Add the GitHub Environment (if deployable) with required reviewers and branch restrictions.
- [ ] Add a health-check URL and a smoke path.
- [ ] Record the data-retention and access rules.
- [ ] Update `DEPLOYMENT.md`, `VERCEL.md` (if applicable) and this page in the same change.
