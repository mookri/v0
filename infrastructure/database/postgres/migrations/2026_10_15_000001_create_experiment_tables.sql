-- Experiment tables (plan B8, phase-1 experimentation): the ledger is the
-- assignment AUTHORITY — Remote Config may deliver variant configuration,
-- but "which variant, when, under which allocation_version" lives here.
-- Exposures are a SEPARATE record, written only when the experience is
-- actually displayed; an assignment never implies an exposure row.
-- Subject keys are HMAC pseudonyms (B7 posture) — never raw user ids.
-- Timestamps are epoch ms, matching the rule_actions posture.

CREATE TABLE IF NOT EXISTS experiment_assignments (
  experiment_id      text    NOT NULL,
  subject_id_hash    text    NOT NULL,
  variant            text    NOT NULL,
  allocation_version integer NOT NULL,
  assigned_at_ms     bigint  NOT NULL,
  -- First-write-wins: assignments are immutable facts (the store's
  -- ON CONFLICT DO NOTHING depends on this key).
  PRIMARY KEY (experiment_id, subject_id_hash)
);

CREATE TABLE IF NOT EXISTS experiment_exposures (
  experiment_id   text   NOT NULL,
  subject_id_hash text   NOT NULL,
  variant         text   NOT NULL,
  exposed_at_ms   bigint NOT NULL,
  -- One exposure per subject per experiment (re-display never duplicates;
  -- SRM counts subjects, not impressions).
  PRIMARY KEY (experiment_id, subject_id_hash)
);
