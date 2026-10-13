# Runbook — warehouse freshness (incl. BigQuery quota exhaustion)

**Drill evidence:** `tests/signals/failure-drills.test.ts` (`bigquery-quota-exhaustion`); B5 nightly `tests/signals/warehouse-nightly.test.ts`.

## Symptoms / detection

- B5 nightly freshness check: `signals_stg` / mart lag exceeds the 15-minute target.
- Ingestion answering **503** to senders (quota-exhausted revision) — the status map keeps this retryable; it must NEVER surface as 400/403/413.
- BigQuery console: job failures with `quotaExceeded` (streaming insert slots, `maximum_bytes_billed` trips on ad-hoc queries).

## Expected degradation mode

- Senders retain and retry their backlog (ordered, no drops); nothing persists half-way (all-or-nothing per batch).
- Staging/mart freshness degrades; GA4 export freshness is monitored separately and is not a hard real-time guarantee (§1).
- Warehouse-lint (static cost gate) and `maximum_bytes_billed` (production gate) are doing their job — a tripped budget is a signal, not an incident by itself.

## Immediate actions

1. Distinguish streaming-insert quota (ingestion path) from query-byte budget (reporting path).
2. Streaming: check partition hotness (single-partition streaming overload); consider splitting load or raising quota via console (user-operated).
3. Query budget: identify the over-budget query (warehouse-lint should have caught it statically — if not, file a lint gap); reduce its window or add partition filters before re-running.
4. Apply the **budget reduction ladder** (`packages/analytics/src/server/budget.ts`): diagnostics/sampling first, revenue pipeline last, never automatic.

## Recovery

- Quota restored → senders' retries get durable-acceptance 200; backlog drains to zero-lag.
- Staging views re-project automatically on the next ingestion; marts recompute on schedule.

## Verification

- Ingestion 503 rate back to zero; sink row counts resume.
- Nightly freshness check green; `reconciliation_ok` green.
- Budget usage back under 80% with the alert trail recorded.
