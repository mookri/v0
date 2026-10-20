# Runbook — collector outage (and ingestion 5xx)

**Drill evidence:** `tests/signals/failure-drills.test.ts` (`collector-outage`, `ingestion-500s`); client-leg load matrix `tests/signals/load-matrix.test.ts`.

## Symptoms / detection

- Client `metrics().failed` climbing; `deliveryRatio()` dropping below baseline (acknowledged ÷ eligible-attempted).
- Edge collector health endpoint returning non-200 or timing out.
- Ingestion Cloud Run revision returning 500s (storage down) — collector maps these to **503** toward senders (status map: no durable ack ⇒ retryable).
- No durable-acceptance acks in ingestion logs; sink row counts flat.

## Expected degradation mode (this is the design, not a bug)

- Browser clients **queue locally** (persist-before-delivery outbox) and retry with jittered backoff. Events are removed only after a positive ack — nothing is lost, nothing is double-counted (sink insertId dedup absorbs replays).
- Business flows (checkout, booking) are **unaffected**: the SDK never blocks the UI thread or the business path.
- `flushBeacon()` on unload is best-effort only; those sends stay outside the delivery-SLO denominator.

## Immediate actions

1. Confirm scope: collector-only or also ingestion (check both health endpoints).
2. If ingestion: check downstream (Pub/Sub, BigQuery streaming quota) first — an ingestion outage is usually downstream-caused.
3. Do NOT restart client fleets or flush queues manually; the client retry ladder self-recovers.

## Recovery

- When the collector/ingestion answers 200 again, client queues drain on the next flush cycles (backoff caps at 30s).
- Ingestion storage heals ⇒ retried batches get durable-acceptance 200; all-or-nothing per batch means no partial writes ever landed.

## Verification

- Client `deliveryRatio()` returns to baseline within one backoff window.
- Sink row counts resume; `reconcileLevel1` (nightly) shows zero unexplained loss for the outage window.
- B5 nightly `quarantined_records` / `reconciliation_ok` checks green next run.
