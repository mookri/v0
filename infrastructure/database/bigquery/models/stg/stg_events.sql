-- stg_events: typed, validated projection of signals_raw (plan B5).
-- Registry-shaped params stay in params_json; this model only promotes the
-- columns the marts actually verify against (verified query patterns →
-- clustering in the mart models).
-- Schedule: hourly; partition filter required (event_date window bound by the
-- scheduler parameters @since / @until).

CREATE OR REPLACE VIEW `PROJECT.signals_stg.events` AS
SELECT
  id,
  name,
  version,
  occurred_at,
  received_at,
  event_date,
  surface,
  app_version,
  session_id,
  session_seq,
  -- Controlled identifier joins: pseudonymous anchors only, never raw PII.
  uid_hash,
  anon_id,
  consent_analytics,
  consent_ads,
  route_template,
  experiment_exposure_id,
  JSON_VALUE(params_json, '$.value') AS param_value,
  JSON_VALUE(params_json, '$.currency') AS param_currency,
  source
FROM `PROJECT.signals_raw.events`
WHERE event_date >= @since
  AND event_date < @until
  AND source IN ('first_party', 'server', 'shadow');  -- ga4_export never re-enters truth
