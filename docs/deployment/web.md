# Web App Deployment

**Status:** Target — host deploy not implemented
**App:** `@navdhya/web`
**Package:** `apps/web`
**Port:** 3000
**Production origin:** `www.navdhya.com`
**Owner:** `@eng-b`

---

## 1. Overview

The web app is the public-facing surface: discovery, puja pages, booking flows, and account surfaces. It is the highest-traffic app and the entry point for most users.

### 1.1 Key characteristics

- Public, unauthenticated by default (auth is optional for booking).
- Uses `NEXT_PUBLIC_*` analytics and Signals variables.
- Has its own health endpoints: `/api/healthz` (liveness) and `/api/readyz` (readiness).
- Standalone output is required for host deploy.
- SEO-critical: 1,363+ static pages generated at build time.

### 1.2 Dependencies

- None at runtime (mock mode by default).
- Optional: backend API when `CUSTOMER_BACKEND=laravel` is set (rare for web).
- Optional: Signals ingestion endpoint for analytics.

---

## 2. Build

```sh
NEXT_STANDALONE=1 pnpm --filter @navdhya/web run build
```

Outputs:

```
apps/web/.next/standalone/   # server bundle
apps/web/.next/static/       # client chunks, CSS, fonts
apps/web/public/             # static public files
apps/web/.next/BUILD_ID
```

### 2.1 Build-time environment

| Variable | Required | Notes |
| --- | --- | --- |
| `NEXT_PUBLIC_GA4_MEASUREMENT_ID_WEB` | No | GA4 measurement ID |
| `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB` | No | GTM container |
| `NEXT_PUBLIC_SIGNALS_COLLECTOR_URL` | No | Default `/api/ae/v1/batch` |
| `NEXT_PUBLIC_SIGNALS_ENABLED` | No | Default `false` |
| `NEXT_PUBLIC_SITE_URL` | Hardcoded | Set in `next.config.ts` to `https://www.navdhya.com` |

Server-only variables (`SIGNALS_HMAC_SECRET`, `SIGNALS_CONSENT_TOKEN_SECRET`, etc.) are read at runtime.

### 2.2 Build verification

```sh
pnpm --filter @navdhya/web test
pnpm --filter @navdhya/web run build
# Verify .next/standalone/server.js exists
# Verify .next/BUILD_ID exists
```

---

## 3. Environment variables

### Production (Vercel or host)

| Variable | Required | Secret? | Notes |
| --- | --- | --- | --- |
| `NEXT_PUBLIC_GA4_MEASUREMENT_ID_WEB` | No | No | |
| `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB` | No | No | |
| `NEXT_PUBLIC_SIGNALS_ENABLED` | No | No | Keep `false` until shadow validation |
| `SIGNALS_HMAC_SECRET` | If Signals enabled | Yes | |
| `SIGNALS_CONSENT_TOKEN_SECRET` | If Signals enabled | Yes | |
| `SIGNALS_INGEST_URL` | If Signals enabled | No | Internal config |
| `GA4_ACCESS_TOKEN` | Ops only | Yes | Privacy erasure |
| `DATABASE_URL` | Ops only | Yes | Privacy erasure |

### Preview

- Keep in mock mode (no backend).
- No production secrets.
- Analytics can be enabled for testing.

---

## 4. Health checks

| Endpoint | Purpose | Expected |
| --- | --- | --- |
| `GET /api/healthz` | Liveness | 200, `{ status: 'ok', service: 'web-bff' }` |
| `GET /api/readyz` | Readiness | 200; `backend: mock` or `backend: up` |

`/api/healthz` is a static truth — the process answers. `/api/readyz` probes the backend `/up` with a 2-second budget when wired.

---

## 5. Deployment (Vercel)

- **Project:** `navdhya` (verify mapping).
- **Root Directory:** `apps/web`.
- **Framework:** Next.js.
- **Node:** 20.x.
- **Domain:** `www.navdhya.com`.

See [`../operations/VERCEL.md`](../operations/VERCEL.md).

## 6. Deployment (host — target)

Follow the contract in [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md). Web-specific notes:

- Static assets (`.next/static`, `public`) must be present alongside `standalone/`.
- The process manager must set `PORT` and `HOSTNAME`.
- `NEXT_PUBLIC_*` values are baked at build time; changing them requires a rebuild.

---

## 7. Rollback

See [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md).

- **Vercel:** promote the previous deployment.
- **Host:** restore the previous release directory and restart.

---

## 8. Monitoring

- `/api/healthz` and `/api/readyz` checked every 30 seconds (Route 53).
- Watch for 5xx rate, static asset 404s, and TTFB.
- Web has 1,363+ static pages; monitor build size and page count.

---

## 9. Known issues

- Vercel preview checks are currently failing (cause unknown; needs log access).
- Host deploy not implemented.
- Standalone artefact omits static assets (P0 gap).

---

## 10. Related

- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md)
- [`../operations/ENVIRONMENTS.md`](../operations/ENVIRONMENTS.md)
- [`../operations/VERCEL.md`](../operations/VERCEL.md)
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md)
