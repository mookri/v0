-- Signals outbox (plan B2) — transactional append target for P0 emissions.
-- Deployed with the DATABASE_URL provision step; until then the store is
-- exercised against the in-memory reference implementation (contract tests)
-- and a statement-level fake client (tx tests).
--
-- Contract: business row + outbox row share one transaction; publication is
-- at-least-once with lease redelivery; effects dedup by id (envelope ULID).

CREATE TABLE IF NOT EXISTS outbox (
  id               text        PRIMARY KEY,          -- stable signal/domain ID (ULID)
  aggregate_id     text        NOT NULL,
  type             text        NOT NULL,             -- registry event name for signals
  payload          jsonb       NOT NULL,             -- full SignalEnvelope for signals
  occurred_at      timestamptz NOT NULL,
  published        boolean     NOT NULL DEFAULT false,
  published_at     timestamptz,
  claimed_at       timestamptz,                      -- lease holder since
  lease_expires_at timestamptz,                      -- redeliverable after
  attempts         integer     NOT NULL DEFAULT 0,   -- claims (attempts started)
  dead_lettered    boolean     NOT NULL DEFAULT false
);

-- Fast claim path: only free, live work is ever scanned.
CREATE INDEX IF NOT EXISTS outbox_claim_idx
  ON outbox (occurred_at)
  WHERE NOT published AND NOT dead_lettered;

-- Reconciliation view: committed vs published (gaps feed Level-1 alerts).
CREATE VIEW IF NOT EXISTS outbox_level1 AS
SELECT id, type, occurred_at, published, dead_lettered, attempts,
       published_at, lease_expires_at
FROM outbox;
