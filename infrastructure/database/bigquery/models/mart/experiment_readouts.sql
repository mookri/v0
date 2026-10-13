-- mart: experiment readouts — exposure → outcome split per experiment
-- (plan B5 — experiment readouts family). Exposure identity comes from
-- experiment_exposure_id on the envelope; only events carrying it join
-- (explicit join key, no user-level inference).
-- Schedule: daily; partition filter mandatory (@since/@until window).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.experiment_readouts` AS
WITH scoped AS (
  SELECT
    event_date,
    experiment_exposure_id,
    name,
    session_id,
    SAFE_CAST(JSON_VALUE(params_json, '$.value') AS FLOAT64) AS value
  FROM `PROJECT.signals_raw.events`
  WHERE event_date >= @since
    AND event_date < @until
    AND experiment_exposure_id IS NOT NULL
    AND source IN ('first_party', 'server', 'shadow')
)
SELECT
  experiment_exposure_id,
  name AS metric_event,
  COUNT(DISTINCT session_id) AS exposed_sessions,
  COUNT(*) AS event_count,
  SUM(IF(name = 'payment_succeeded', value, 0)) AS value_sum
FROM scoped
GROUP BY experiment_exposure_id, name;
