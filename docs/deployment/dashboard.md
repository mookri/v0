# Dashboard App Deployment

**Status:** Target — host deploy not implemented
**App:** `@navdhya/dashboard`
**Package:** `apps/dashboard`
**Port:** 3003
**Production origin:** Private / TBD
**Owner:** `@eng-b`

---

## 1. Overview

The dashboard is the internal operations console for admin tasks: booking support states, pandit verification, refunds, incidents, and content management. It is not a public-facing app.

### 1.1 Key characteristics

- Internal use only; not exposed to the public internet without access controls.
- Admin routes require authentication; **role-based authorization is not yet implemented** (see access control gap).
- Composes existing capabilities rather than duplicating logic.
- No `packages/admin` package; admin screens use shared packages.

### 1.2 Dependencies

- Laravel API (when wired): admin endpoints, audit trail.
- Authentication with `Admin` role.

---

## 2. Build

```sh
NEXT_STANDALONE=1 pnpm --filter @navdhya/dashboard run build
```

### 2.1 Build-time environment

No externally supplied `NEXT_PUBLIC_*` variables are required for the dashboard app, but `NEXT_PUBLIC_SITE_URL` and `NEXT_PUBLIC_PRODUCT` are hardcoded in `next.config.ts` at build time. All other configuration is server-side or runtime.

### 2.2 Build verification

```sh
pnpm --filter @navdhya/dashboard test
pnpm --filter @navdhya/dashboard run build
```

---

## 3. Environment variables

### Production (wired mode)

| Variable | Required | Secret? | Notes |
| --- | --- | --- | --- |
| `DASHBOARD_API_BASE` | If wired | No | Laravel API origin only |
| `DASHBOARD_AUTH_MODE` | No | No | `direct` or `federated` |
| `DASHBOARD_AUTH_ISSUER` | Federated only | No | OIDC issuer |
| `DASHBOARD_AUTH_CLIENT_ID` | Federated only | No | OIDC client ID |
| `DASHBOARD_AUTH_CLIENT_SECRET` | Federated only | Yes | OIDC client secret |
| `DASHBOARD_AUTH_PAR` | No | No | `off` or `required` |
| `SESSION_COOKIE_SECURE` | Production | No | `true` when TLS terminates upstream |

### Access control

- **Current gap:** `RequireAuth` checks only for an authenticated session; it does not enforce an `Admin` role, and federated identities resolve to `staff`. Server-side role checks do not yet exist.
- Until role enforcement lands, keep the app restricted (IP allowlist/VPN) and do not expose it publicly.
- Admin mutations (refunds, verification decisions, suspensions) must append to an audit trail once the role model is implemented.

---

## 4. Health checks

The dashboard does not currently expose dedicated health endpoints. Verify the deployed route returns 200 and the admin login flow works.

---

## 5. Deployment (Vercel)

- **Project:** verify mapping (may not exist yet).
- **Root Directory:** `apps/dashboard`.
- **Framework:** Next.js.
- **Node:** 20.x.
- **Domain:** TBD / private (not documented in this repository).

See [`../operations/VERCEL.md`](../operations/VERCEL.md).

## 6. Deployment (host — target)

Follow the contract in [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md). Dashboard-specific notes:

- The dashboard should not be publicly accessible without authentication.
- Consider IP allowlisting or VPN for the admin surface.
- Static assets must be present alongside `standalone/`.

---

## 7. Rollback

See [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md).

- **Vercel:** promote the previous deployment.
- **Host:** restore the previous release directory and restart.

---

## 8. Security considerations

- Admin surface is high-risk; changes require explicit security review.
- Audit trail is mandatory for admin mutations.
- No `packages/admin` package; admin screens compose existing capabilities.
- Admin routes are excluded from the strangler cutover.

---

## 9. Known issues

- No dedicated health endpoint.
- Production domain not documented.
- Vercel project may not exist yet.
- Host deploy not implemented.

---

## 10. Related

- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md)
- [`../operations/ENVIRONMENTS.md`](../operations/ENVIRONMENTS.md)
- [`../operations/admin-operations.md`](../operations/admin-operations.md)
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md)
- [`../../docs/architecture/route-ownership.yaml`](../../docs/architecture/route-ownership.yaml)
