-- mart: booking funnel per day (plan B5 — funnels family).
-- Funnel steps are registry events; counts are exact within the window.
-- Schedule: daily; partition filter mandatory (@since/@until window).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.funnels` AS
WITH windowed AS (
  SELECT
    event_date,
    name,
    session_id,
    uid_hash,
    JSON_VALUE(params_json, '$.value') AS value,
    JSON_VALUE(params_json, '$.value_basket') AS value_basket
  FROM `PROJECT.signals_raw.events`
  WHERE event_date >= @since
    AND event_date < @until
    AND source IN ('first_party', 'server', 'shadow')
)
SELECT
  event_date,
  COUNT(DISTINCT IF(name = 'page_view', session_id, NULL)) AS sessions_viewed,
  COUNT(DISTINCT IF(name = 'view_item', session_id, NULL)) AS sessions_item_viewed,
  COUNT(DISTINCT IF(name = 'checkout_started', session_id, NULL)) AS sessions_checkout_started,
  COUNT(DISTINCT IF(name = 'begin_checkout', session_id, NULL)) AS sessions_begin_checkout,
  COUNT(DISTINCT IF(name = 'purchase_viewed', session_id, NULL)) AS sessions_purchase_viewed,
  COUNT(DISTINCT IF(name = 'booking_confirmed', uid_hash, NULL)) AS bookings_confirmed,
  COUNT(DISTINCT IF(name = 'payment_succeeded', uid_hash, NULL)) AS payments_succeeded
FROM windowed
GROUP BY event_date;
