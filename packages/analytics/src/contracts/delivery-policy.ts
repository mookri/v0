// packages/analytics/src/contracts/delivery-policy.ts
//
// Sink policy: every registered event declares exactly where it may go.
// One event may intentionally reach different sinks; it must never reach the
// same reporting destination through two paths (no reporting-time dedup as a
// fix for instrumentation design).

export const SINKS = ['gtm_ga4', 'collector_bigquery', 'server_ga4', 'internal_only'] as const;

/** Where an event may be delivered. */
export type Sink = (typeof SINKS)[number];

/** Human-readable ownership of each sink (documented, not enforced here). */
export const SINK_OWNERS: Record<Sink, string> = {
  gtm_ga4: 'Google Tag Manager web container → GA4 (browser-owned reporting events)',
  collector_bigquery: 'First-party collector → Pub/Sub → signals_raw (private analytical stream)',
  server_ga4: 'Server emitter → GA4 Measurement Protocol (authoritative business events)',
  internal_only: 'Internal stores only; never a reporting destination (e.g. ad beacons pre-B4)',
};
