-- Privacy tables (plan B7): the authoritative erasure-token list and the
-- per-system deletion audit trail. Deployed with the DATABASE_URL
-- provision step; until then the in-memory reference implementations
-- (packages/analytics/src/privacy/) carry the contract tests.
--
-- Retention (matrix v1.0.0): erasure_tokens are NEVER pruned — deleting a
-- token would resurrect the subject on the next projection rebuild.
-- deletion_audit rows carry a 3-year TTL (legal review owns the final value).

CREATE TABLE IF NOT EXISTS erasure_tokens (
  subject_hash text        PRIMARY KEY,           -- uidHash/anonId pseudonym — never a raw identifier
  scope        text        NOT NULL,              -- full | fanout_only
  erased_at    timestamptz NOT NULL,
  request_id   text        NOT NULL,              -- the deletion request that produced it
  CHECK (scope IN ('full', 'fanout_only'))
);

-- Consent withdrawals (plan B7): the authoritative fan-out suppression
-- rows. PRIMARY KEY (subject_hash, purpose) + first-write-wins semantics
-- (ON CONFLICT DO NOTHING) preserve the ledger's history rule.
CREATE TABLE IF NOT EXISTS consent_withdrawals (
  subject_hash text        NOT NULL,              -- pseudonym — never a raw identifier
  purpose      text        NOT NULL,
  withdrawn_at timestamptz NOT NULL,
  request_id   text        NOT NULL,
  PRIMARY KEY (subject_hash, purpose),
  CHECK (purpose IN ('analytics', 'ads'))
);

-- Scope only ever upgrades (fanout_only → full); the CHECK above plus the
-- workflow's upgrade rule keep the semantics honest at the DB layer too.

CREATE TABLE IF NOT EXISTS deletion_audit (
  request_id    text        PRIMARY KEY,
  subject_hash  text        NOT NULL,
  reason        text        NOT NULL,             -- account_deletion | consent_withdrawal
  requested_at  timestamptz NOT NULL,
  deadline_at   timestamptz NOT NULL,             -- 30-day SLA
  completed_at  timestamptz,
  status        text        NOT NULL,             -- in_progress | complete | partial_failure | sla_breached | dry_run
  mode          text        NOT NULL DEFAULT 'execute', -- execute | dry_run (dry_run claims no erasure)
  proofs        jsonb       NOT NULL,             -- per-system evidence, exactly as the workflow recorded it
  CHECK (reason IN ('account_deletion', 'consent_withdrawal')),
  CHECK (status IN ('in_progress', 'complete', 'partial_failure', 'sla_breached', 'dry_run')),
  CHECK (mode IN ('execute', 'dry_run'))
);

-- SLA watch: unfinished requests past their deadline (the verify() path).
CREATE INDEX IF NOT EXISTS deletion_audit_open_idx
  ON deletion_audit (deadline_at)
  WHERE completed_at IS NULL;

-- Audit view: subject-facing requests by pseudonym (regulator lookup path).
CREATE VIEW IF NOT EXISTS deletion_audit_by_subject AS
SELECT subject_hash, request_id, reason, status, requested_at, deadline_at, completed_at
FROM deletion_audit
ORDER BY requested_at DESC;
