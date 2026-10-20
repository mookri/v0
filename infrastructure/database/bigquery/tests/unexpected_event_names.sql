-- Data-quality test: every persisted event name must be in the registry
-- allowlist (an unknown name = producer bug or injection — one returned row
-- is a failure). Schedule nightly with the @since/@until window.

SELECT id, name, event_date, source
FROM `PROJECT.signals_raw.events`
WHERE event_date >= @since
  AND event_date < @until
  AND name NOT IN (
    'page_view', 'view_item', 'search', 'zero_results_search',
    'checkout_started', 'begin_checkout', 'purchase_viewed',
    'sign_up', 'login', 'kyc_submitted', 'app_error',
    'booking_confirmed', 'payment_succeeded', 'payment_failed',
    'ad_impression', 'ad_click'
  );
