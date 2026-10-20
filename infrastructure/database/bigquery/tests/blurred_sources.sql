-- Data-quality test: source must stay inside the enforced set — the streams
-- never blur (defense in depth: toRawRow validates on write, this catches
-- direct inserts). One returned row is a failure.

SELECT id, name, event_date, source
FROM `PROJECT.signals_raw.events`
WHERE event_date >= @since
  AND event_date < @until
  AND source NOT IN ('first_party', 'server', 'shadow', 'ga4_export');
