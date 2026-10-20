-- Crash dataset (plan B8) — FIRST-PARTY, RESTRICTED.
--
-- Crash payloads carry stack traces and (redacted) free text; access is
-- limited to the crash owners, NOT the general analytics audience. Nothing
-- here is routed through GA4 — event params are not a crash channel (plan
-- wording: "not a GA4 dumping ground").
--
-- Web instrumentation only: native app crashes never land in these tables,
-- and crash-free metrics computed from them are web-scoped by construction.

CREATE SCHEMA IF NOT EXISTS `PROJECT.signals_crash`;

CREATE TABLE IF NOT EXISTS `PROJECT.signals_crash.errors` (
  event_date DATE NOT NULL,          -- partition column (B5 cost-gate rule)
  occurred_at TIMESTAMP NOT NULL,
  release STRING NOT NULL,
  session_id STRING,                 -- null for errors outside a session
  subject_id_hash STRING,            -- pseudonym; often null (logged out)
  error_group STRING NOT NULL,       -- fingerprint (build hashes stripped)
  error_class STRING NOT NULL,
  message STRING,                    -- redacted at capture (crash/redact.ts)
  frames JSON,                       -- normalized app frames only
  breadcrumbs JSON,                  -- redacted bounded ring buffer
  sample_rate FLOAT64 NOT NULL       -- adaptive sampling probability (downstream rates are sampling-corrected by this)
)
PARTITION BY event_date
OPTIONS (
  partition_expiration_days = 180,  -- retention matrix domain crash_events
  description = 'REstricted: normalized crash reports (fingerprint, app frames, redacted message + breadcrumbs). Web instrumentation only.'
);

CREATE TABLE IF NOT EXISTS `PROJECT.signals_crash.sessions` (
  event_date DATE NOT NULL,          -- partition column
  release STRING NOT NULL,
  session_id STRING NOT NULL,
  started_at TIMESTAMP NOT NULL
)
PARTITION BY event_date
OPTIONS (
  partition_expiration_days = 180,  -- retention matrix domain crash_events
  description = 'REstricted: web-instrumented session observations — the honest denominator for crash-free rates.'
);
