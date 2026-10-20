-- Level-1 reconciliation queries (plan B2, item 6). Two sides, one ID:
-- outbox record ID == envelope ULID == signals_raw.id.
-- Schedule these alongside the publisher loop; any row returned is a gap
-- that MUST appear in the alert payload (never inferred away).

-- ---------------------------------------------------------------------------
-- Postgres side: committed vs published.
-- ---------------------------------------------------------------------------

-- Committed but never acked by the publisher (and not dead-lettered).
-- Healthy steady state: rows only younger than the publish SLO.
SELECT id, type, occurred_at, attempts, lease_expires_at
FROM outbox
WHERE NOT published
  AND NOT dead_lettered
  AND occurred_at < now() - interval '5 minutes'
ORDER BY occurred_at ASC;

-- Dead-lettered signals: visible in the gap report by construction.
SELECT id, type, occurred_at, attempts, published_at
FROM outbox
WHERE dead_lettered
ORDER BY occurred_at ASC;

-- ---------------------------------------------------------------------------
-- BigQuery side: what signals_raw actually contains (as a table macro:
-- parameterize @since / @until to the reconciliation window).
-- ---------------------------------------------------------------------------

-- Persisted more than once — dedup (insertId) violated.
SELECT id, COUNT(*) AS n
FROM `PROJECT.signals_raw.events`
WHERE occurred_at >= @since AND occurred_at < @until
GROUP BY id
HAVING n > 1;

-- ---------------------------------------------------------------------------
-- Cross-warehouse diff (application-side): committed ∖ persisted =
-- missingPersisted lives in workers/reconciliation/level1.ts
-- (reconcileLevel1). It cannot be one SQL statement: the outbox is Postgres,
-- signals_raw is BigQuery, and no federated join exists by design. Feed it:
--   committedIds / publishedIds ← Postgres queries above (window-scoped)
--   persistedIds                ← SELECT id FROM `PROJECT.signals_raw.events`
--                                    WHERE occurred_at >= @since
--                                      AND occurred_at <  @until
-- then diff in code so the report format stays stable across warehouses.
-- ---------------------------------------------------------------------------
