-- stg_ga4_events: typed view over the Google-managed GA4 export (plan B5).
-- The export is daily-partitioned by table suffix — _TABLE_SUFFIX IS the
-- partition filter, so this model can never become an unbounded scan.
-- Schedule: daily after the export lands (GA4 export lag ≈ 24–48h; Level-2
-- excludes days still inside that lag).

CREATE OR REPLACE VIEW `PROJECT.signals_stg.ga4_events` AS
SELECT
  event_date,
  event_name,
  event_unique_id,          -- duplicate-suppression key for Level-2
  user_pseudo_id,
  ga4_session_id,
  transaction_id,
  value,
  traffic_source,
  traffic_medium,
  traffic_campaign
FROM `PROJECT.analytics_000000000.events_*`
WHERE _TABLE_SUFFIX BETWEEN FORMAT_DATE('%Y%m%d', @since)
                        AND FORMAT_DATE('%Y%m%d', DATE_SUB(@until, INTERVAL 1 DAY));
