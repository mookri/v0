# Per-Application Deployment

**Status:** Current index; individual guides are **Target** where host deploy is not implemented
**Scope:** Deployment procedures for each application in the Navdhya monorepo.

See [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) for the canonical end-to-end deployment guide. This directory contains the per-app specifics: build, environment, health checks, and rollback.

---

## Application guides

| App | Guide | Tier | Status |
| --- | --- | --- | --- |
| Web | [`web.md`](web.md) | Public, high traffic | Target |
| Customer | [`customer.md`](customer.md) | Authenticated, high sensitivity | Target |
| Partner | [`partner.md`](partner.md) | Authenticated, workforce | Target |
| Dashboard | [`dashboard.md`](dashboard.md) | Internal, admin | Target |
| Backend API | [`backend.md`](backend.md) | Core, high sensitivity | Current (host-based) |

---

## Common deployment contract (Next.js apps only)

The four Next.js apps follow this contract (see [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) and [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md)). The Laravel backend is deployed separately on the host and does not produce a Next.js standalone bundle; see [`backend.md`](backend.md).

1. Build with `NEXT_STANDALONE=1`.
2. Package standalone output (plus `.next/static` and `public` — currently missing; see P0 gap).
3. Verify checksums.
4. Unpack to a versioned release directory.
5. Switch the `current` symlink.
6. Restart the process.
7. Smoke health endpoints.
8. On failure, restore the previous release.

---

## Build commands

```sh
NEXT_STANDALONE=1 pnpm --filter @navdhya/web run build
NEXT_STANDALONE=1 pnpm --filter @navdhya/customer run build
NEXT_STANDALONE=1 pnpm --filter @navdhya/partner-app run build
NEXT_STANDALONE=1 pnpm --filter @navdhya/dashboard run build
```

PowerShell: set `$env:NEXT_STANDALONE='1'` before each build.

---

## Health endpoints

| App | Liveness | Readiness | Version |
| --- | --- | --- | --- |
| Web | `GET /api/healthz` | `GET /api/readyz` | — |
| Customer | `GET /api/health` | — | `GET /api/version` |
| Partner | `GET /api/health` | — | `GET /api/version` |
| Dashboard | Verify deployed route | — | — |
| Backend | `GET /up` | `GET /ready` | — |

---

## Rollback

See [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md) for the full procedure. The short version: promote the previous deployment (Vercel) or restore the previous release directory (host).

---

## Related

- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) — canonical deploy guide
- [`../operations/ENVIRONMENTS.md`](../operations/ENVIRONMENTS.md) — environment matrix
- [`../operations/VERCEL.md`](../operations/VERCEL.md) — Vercel setup
- [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md) — deploy script contract
