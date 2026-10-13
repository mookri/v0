-- Rule actions (plan B6) — one row per (rule, subject, triggering events)
-- decision, retained so EVERY fired action is explainable a year later from
-- persisted records: rule version, triggering event IDs, decision + evidence,
-- action request ID, final delivery status.
--
-- Deployed with the DATABASE_URL provision step; until then the store is
-- exercised against the in-memory reference implementation (contract tests)
-- and a statement-level fake client (store tests) — same posture as the B2
-- outbox.

CREATE TABLE IF NOT EXISTS rule_actions (
  action_request_id    text        PRIMARY KEY,   -- deterministic idempotency key
  rule_id              text        NOT NULL,
  rule_version         integer     NOT NULL,
  subject_key          text        NOT NULL,
  triggering_event_ids jsonb       NOT NULL,      -- the replay/dedup identity
  decision             text        NOT NULL,      -- fired | pending_window | suppressed_*
  evidence             text        NOT NULL,      -- the WHY, queryable
  requested_at_ms      bigint      NOT NULL,
  evaluate_at_ms       bigint,                    -- due time for windowed rules
  decided_at_ms        bigint,
  delivery_status      text        NOT NULL,      -- scheduled|pending|delivered|failed|none
  delivered_at_ms      bigint,
  attempts             integer     NOT NULL DEFAULT 0
);

-- Frequency guards: fired counts per (rule, subject) since a timestamp.
CREATE INDEX IF NOT EXISTS rule_actions_fired_idx
  ON rule_actions (rule_id, subject_key, requested_at_ms)
  WHERE decision = 'fired';

-- Sweep path: only live windowed work is scanned.
CREATE INDEX IF NOT EXISTS rule_actions_pending_due_idx
  ON rule_actions (evaluate_at_ms)
  WHERE decision = 'pending_window';

-- Audit view: decision history without the storage columns' ordering noise.
CREATE VIEW IF NOT EXISTS rule_actions_audit AS
SELECT action_request_id, rule_id, rule_version, subject_key,
       triggering_event_ids, decision, evidence, delivery_status,
       to_timestamp(requested_at_ms / 1000.0)  AS requested_at,
       to_timestamp(coalesce(decided_at_ms, requested_at_ms) / 1000.0) AS decided_at
FROM rule_actions;
