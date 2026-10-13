-- Data-quality test: integrity columns must never be NULL or empty
-- (one returned row is a failure). Schedule nightly with @since/@until.

SELECT id, name, event_date, source, session_id
FROM `PROJECT.signals_raw.events`
WHERE event_date >= @since
  AND event_date < @until
  AND (
    id IS NULL OR id = ''
    OR source IS NULL OR source = ''
    OR session_id IS NULL
    OR occurred_at IS NULL
    OR received_at IS NULL
  );
