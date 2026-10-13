-- mart: attribution — first-touch UTM per anchor, joined to conversions
-- (plan B5 — attribution family). UTM context lives on the envelope; the
-- join stays pseudonymous (uid_hash/anon_id) per the controlled-join rule.
-- Schedule: daily; partition filter mandatory (@since/@until window).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.attribution` AS
WITH scoped AS (
  SELECT
    event_date,
    COALESCE(uid_hash, anon_id) AS anchor,
    name,
    JSON_VALUE(utm_json, '$.source') AS utm_source,
    JSON_VALUE(utm_json, '$.medium') AS utm_medium,
    JSON_VALUE(utm_json, '$.campaign') AS utm_campaign
  FROM `PROJECT.signals_raw.events`
  WHERE event_date >= @since
    AND event_date < @until
    AND source IN ('first_party', 'server', 'shadow')
    AND utm_json IS NOT NULL
    AND (uid_hash IS NOT NULL OR anon_id IS NOT NULL)
),
first_touch AS (
  -- Earliest touch inside the window; ties broken deterministically.
  SELECT anchor, utm_source, utm_medium, utm_campaign
  FROM scoped
  QUALIFY ROW_NUMBER() OVER (PARTITION BY anchor ORDER BY event_date, name) = 1
)
SELECT
  f.utm_source,
  f.utm_medium,
  f.utm_campaign,
  COUNT(DISTINCT f.anchor) AS anchors,
  COUNT(DISTINCT IF(s.name = 'payment_succeeded', s.anchor, NULL)) AS converting_anchors
FROM first_touch f
JOIN scoped s USING (anchor)
WHERE s.event_date >= @since
  AND s.event_date < @until
GROUP BY f.utm_source, f.utm_medium, f.utm_campaign;
