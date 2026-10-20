# Customer App Deployment

**Status:** Target — host deploy not implemented
**App:** `@navdhya/customer`
**Package:** `apps/customer`
**Port:** 3001
**Production origin:** `app.navdhya.com`
**Owner:** `@eng-a`

---

## 1. Overview

The customer app is the authenticated workspace where customers manage bookings, payments, and account settings. It is the most security-sensitive frontend and the only app that wires to the Laravel backend in production.

### 1.1 Key characteristics

- Authenticated; session cookie (`HttpOnly`, `SameSite=Lax`).
- Wired mode (`CUSTOMER_BACKEND=laravel`) connects to the Laravel API.
- Mock mode is the default; no backend calls in mock mode.
- 54 pages (as of 2026-10 review).
- BFF routes under `/api/*` proxy to Laravel routes.

### 1.2 Dependencies

- Laravel API (when wired): identity, bookings, payments.
- `IDENTITY_HMAC_SECRET` must match `SESSION_SIGNING_KEY`.
- Tenant context (`CUSTOMER_TENANT_ID`) required in wired mode.

---

## 2. Build

```sh
NEXT_STANDALONE=1 pnpm --filter @navdhya/customer run build
```

### 2.1 Build-time environment

No `NEXT_PUBLIC_*` variables are required for the customer app. All configuration is server-side or runtime.

### 2.2 Build verification

```sh
pnpm --filter @navdhya/customer test
pnpm --filter @navdhya/customer run build
```

---

## 3. Environment variables

### Production (wired mode)

| Variable | Required | Secret? | Notes |
| --- | --- | --- | --- |
| `CUSTOMER_BACKEND` | Yes | No | Set to `laravel` |
| `NAVDHYA_API_BASE` | Yes | No | Origin only, no `/api/v1` |
| `CUSTOMER_TENANT_ID` | Yes | No | Tenant ULID |
| `SESSION_SIGNING_KEY` | Yes | Yes | Must equal backend `IDENTITY_HMAC_SECRET` |
| `CUSTOMER_AUTH_MODE` | No | No | `direct` (default) or `federated` |
| `CUSTOMER_AUTH_ISSUER` | Federated only | No | OIDC issuer |
| `CUSTOMER_AUTH_CLIENT_ID` | Federated only | No | OIDC client ID |
| `CUSTOMER_AUTH_CLIENT_SECRET` | Federated only | Yes | OIDC client secret |
| `CUSTOMER_AUTH_PAR` | No | No | `off` or `required` |
| `CUSTOMER_DPOP_KEY_FILE` | Federated DPoP | Yes (file) | Private key file path |
| `SESSION_COOKIE_SECURE` | Production | No | `true` when TLS terminates upstream |

### Preview

- Keep in mock mode (unset `CUSTOMER_BACKEND`).
- No production secrets.
- No wired backend.

---

## 4. Health checks

| Endpoint | Purpose | Expected |
| --- | --- | --- |
| `GET /api/health` | Liveness | 200, `{ status: 'ok', product: 'customer' }` |
| `GET /api/version` | Build metadata | 200, `{ version, commitSha, buildAt, environment }` |

---

## 5. Deployment (Vercel)

- **Project:** verify mapping (one of `navdhya` / `navdhya-0`).
- **Root Directory:** `apps/customer`.
- **Framework:** Next.js.
- **Node:** 20.x.
- **Domain:** `app.navdhya.com`.

See [`../operations/VERCEL.md`](../operations/VERCEL.md).

## 6. Deployment (host — target)

Follow the contract in [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md). Customer-specific notes:

- `SESSION_SIGNING_KEY` must be set before the app starts; a mismatch produces valid-looking sessions that the backend rejects.
- `NAVDHYA_API_BASE` must be an origin only.
- The BFF appends `/api/v1` to all backend calls.
- Static assets must be present alongside `standalone/`.

---

## 7. Rollback

See [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md).

- **Vercel:** promote the previous deployment.
- **Host:** restore the previous release directory and restart.
- **Key rotation:** if `SESSION_SIGNING_KEY` was rotated, all sessions invalidate; users must re-login.

---

## 8. Security considerations

- Session cookie is `HttpOnly`, `SameSite=Lax`; no CSRF token (double-submit) is issued.
- If cookie policy is relaxed for cross-site embedding, add a CSRF token first.
- `DEV_CUSTOMER_SESSION` must never be set in production.
- Contract tests enforce that BFF paths resolve to real Laravel routes (allow-list stays empty).

---

## 9. Known issues

- Vercel preview checks are currently failing.
- Host deploy not implemented.
- Standalone artefact omits static assets (P0 gap).

---

## 10. Related

- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md)
- [`../operations/ENVIRONMENTS.md`](../operations/ENVIRONMENTS.md)
- [`../operations/VERCEL.md`](../operations/VERCEL.md)
- [`../operations/SECRETS.md`](../operations/SECRETS.md)
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md)
- [`../../auth/laravel-contract.md`](../../auth/laravel-contract.md)
