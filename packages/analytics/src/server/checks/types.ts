// Nightly data checks (plan B5): every check is an EXPLICIT function over
// injected inputs — green path, red path with a concrete diagnostic, and a
// named owner for the alert. The evaluation criterion ("every nightly check
// has a green path, a red path, and an owner alert") is pinned by tests.

export type CheckId =
  | 'missing_expected_domain_events'
  | 'duplicate_event_ids'
  | 'unpublished_beyond_sla'
  | 'published_not_persisted'
  | 'quarantined_records'
  | 'transaction_count_divergence'
  | 'volume_swing'
  | 'freshness'
  | 'query_cost';

export const CHECK_IDS: readonly CheckId[] = [
  'missing_expected_domain_events',
  'duplicate_event_ids',
  'unpublished_beyond_sla',
  'published_not_persisted',
  'quarantined_records',
  'transaction_count_divergence',
  'volume_swing',
  'freshness',
  'query_cost',
] as const;

export type CheckStatus = 'green' | 'red';

export interface CheckResult {
  id: CheckId;
  status: CheckStatus;
  /** ISO timestamp of the run — the badge's "at T". */
  checkedAt: string;
  /** Empty on green; concrete findings on red (id/counts/ages, never vague). */
  diagnostics: string[];
  /** Numeric context for dashboards (counts, ages, bytes). */
  detail: Record<string, number>;
}

/** Alert routing — owners from the registry vocabulary, one per check. */
export const CHECK_OWNERS: Record<CheckId, string> = {
  missing_expected_domain_events: 'client_platform',
  duplicate_event_ids: 'client_platform',
  unpublished_beyond_sla: 'client_platform',
  published_not_persisted: 'client_platform',
  quarantined_records: 'client_platform',
  transaction_count_divergence: 'payments',
  volume_swing: 'growth',
  freshness: 'client_platform',
  query_cost: 'client_platform',
};

export function green(id: CheckId, checkedAt: string, detail: Record<string, number> = {}): CheckResult {
  return { id, status: 'green', checkedAt, diagnostics: [], detail };
}

export function red(
  id: CheckId,
  checkedAt: string,
  diagnostics: string[],
  detail: Record<string, number> = {},
): CheckResult {
  return { id, status: 'red', checkedAt, diagnostics, detail };
}
