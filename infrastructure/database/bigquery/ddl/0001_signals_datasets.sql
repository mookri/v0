-- Navdhya Signals — BigQuery dataset layout (plan B5).
-- Version-controlled DDL; apply in order with the bq CLI (driver-gated):
--   bq --project_id=PROJECT query < infrastructure/database/bigquery/ddl/0001_signals_datasets.sql
-- Replace PROJECT with the real project id (no warehouse credential lives
-- in this repo — same posture as DATABASE_URL).
--
-- Policy encoded here:
--   * every query-facing table is partitioned by event_date (or an explicit
--     timestamp) — production queries MUST carry a partition filter
--     (enforced in CI by tooling/scripts/warehouse-lint.ts);
--   * clustering follows verified query patterns (source/name/session for
--     raw; name/day for marts);
--   * raw expiration follows the retention baseline: raw_30d=30,
--     raw_90d=90, audit_long=400 days. The single events table is bounded
--     at the audit ceiling; per-class pruning jobs are versioned with the
--     B7 retention matrix (class membership comes from registry
--     retentionClass per event name).

-- ---------------------------------------------------------------------------
-- Datasets (logical planes — raw truth, typed staging, reporting marts,
-- reconciliation artifacts; GA4 export stays in its own Google-managed
-- namespace, see 0002_ga4_export.sql).
-- ---------------------------------------------------------------------------

CREATE SCHEMA IF NOT EXISTS `PROJECT.signals_raw`
  OPTIONS (description = 'First-party + server + shadow signals, source-tagged. Append-only truth.');

CREATE SCHEMA IF NOT EXISTS `PROJECT.signals_stg`
  OPTIONS (description = 'Typed, validated, registry-shaped views over raw + GA4 export. Controlled identifier joins only.');

CREATE SCHEMA IF NOT EXISTS `PROJECT.signals_mart`
  OPTIONS (description = 'Reporting marts: funnels, retention, LTV, attribution, experiments, data quality, ops metrics.');

CREATE SCHEMA IF NOT EXISTS `PROJECT.signals_reconciliation`
  OPTIONS (description = 'Level-1/Level-2 reconciliation artifacts and nightly data-trust records.');

-- ---------------------------------------------------------------------------
-- signals_raw.events — the wire-format truth (envelope columns + JSON).
-- insertId = envelope ULID (streaming dedup) — one row per event ID.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `PROJECT.signals_raw.events` (
  id STRING NOT NULL,
  name STRING NOT NULL,
  version INT64 NOT NULL,
  occurred_at TIMESTAMP NOT NULL,
  received_at TIMESTAMP NOT NULL,
  event_date DATE NOT NULL,
  surface STRING NOT NULL,
  app_version STRING NOT NULL,
  session_id STRING NOT NULL,
  session_seq INT64 NOT NULL,
  uid_hash STRING,
  anon_id STRING,
  consent_analytics BOOL NOT NULL,
  consent_ads BOOL NOT NULL,
  route_template STRING,
  experiment_exposure_id STRING,
  utm_json STRING,
  params_json STRING,
  breadcrumbs_json STRING,
  source STRING NOT NULL  -- first_party | server | shadow | ga4_export — never blurred
)
PARTITION BY event_date
OPTIONS (
  partition_expiration_days = 400,  -- audit_long ceiling; per-class pruning is B7
  description = 'Append-only signal rows; partitioned by event date, clustered by pipeline dimensions.'
)
CLUSTER BY source, name, session_id;

-- Schema-invalid / rejected rows stay VISIBLE (never silently dropped):
-- the nightly quarantined check reads this table.
CREATE TABLE IF NOT EXISTS `PROJECT.signals_raw.quarantined` (
  id STRING,
  name STRING,
  reason STRING NOT NULL,
  payload_json STRING,
  source STRING,
  occurred_at TIMESTAMP,
  event_date DATE NOT NULL
)
PARTITION BY event_date
OPTIONS (description = 'Schema-rejected rows with their reject reason — input to the quarantined_records nightly check.');

-- ---------------------------------------------------------------------------
-- signals_reconciliation — nightly check results + data-trust records.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `PROJECT.signals_reconciliation.nightly_checks` (
  checked_at TIMESTAMP NOT NULL,
  check_id STRING NOT NULL,
  status STRING NOT NULL,          -- green | red
  diagnostics_json STRING,
  detail_json STRING,
  owner STRING
)
PARTITION BY DATE(checked_at)
CLUSTER BY check_id, status
OPTIONS (description = 'One row per check per night — audit trail behind the data-trust badge.');

CREATE TABLE IF NOT EXISTS `PROJECT.signals_reconciliation.level2_parity` (
  window_since DATE NOT NULL,
  window_until DATE NOT NULL,
  metric STRING NOT NULL,
  computed_at TIMESTAMP NOT NULL,
  window_drift_pct FLOAT64,
  status STRING NOT NULL,          -- ok | warn | alert
  report_json STRING
)
PARTITION BY window_since
OPTIONS (description = 'Level-2 measurement comparisons — never a replacement for Level-1.');
