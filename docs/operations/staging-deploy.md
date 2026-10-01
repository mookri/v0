# Staging Deploy Matrix

Target: prove each app boots standalone with mocks, behind its own origin,
before any production cutover (phase 25).

## Apps × ports × origins

| App | Dev port | Standalone | Staging origin | Health |
|-----|----------|------------|----------------|--------|
| web (`apps/web`) | 3000 | `output: standalone` (check app config) | `https://www.navdhya.com` | TBD |
| customer (`apps/customer`) | 3001 | `output: standalone` | `https://app.navdhya.com` | `/api/health` → `{status:'ok',product:'customer'}` · `/api/version` → build metadata |
| partner (`apps/partner`) | 3002 | `output: standalone` (check app config) | `https://partner.navdhya.com` | `/api/health` → `{status:'ok',product:'partner'}` · `/api/version` → build metadata |

## Env contract

- `NEXT_PUBLIC_*`: origin/product identifiers only (baked at build).
- Server-only (`packages/config` `validateEnv`): DB URLs, signing keys —
  never `NEXT_PUBLIC_`, never read in client components.
- Backend plug-in: mocks are the default; setting backend base URLs must not
  require frontend rewrites (see `docs/api`, BFF boundary).

## Staging gate (per app)

1. `pnpm --filter <app> build && start` serves on its port.
2. Health endpoint returns ok with `Cache-Control: no-store`.
3. BFF routes return envelope errors (no stack leaks) when unauthenticated.
4. Old web routes still live (strangler fallback intact).

Status: matrix documented; deploy run pending (see `migration-status.yaml`
`19-staging-deploy`).

## Local staging run (2026-10-01, Windows dev box, node v24 vs wanted 20.x)

- customer: `NEXT_STANDALONE=0 pnpm build` ✓ (18 static pages), booted on
  :3001 — `/api/health` → ok envelope; `/api/bookings` → 401
  `UNAUTHENTICATED` envelope, no leaks; `/` → 200.
- partner: build ✓, booted on :3002 — `/api/health` → ok; accept without
  actor → 401; with actor → stable `ASSIGNMENT_NOT_FOUND` envelope.
- web: build ✓ (1363 static pages), booted on :3000 — `/` → 200,
  `/book` → 200, `/user/bookings` → 200 with NO redirect (strangler flag
  off, as designed). Fallback intact.
- Fixes the run forced: Next 16 dynamic route/page `params` are now
  `Promise` (4 routes + 1 page updated); `NEXT_STANDALONE=0` escape for
  local runs (Windows lacks symlink privilege for standalone trace-copy);
  `outputFileTracingRoot` pinned (stray lockfile above repo).
- Environment notes for real staging: pin node 20.x (`.nvmrc`); deploy
  pipelines keep default `standalone` output.
