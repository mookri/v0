# Staging Deploy Matrix

Target: prove each app boots standalone with mocks, behind its own origin,
before any production cutover (phase 25).

## Apps × ports × origins

| App | Dev port | Standalone | Staging origin | Health |
|-----|----------|------------|----------------|--------|
| web (`apps/web`) | 3000 | `output: standalone` (check app config) | `https://www.navdhya.com` | TBD |
| customer (`apps/customer`) | 3001 | `output: standalone` | `https://app.navdhya.com` | `/api/health` → `{status:'ok',product:'customer'}` |
| partner (`apps/partner`) | 3002 | `output: standalone` (check app config) | `https://partner.navdhya.com` | TBD |

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
