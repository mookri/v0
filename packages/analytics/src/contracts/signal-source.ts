// Canonical provenance column for signals_raw rows: WHERE a row entered the
// warehouse, decided at ingestion — never inferred at reporting time (rule:
// no reporting-time fixes for instrumentation design).
//
//   first_party  browser SDK → collector → Pub/Sub (production path)
//   server       P0 outbox publisher (transactional emission)
//   shadow       B3 comparison dataset: same first_party path, labelled so
//                it can be diffed against the B0 active baseline and never
//                mixed into production reporting while comparing
//   ga4_export   rows re-imported from a GA4 BigQuery export (B8 reconciliation)

export const SIGNAL_SOURCES = ['first_party', 'server', 'shadow', 'ga4_export'] as const;
export type SignalSource = (typeof SIGNAL_SOURCES)[number];

export function isSignalSource(v: unknown): v is SignalSource {
  return typeof v === 'string' && (SIGNAL_SOURCES as readonly string[]).includes(v);
}

/**
 * What the browser SDK may declare. `server` and `ga4_export` are not
 * client-claimable: a browser can only ever be first_party or shadow data.
 */
export const CLIENT_SOURCES = ['first_party', 'shadow'] as const;
export type ClientSource = (typeof CLIENT_SOURCES)[number];
