# Navdhya Frontend Canary, Rollback & API Floor Skew Runbook (`G-DEPLOY-1`)

## 1. Per-App Container & Liveness Probes

All four Next.js BFF applications build standalone container images (`apps/<app>/Dockerfile`) running as unprivileged user `nextjs` and exposing a uniform unauthenticated liveness endpoint at `GET /api/healthz` (`Cache-Control: no-store`):

| App | Port | Host | Healthz |
| --- | --- | --- | --- |
| `apps/web` | 3000 | `www.navdhya.com` | `/api/healthz` |
| `apps/customer` | 3001 | `app.navdhya.com` | `/api/healthz` |
| `apps/partner` | 3002 | `partner.navdhya.com` | `/api/healthz` |
| `apps/dashboard` | 3003 | `dashboard.navdhya.com` | `/api/healthz` |

## 2. API Floor Skew Contract

Frontend deploys enforce `MIN_SUPPORTED_API_VERSION` from `@navdhya/contracts` (`assertApiFloorCompatible`):
1. Deploy backend (`platform-navdhya`) **before** raising `MIN_SUPPORTED_API_VERSION` in frontend releases.
2. When rolling out a breaking backend change, run dual-version serialization behind `X-Navdhya-Api-Version` until all four BFF deployments have rolled.

## 3. Canary & Rollback Procedure

1. Roll `10%` traffic to the new container image for an individual app (never cross-couple deployments via shared session cookies — all sessions remain host-only per ADR-0023).
2. Watch `5xx` rate on `/api/*`, `DEPENDENCY_UNAVAILABLE` spikes, and `/api/healthz` probe latency for 10 minutes.
3. Rollback trigger: any `> 1%` 5xx rate, healthz failure, or API floor mismatch -> shift ALB/Ingress target group back to previous immutable digest immediately (`0` database schema rollback needed for frontend shifts).

