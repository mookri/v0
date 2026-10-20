// Nightly runner: one bundle of exported inputs → results + owner alerts +
// data-trust badge. The tooling script (warehouse-nightly.ts) feeds this from
// bq/outbox JSON exports; tests feed it fixtures and injected failures.

import {
  checkDuplicateEventIds,
  checkFreshness,
  checkMissingExpectedDomainEvents,
  checkPublishedNotPersisted,
  checkQueryCost,
  checkQuarantinedRecords,
  checkTransactionCountDivergence,
  checkUnpublishedBeyondSla,
  checkVolumeSwing,
  type DivergencePair,
  type UnpublishedRecord,
  type VolumeFamily,
} from './checks';
import { renderOwnerAlert, type OwnerAlert } from './alerts';
import { computeDataTrust, type DataTrustBadge } from './data-trust';
import type { CheckResult } from './types';

export interface NightlyInputs {
  /** ISO timestamp for every result — injected for determinism. */
  checkedAt: string;
  nowMs: number;
  /** Level-1 ID space: committed / published / persisted. */
  level1: { committedIds: string[]; publishedIds: string[]; persistedIds: string[] };
  /** Committed outbox records not published and not dead-lettered. */
  unpublished: UnpublishedRecord[];
  slaMs: number;
  quarantined: Array<{ id: string; reason: string }>;
  expectedVsObserved: { expected: Record<string, number>; observed: Record<string, number> };
  divergence: { pairs: DivergencePair[]; tolerance?: number };
  swings: { families: VolumeFamily[]; maxSwingPct: number };
  freshness: { newestPersistedAt: string | null; maxAgeMs: number };
  cost: { runs: Array<{ name: string; estimatedBytes: number }>; budgetBytes: number };
}

export interface NightlyReport {
  results: CheckResult[];
  alerts: OwnerAlert[];
  trust: DataTrustBadge;
}

export function runNightlyChecks(inputs: NightlyInputs): NightlyReport {
  const at = inputs.checkedAt;

  const results: CheckResult[] = [
    checkMissingExpectedDomainEvents(inputs.expectedVsObserved, at),
    checkDuplicateEventIds({ persistedIds: inputs.level1.persistedIds }, at),
    checkUnpublishedBeyondSla(
      { records: inputs.unpublished, nowMs: inputs.nowMs, slaMs: inputs.slaMs },
      at,
    ),
    checkPublishedNotPersisted(inputs.level1, at),
    checkQuarantinedRecords({ quarantined: inputs.quarantined }, at),
    checkTransactionCountDivergence(inputs.divergence, at),
    checkVolumeSwing(inputs.swings, at),
    checkFreshness(
      { ...inputs.freshness, nowMs: inputs.nowMs },
      at,
    ),
    checkQueryCost(inputs.cost, at),
  ];

  const alerts = results
    .map(renderOwnerAlert)
    .filter((alert): alert is OwnerAlert => alert !== null);

  return { results, alerts, trust: computeDataTrust(results, at) };
}
