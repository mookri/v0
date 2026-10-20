# Partner App Deployment

**Status:** Target — host deploy not implemented
**App:** `@navdhya/partner-app`
**Package:** `apps/partner`
**Port:** 3002
**Production origin:** `partner.navdhya.com`
**Owner:** `@eng-c`

---

## 1. Overview

The partner app is the workforce application used by pandits and partners to manage tasks, assignments, and earnings. It is an authenticated app with a task-based workflow.

### 1.1 Key characteristics

- Authenticated; uses the same session model as the customer app.
- Task-based workflow: assignments, status updates, completion.
- Mock mode by default; can be wired to the Laravel backend.
- 401 and 404 envelopes are stable and tested.

### 1.2 Dependencies

- Laravel API (when wired): identity, assignments, payouts.
- Same session signing key as the customer app (if shared identity).

---

## 2. Build

```sh
NEXT_STANDALONE=1 pnpm --filter @navdhya/partner-app run build
```

### 2.1 Build-time environment

No `NEXT_PUBLIC_*` variables are required for the partner app. All configuration is server-side or runtime.

### 2.2 Build verification

```sh
pnpm --filter @navdhya/partner-app test
pnpm --filter @navdhya/partner-app run build
```

---

## 3. Environment variables

### Production (wired mode)

| Variable | Required | Secret? | Notes |
| --- | --- | --- | --- |
| `PARTNER_BACKEND` | If wired | No | Set to `laravel` (the partner client reads this, not `CUSTOMER_BACKEND`) |
| `NAVDHYA_API_BASE` | If wired | No | Origin only |
| `CUSTOMER_TENANT_ID` | If wired | No | Tenant ULID |
| `SESSION_SIGNING_KEY` | If wired | Yes | Must equal backend `IDENTITY_HMAC_SECRET` |
| `SESSION_COOKIE_SECURE` | Production | No | `true` when TLS terminates upstream |

### Preview

- Keep in mock mode.
- No production secrets.

---

## 4. Health checks

| Endpoint | Purpose | Expected |
| --- | --- | --- |
| `GET /api/health` | Liveness | 200, `{ status: 'ok', product: 'partner' }` |
| `GET /api/version` | Build metadata | 200, `{ version, commitSha, buildAt, environment }` |

---

## 5. Deployment (Vercel)

- **Project:** verify mapping.
- **Root Directory:** `apps/partner`.
- **Framework:** Next.js.
- **Node:** 20.x.
- **Domain:** `partner.navdhya.com`.

See [`../operations/VERCEL.md`](../operations/VERCEL.md).

## 6. Deployment (host — target)

Follow the contract in [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md). Partner-specific notes:

- The partner app shares the same session model as the customer app.
- If identity is shared, `SESSION_SIGNING_KEY` must match the same backend secret.
- Static assets must be present alongside `standalone/`.

---

## 7. Rollback

See [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md).

- **Vercel:** promote the previous deployment.
- **Host:** restore the previous release directory and restart.

---

## 8. Security considerations

- Authenticated app; protected application routes require a valid session. Health, version, and authentication endpoints are public.
- 401 envelope is stable and tested.
- No payment processing in the partner app (read-only earnings view).
- Contract tests enforce BFF-to-Laravel route resolution.

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
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md)
- [`../api/partner-onboarding.md`](../api/partner-onboarding.md)
- [`../api/pandit-onboarding.md`](../api/pandit-onboarding.md)
