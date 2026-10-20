# Production Cutover Runbook (phase 25)

> **Status: Target.** This is the flag-gated cutover plan; it is not evidence
> that live traffic has been flipped. Deploy and recovery context:
> [`DEPLOYMENT.md`](DEPLOYMENT.md) and [`ROLLBACK.md`](ROLLBACK.md).

Scope: flipping live traffic from web-served customer/partner routes to the
customer/partner apps via the strangler (flag-gated 307 redirects).
Web routes stay deployed throughout — rollback is flag-off, instant.

## Preconditions (all must be green)

1. Staging gate per app passes on the release candidate: build ✓, health ✓,
   envelope errors ✓, fallback routes live ✓. The 2026-10-01 drill in
   [`staging-deploy.md`](staging-deploy.md) is historical; re-run the gate
   before a live flip.
2. Full suite green: arch gates + unit suites (see `migration-status.yaml`
   baselines).
3. `turbo run typecheck` 40/40.
4. Standalone output verified in a Linux pipeline (local Windows runs use
   `NEXT_STANDALONE=0`; standalone is deploy-only).
5. Node 20.x pinned in deploy env (repo engine; local runs warn on v24).
6. Secrets present, server-only: session signing keys, Razorpay `keyId` /
   `keySecret` (never `NEXT_PUBLIC_*`).

## Known non-blocking warnings (drill-observed, 2026-10-01)

- Web logs `unstable_cacheLife/cacheTag should be imported as cacheLife/
  cacheTag` at boot (Next 16 stabilized the names; the `unstable_` prefix
  will be removed in a future Next). Non-fatal today; migrate imports
  before the next Next upgrade or the web build breaks.
- `next start` warns when `output: standalone` is set but unused locally
  (expected: local runs use `NEXT_STANDALONE=0`; deploy uses standalone).

## Order of cutover (one class at a time)

| Step | Flag | Traffic | Verify |
|------|------|---------|--------|
| 1 | `STRANGLER_CUSTOMER_BOOK=1` on web | `/book/**` → `app.navdhya.com` | probes below |
| 2 | `STRANGLER_CUSTOMER_USER=1` | `/user/**` → `app.navdhya.com` | probes below |
| 3 | `STRANGLER_CUSTOMER_SETTINGS=1` | `/settings/**` → `app.navdhya.com` | probes below |
| (`STRANGLER_CUSTOMER_LIVE=1` sets all three at once — stepwise flags take precedence per class.) | | | |
| 4 (later) | partner flag (does not exist yet — add when partner routes proven) | `/pandit/**`, `/vendor/**` → partner origin | partner health + accept flow |
| never via strangler | public (`/`, `/pujas/**`, …) and `/admin/**` | stay on web | — |

## Probes after each step (record in `cutover-log.md`)

- `GET https://www.navdhya.com/book` → 307 to `app.navdhya.com/book*`
  (query preserved).
- `GET https://app.navdhya.com/api/health` → ok envelope.
- Authenticated booking list/detail round-trip on the app origin.
- `/book/start?intent=…` handoff from a live web entry completes.
- Error rate / 5xx on both origins flat for 30 min before next step.

## Rollback

- Set the flag back to `0` and redeploy web (or flip env without redeploy
  if the platform supports it). Web still serves every route — users see
  zero downtime, only the origin changes back.
- Rollback triggers: any probe red, 5xx lift, session/auth errors, payment
  verification failures.
- Log every flip (both directions) in `cutover-log.md` with timestamp,
  actor, reason, and probe results.

## Out of scope for first cutover

- Partner classes (step 2 needs its own gate run).
- Admin (`/admin/**` stays web until phase 28).
- Realtime broker upgrade (single-instance memory bus is staging-grade;
  cut over reads first, live channels second).
