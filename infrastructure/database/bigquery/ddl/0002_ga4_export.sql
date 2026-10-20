-- GA4 export namespace (plan B5): the GA4 BigQuery export is GOOGLE-MANAGED
-- — the link is created in the GA4 admin console (user-only step) and lands
-- in its own dataset (`analytics_<PROPERTY_ID>`, daily `events_*` tables).
-- It stays OUT of signals_raw's namespace by design; signals_stg transforms
-- it into reporting models (see models/stg/stg_ga4_events.sql).
--
-- What IS version-controlled here: our side of the contract — the typed
-- reporting dataset we build ON TOP of the export, plus the placeholder
-- property id to replace when the link exists.

CREATE SCHEMA IF NOT EXISTS `PROJECT.signals_ga4`
  OPTIONS (description = 'Reporting models over the Google-managed GA4 export (analytics_<PROPERTY_ID>). Never a source of truth — Level-2 compares it, Level-1 never reads it.');

-- Daily exported events, typed and deduplicated by eventUniqueId.
-- Partition filter: _TABLE_SUFFIX (the export''s own daily pseudo-partition),
-- required on every query — see models/stg/stg_ga4_events.sql.
CREATE VIEW IF NOT EXISTS `PROJECT.signals_ga4.events_typed` AS
SELECT
  event_date,
  event_name,
  event_unique_id,
  user_pseudo_id,
  (SELECT value.int_value FROM UNNEST(event_params) WHERE key = 'session_id') AS ga4_session_id,
  (SELECT value.string_value FROM UNNEST(event_params) WHERE key = 'transaction_id') AS transaction_id,
  (SELECT value.double_value FROM UNNEST(event_params) WHERE key = 'value') AS value,
  traffic_source.source AS traffic_source,
  traffic_source.medium AS traffic_medium,
  traffic_source.name AS traffic_campaign
FROM `PROJECT.analytics_000000000.events_*`
WHERE _TABLE_SUFFIX BETWEEN FORMAT_DATE('%Y%m%d', DATE_SUB(CURRENT_DATE(), INTERVAL 400 DAY))
                        AND FORMAT_DATE('%Y%m%d', CURRENT_DATE());
