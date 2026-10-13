-- mart: operational metrics — volume, publish lag, freshness per day
-- (plan B5 — operational metrics family). received_at − occurred_at is the
-- observable end-to-end pipeline lag; used by the volume_swing and
-- freshness nightly checks as their baseline source.
-- Schedule: hourly; partition filter mandatory (@since/@until window).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.operational_metrics` AS
SELECT
  event_date,
  source,
  name AS event_name,
  COUNT(*) AS row_count,
  COUNT(DISTINCT id) AS distinct_event_ids,
  AVG(TIMESTAMP_DIFF(received_at, occurred_at, SECOND)) AS avg_lag_seconds,
  MAX(TIMESTAMP_DIFF(received_at, occurred_at, SECOND)) AS max_lag_seconds,
  MAX(received_at) AS newest_received_at
FROM `PROJECT.signals_raw.events`
WHERE event_date >= @since
  AND event_date < @until
GROUP BY event_date, source, name;
