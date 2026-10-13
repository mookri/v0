# Runbook — schema rollback (misdeployed schema)

**Drill evidence:** `tests/signals/failure-drills.test.ts` (`misdeployed-schema`); registry drift check `npm run registry:check` (from `packages/analytics`).

## Symptoms / detection

- Ingestion **400s** spiking with `invalid_events` / `unknown param` / unknown event name in the rejection detail.
- Dead-letter seam reports piling up (every rejection is surfaced explicitly — never silently dropped).
- Quarantined-records count rising (B5 `quarantined_records` nightly check).
- Client-side: SDK `warn` callbacks for registry-allowlist rejections (if the misdeploy is client-side).

## Expected degradation mode

- **All-or-nothing per batch**: a batch containing any invalid event persists NOTHING; valid batchmates are reported `deferred` so the sender retries without the rejected indexes.
- Zero silent rows — the fail-closed allowlist (unknown events and unknown params are violations) is the containment.
- Business flows unaffected: the kill switch (`NEXT_PUBLIC_SIGNALS_ENABLED=false`) no-ops the SDK without touching checkout/booking.

## Immediate actions

1. Confirm the drift: `npm run registry:check` (from `packages/analytics`) — generated validator out of sync with `events/index.ts`?
2. Engage the kill switch if the misdeployed client build is emitting at volume: set `NEXT_PUBLIC_SIGNALS_ENABLED=false`, redeploy the surface. The SDK becomes a no-op; events stop entering the wire immediately.
3. Server-side misdeploy: roll back the ingestion/registry deployment to the last known-good validator.

## Recovery — registry revert

1. Revert `packages/analytics/src/registry/events/index.ts` to the last good state (git revert of the registry commit).
2. Regenerate: `npx tsx src/registry/generate.ts` (from `packages/analytics`).
3. Redeploy ingestion + surfaces; confirm `registry:check` passes.
4. Re-enable the kill switch (restore `NEXT_PUBLIC_SIGNALS_ENABLED=true` where it was).
5. Retry deferred batches (senders retry automatically on 200-resume).

## Verification

- Ingestion 400 rate back to baseline; dead-letter seam quiet.
- Sink rows resume for the affected events; quarantined count stops growing.
- `registry:check` green in CI; nightly `reconciliation_ok` green.
