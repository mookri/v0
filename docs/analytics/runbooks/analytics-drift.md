# Runbook — analytics drift (schema/event/metric drift)

**Drill evidence:** registry drift gate `npm run registry:check` (from `packages/analytics`); B5 reconciliation + parity `tests/signals/level2-parity.test.ts`, `tests/signals/key-event-parity.test.ts`; B8 release-health `tests/signals/crash-release.test.ts`.

## Symptoms / detection

- Nightly checks green-but-drifting: event volume per name shifting > threshold without a corresponding product change; key-event counts diverging between first-party and GA4 (Level-2 parity).
- New event names or params appearing in quarantined records that no one recognizes (an SDK build shipping unregistered events — see also `schema-rollback.md`).
- Metric definitions changing in marts without a corresponding registry/contract change (dashboard numbers move without a code review trail).
- Crash-free rate or error-group counts shifting after a release without a matching canary alert (see `crash-spike.md`).

## Expected degradation mode

- Drift is **detected, not prevented** — the platform's honesty posture is that every divergence is either explained (a deliberate change with a review trail) or alerted (an unexplained one).
- No automatic correction: reconciliation and parity reports are measurement, not mutation. Drift never silently rewrites historical marts.

## Immediate actions

1. Classify the drift: intentional (new feature/event shipping) vs. unexplained.
2. Intentional: update the registry (`events/index.ts`), regenerate (`npx tsx src/registry/generate.ts`), add the contract test, and record the change in CHANGELOG. The nightly checks should re-baseline within one cycle.
3. Unexplained: bisect the last deployment touching the event path (SDK, collector, ingestion, marts). The Level-1 reconciliation report (`missingPersisted`, `duplicatedPersisted`) narrows whether the drift is upstream (emission) or downstream (ingestion/projection).
4. If drift is in GA4 only: check the GTM container and MP adapter (B4) — GA4 drift is not first-party truth drift; the first-party warehouse remains authoritative.

## Recovery

- Intentional drift: registry updated, checks re-baseline, drift closed with a review trail.
- Unexplained drift: fix the emission/ingestion bug, backfill affected partitions from `signals_raw` (append-only audit log supports replay), re-run reconciliation until clean.

## Verification

- `registry:check` green; nightly reconciliation + parity checks green for two consecutive cycles.
- Any backfilled partitions reconcile exactly once (no duplicates, no gaps).
