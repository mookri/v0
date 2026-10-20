// Versioned retention matrix (plan B7, implementation bullet 1): the single
// canonical record of how long each class of signal data lives, who owns the
// decision, and WHERE it is enforced. B5's DDL and B7's pruning jobs both
// reference this matrix — it supersedes the B5 "baseline pending B7" note.
//
// Honesty notes:
//   - "DPDP-ready" is a legal-review outcome, not an engineering label
//     (plan B7 bullet 5). The durations below are ENGINEERING defaults a
//     reviewer can change here; the legal workstream owns the final values.
//   - Erasure tokens and deletion audit records are retained LONGER than
//     the data they govern on purpose: they are the proof and the
//     suppression authority, not the subject's behavioural data.

/** Bump on any duration/authority change — recorded in CHANGELOG. */
export const RETENTION_MATRIX_VERSION = '1.1.0';
export const RETENTION_MATRIX_EFFECTIVE_FROM = '2026-10-13';

/** Registry `retentionClass` → days. Raw-dataset partition pruning jobs
 *  and the warehouse DDL ceiling derive from this map. */
export const RETENTION_CLASS_DAYS: Record<string, number> = {
  raw_30d: 30,
  raw_90d: 90,
  audit_long: 400,
};

export interface RetentionDomain {
  domain: string;
  /** Retention in days, or a policy literal ('class-driven', 'indefinite'). */
  retentionDays: number | 'class-driven' | 'indefinite';
  authority: string;
  /** Where deletion actually happens — a view filter is NOT enforcement. */
  enforcement: string;
  notes: string;
}

export const RETENTION_MATRIX: readonly RetentionDomain[] = [
  {
    domain: 'private_collection_stream',
    retentionDays: 'class-driven',
    authority: 'registry retentionClass per event (raw_30d / raw_90d / audit_long)',
    enforcement:
      'BigQuery signals_raw.events partition expiration (400d ceiling) + per-class pruning jobs over event names',
    notes: 'First-party + server + shadow streams share the table; class membership comes from the registry, never from a copy.',
  },
  {
    domain: 'ga4_data',
    retentionDays: 400,
    authority: 'GA4 property user-data retention setting (console-managed, max 14 months on GA4)',
    enforcement: 'GA4 console setting + the GA4 user-deletion API for subject requests',
    notes: 'Google-managed namespace; engineering files deletion requests, GA4 enforces retention.',
  },
  {
    domain: 'exported_datasets',
    retentionDays: 400,
    authority: RETENTION_MATRIX_VERSION,
    enforcement: 'signals_ga4.events_typed view window + signals_raw partition expiration',
    notes: 'Derived data never outlives its source window.',
  },
  {
    domain: 'event_audit_records',
    retentionDays: 400,
    authority: 'audit_long retention class',
    enforcement: 'signals_raw.events partition expiration (400d ceiling)',
    notes: 'Domain-event audit trail behind Level-1 reconciliation.',
  },
  {
    domain: 'experiment_data',
    retentionDays: 400,
    authority: 'experiment ledger (B8) inherits audit_long unless a shorter legal value lands',
    enforcement: 'experiment ledger storage TTL',
    notes: 'Exposure records are pseudonymous; no raw identifiers.',
  },
  {
    domain: 'messaging_identifiers',
    retentionDays: 90,
    authority: 'FCM/audience adapter owner',
    enforcement: 'audience sync prunes tokens idle > 90d; deletion/withdrawal removes immediately',
    notes: 'A live token is operational data — purged on request well before retention.',
  },
  {
    domain: 'consent_records',
    retentionDays: 1095,
    authority: 'legal review workstream (engineering default: 3 years)',
    enforcement: 'consent ledger storage TTL',
    notes: 'Retained beyond the data they govern — consent proof must outlive the events.',
  },
  {
    domain: 'erasure_tokens',
    retentionDays: 'indefinite',
    authority: 'privacy engineering',
    enforcement: 'never pruned — the erasure-token table is the authoritative suppression list',
    notes: 'Deleting a token would resurrect the subject on the next projection rebuild.',
  },
  {
    // Added with the B8 crash subsystem (matrix v1.1.0).
    domain: 'crash_events',
    retentionDays: 180,
    authority: 'crash subsystem owners (privacy engineering reviews free-text retention)',
    enforcement: 'signals_crash.errors / signals_crash.sessions partition_expiration_days = 180',
    notes: 'Stack traces + redacted free text; restricted dataset, web instrumentation only.',
  },
  {
    domain: 'deletion_audit_records',
    retentionDays: 1095,
    authority: 'legal review workstream (engineering default: 3 years)',
    enforcement: 'deletion_audit table TTL',
    notes: 'Proof per system per request — the record a regulator asks for.',
  },
];

/** Days for a registry retention class; throws for unknown classes so a new
 *  class cannot silently skip retention. */
export function retentionDaysForClass(retentionClass: string): number {
  const days = RETENTION_CLASS_DAYS[retentionClass];
  if (days === undefined) {
    throw new Error(`retention class '${retentionClass}' has no matrix entry — extend RETENTION_CLASS_DAYS`);
  }
  return days;
}

/** Every domain listed in plan B7 bullet 1 must be present. */
export const REQUIRED_DOMAINS: readonly string[] = [
  'private_collection_stream',
  'ga4_data',
  'exported_datasets',
  'event_audit_records',
  'experiment_data',
  'messaging_identifiers',
  'consent_records',
];
