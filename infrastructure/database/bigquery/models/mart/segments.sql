-- mart: segments v1 — explainable cohort memberships materialized from
-- evidence (plan B6). The TS evaluator (packages/analytics/src/segments/)
-- is the batch reference implementation over the SAME definitions; this
-- model materializes the slow-moving cohorts in BigQuery. NO segment →
-- Remote-Config sync exists or is permitted from this dataset — client
-- config never carries segment membership.
-- Schedule: daily; partition filter mandatory (@since/@until window).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.segments` AS
WITH scoped AS (
  SELECT
    COALESCE(uid_hash, anon_id) AS anchor,
    event_date,
    name,
    session_id,
    JSON_VALUE(params_json, '$.city') AS city,
    JSON_VALUE(utm_json, '$.source') AS utm_source
  FROM `PROJECT.signals_raw.events`
  WHERE event_date >= @since
    AND event_date < @until
    AND source IN ('first_party', 'server', 'shadow')
    AND (uid_hash IS NOT NULL OR anon_id IS NOT NULL)
),
rollup AS (
  SELECT
    anchor,
    COUNTIF(name = 'zero_results_search') AS zero_result_searches,
    COUNTIF(name IN ('view_item', 'checkout_started', 'begin_checkout')) AS high_intent_events,
    COUNTIF(name IN ('purchase_viewed', 'booking_confirmed')) AS in_window_conversions,
    MAX(event_date) AS last_seen_date,
    COUNT(DISTINCT IF(city IS NOT NULL, city, NULL)) AS distinct_cities,
    ANY_VALUE(city HAVING MAX event_date) AS latest_city,
    ANY_VALUE(utm_source HAVING MAX event_date) AS latest_utm_source
  FROM scoped
  WHERE anchor IS NOT NULL
  GROUP BY anchor
)
SELECT
  anchor,
  -- Segment definitions mirror packages/analytics/src/segments/segments-v1.ts.
  high_intent_events >= 2 AND in_window_conversions = 0 AS high_intent,
  zero_result_searches >= 2 AS catalog_gap_searcher,
  latest_city IN ('Pune', 'Mumbai', 'Bengaluru') AS city_cohort,
  latest_utm_source IN ('organic', 'paid_search', 'referral', 'partner') AS acquisition_cohort,
  zero_result_searches,
  high_intent_events,
  in_window_conversions,
  last_seen_date,
  latest_city,
  latest_utm_source
FROM rollup;
