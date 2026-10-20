-- mart: retention — returning-session rates by first-seen cohort day
-- (plan B5 — retention family). Cohort = first day the pseudonymous anchor
-- was seen; activity = any later day inside the retention horizon.
-- Schedule: daily; partition filter mandatory (@since/@until horizon).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.retention` AS
WITH activity AS (
  SELECT
    event_date,
    COALESCE(uid_hash, anon_id) AS anchor,
    session_id
  FROM `PROJECT.signals_raw.events`
  WHERE event_date >= @since
    AND event_date < @until
    AND source IN ('first_party', 'server', 'shadow')
    AND (uid_hash IS NOT NULL OR anon_id IS NOT NULL)
),
cohorts AS (
  SELECT anchor, MIN(event_date) AS cohort_date
  FROM activity
  GROUP BY anchor
)
SELECT
  c.cohort_date,
  a.event_date AS activity_date,
  DATE_DIFF(a.event_date, c.cohort_date, DAY) AS days_since_first,
  COUNT(DISTINCT a.anchor) AS returning_anchors
FROM activity a
JOIN cohorts c USING (anchor)
WHERE a.event_date >= @since
  AND a.event_date < @until
GROUP BY c.cohort_date, a.event_date;
