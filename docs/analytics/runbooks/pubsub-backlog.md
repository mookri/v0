# Runbook — Pub/Sub backlog

**Drill evidence:** `tests/signals/failure-drills.test.ts` (`pubsub-backlog`); load matrix truth-path leg `tests/signals/load-matrix.test.ts`.

## Symptoms / detection

- Outbox-publisher logs: `released` counts > 0 across cycles; `published` flat.
- Pub/Sub subscription backlog depth (unacked messages) rising on the signals topic.
- Level-1 reconciliation `missingPublished` growing (committed but not yet acked) — the standing safety net, not yet data loss.

## Expected degradation mode

- Records **stay in the outbox** with `attempts` incrementing per claim cycle. They are RETAINED — never dropped, never dead-lettered below the attempts ceiling.
- Action latency (async rules p95 < 60s) degrades toward the backlog-drain time; P0 truth is delayed, not lost.
- Ingestion consumers idle; sink writes flat.

## Immediate actions

1. Identify the transport fault: Pub/Sub publish permissions, endpoint reachability, quota.
2. Confirm attempts are below `maxAttempts` (default 5 in publisher; drills use 50). Records at the ceiling dead-letter and alert via `onDeadLettered` — those need individual replay after the fix.
3. Do not manually mark records published — the publisher's claim/ack loop drains in order.

## Recovery

- Heal the transport; publisher cycles drain the backlog oldest-first (claim → publish → markPublished).
- Crash-replay duplicates during redelivery collapse at the sink by insertId — exactly-once rows despite at-least-once delivery.

## Verification

- Publisher logs: backlog reaches zero (all records `published: true`).
- `reconcileLevel1` clean: `missingPublished: []`, `duplicatedPersisted: []`.
- Action-latency SLO recovers once backlog clears (p95 < 60s).
