// Data-trust badge (plan B5 evaluation criterion): the badge means
// "specified checks passed at timestamp T" — it is NEVER a completeness
// claim about GA4 or any export. The statement strings are pinned by test.

import type { CheckId, CheckResult } from './types';

export interface DataTrustBadge {
  status: 'green' | 'red';
  /** Set ONLY when every specified check ran green; null otherwise. */
  passedAt: string | null;
  failedChecks: CheckId[];
  /** Exact human statement — no export-completeness language, ever. */
  statement: string;
}

export function computeDataTrust(results: CheckResult[], nowIso: string): DataTrustBadge {
  const failedChecks = results
    .filter((result) => result.status === 'red')
    .map((result) => result.id);

  if (failedChecks.length === 0) {
    return {
      status: 'green',
      passedAt: nowIso,
      failedChecks: [],
      statement: `Specified checks passed at ${nowIso}.`,
    };
  }
  return {
    status: 'red',
    passedAt: null,
    failedChecks,
    statement:
      `Specified checks FAILED at ${nowIso}: ${failedChecks.join(', ')}. ` +
      'This badge reports check status only — not GA4 or export completeness.',
  };
}
