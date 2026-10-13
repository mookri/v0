-- mart: error_groups — release-scoped error-group counts, affected
-- sessions, and the WEB crash-free rate (plan B8 crash subsystem).
--
-- Honesty: web_crash_free_rate is web-instrumented sessions only (SDK
-- session observations from signals_crash.sessions). Native app crashes
-- never reach this pipeline and must never be equated with it — the same
-- basis string ships with the API metric (crash/trends.ts).
--
-- Schedule: daily; partition filter mandatory (B5 cost gate).

CREATE OR REPLACE VIEW `PROJECT.signals_mart.error_groups` AS
WITH errors AS (
  SELECT release, error_group, error_class, session_id
  FROM `PROJECT.signals_crash.errors`
  WHERE event_date >= @since
    AND event_date < @until
),
sessions AS (
  SELECT release, COUNT(DISTINCT session_id) AS sessions
  FROM `PROJECT.signals_crash.sessions`
  WHERE event_date >= @since
    AND event_date < @until
  GROUP BY release
)
SELECT
  e.release,
  e.error_group,
  e.error_class,
  COUNT(*) AS errors,
  COUNT(DISTINCT e.session_id) AS affected_sessions,
  s.sessions AS release_sessions,
  SAFE_DIVIDE(COUNT(DISTINCT e.session_id), s.sessions) AS affected_session_rate,
  1 - SAFE_DIVIDE(COUNT(DISTINCT e.session_id), s.sessions) AS web_crash_free_rate
FROM errors e
JOIN sessions s ON e.release = s.release
GROUP BY e.release, e.error_group, e.error_class, s.sessions
