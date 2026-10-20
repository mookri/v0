-- mart: LTV — cumulative captured value per pseudonymous anchor
-- (plan B5 — LTV family). Value comes from server `payment_succeeded` rows
-- (backend truth, never the displayed price). Display-only `value` params
-- from client events are excluded by construction.
-- Schedule: daily; partition filter mandatory (@since/@until window).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.ltv` AS
WITH payments AS (
  SELECT
    event_date,
    COALESCE(uid_hash, anon_id) AS anchor,
    SAFE_CAST(JSON_VALUE(params_json, '$.value') AS FLOAT64) AS captured_value,
    JSON_VALUE(params_json, '$.currency') AS currency,
    JSON_VALUE(params_json, '$.transaction_ref') AS transaction_ref
  FROM `PROJECT.signals_raw.events`
  WHERE event_date >= @since
    AND event_date < @until
    AND name = 'payment_succeeded'
    AND source = 'server'
)
SELECT
  anchor,
  currency,
  COUNT(DISTINCT transaction_ref) AS payments,
  SUM(captured_value) AS lifetime_value,
  MIN(event_date) AS first_payment_date,
  MAX(event_date) AS last_payment_date
FROM payments
WHERE anchor IS NOT NULL
GROUP BY anchor, currency;
