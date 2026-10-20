-- mart: data quality — daily rejection/consent/shape metrics (plan B5 —
-- data-quality family). Feeds the nightly checks: reject counts become the
-- quarantined diagnostic, consent mix explains expected GA4 gaps.
-- Schedule: daily; partition filter mandatory (@since/@until window).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.data_quality` AS
WITH raw_window AS (
  SELECT
    event_date,
    name,
    consent_analytics,
    params_json
  FROM `PROJECT.signals_raw.events`
  WHERE event_date >= @since
    AND event_date < @until
),
rejects AS (
  SELECT
    event_date,
    COUNT(*) AS quarantined_count
  FROM `PROJECT.signals_raw.quarantined`
  WHERE event_date >= @since
    AND event_date < @until
  GROUP BY event_date
)
SELECT
  r.event_date,
  COUNT(*) AS persisted_count,
  COUNTIF(NOT r.consent_analytics) AS consent_denied_count,
  COUNTIF(JSON_VALUE(r.params_json) IS NULL OR JSON_VALUE(r.params_json) = '') AS malformed_params_count,
  COUNT(DISTINCT r.name) AS distinct_event_names,
  COALESCE(q.quarantined_count, 0) AS quarantined_count
FROM raw_window r
LEFT JOIN rejects q USING (event_date)
GROUP BY r.event_date, q.quarantined_count;
