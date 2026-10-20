// The nine nightly checks (plan B5 implementation bullet 5). Each check is
// pure: injected inputs → CheckResult with an expected diagnostic on red.
// Data comes from the documented bq/outbox exports (tooling/scripts/
// warehouse-nightly.ts); no check touches a live system.

import { reconcileLevel1 } from '@navdhya/analytics/src/server/reconciliation/level1';
import { green, red, type CheckResult } from './types';

/** Cap diagnostics per check so an alert stays readable. */
const MAX_DIAGNOSTICS = 20;

function cap(diagnostics: string[]): string[] {
  if (diagnostics.length <= MAX_DIAGNOSTICS) return diagnostics;
  return [
    ...diagnostics.slice(0, MAX_DIAGNOSTICS),
    `… ${diagnostics.length - MAX_DIAGNOSTICS} more`,
  ];
}

// 1 ─ missing expected domain events ---------------------------------------
// Expected counts implied by authoritative activity vs what signals_raw has.
export interface MissingExpectedInput {
  expected: Record<string, number>;
  observed: Record<string, number>;
}

export function checkMissingExpectedDomainEvents(
  input: MissingExpectedInput,
  checkedAt: string,
): CheckResult {
  const diagnostics: string[] = [];
  let missing = 0;
  for (const [name, expected] of Object.entries(input.expected)) {
    const observed = input.observed[name] ?? 0;
    if (observed < expected) {
      missing += expected - observed;
      diagnostics.push(`${name}: expected ${expected} observed ${observed} (missing ${expected - observed})`);
    }
  }
  return diagnostics.length === 0
    ? green('missing_expected_domain_events', checkedAt, { missing })
    : red('missing_expected_domain_events', checkedAt, cap(diagnostics), { missing });
}

// 2 ─ duplicate event IDs ----------------------------------------------------
// The same envelope ULID persisted more than once (insertId dedup violated).
export function checkDuplicateEventIds(
  input: { persistedIds: string[] },
  checkedAt: string,
): CheckResult {
  const counts = new Map<string, number>();
  for (const id of input.persistedIds) counts.set(id, (counts.get(id) ?? 0) + 1);
  const diagnostics = [...counts.entries()]
    .filter(([, n]) => n > 1)
    .map(([id, n]) => `${id} persisted ${n} times`);
  return diagnostics.length === 0
    ? green('duplicate_event_ids', checkedAt, { rows: input.persistedIds.length })
    : red('duplicate_event_ids', checkedAt, cap(diagnostics), {
        duplicates: diagnostics.length,
      });
}

// 3 ─ unpublished beyond SLA -------------------------------------------------
// Committed outbox records neither published nor dead-lettered, older than SLA.
export interface UnpublishedRecord {
  id: string;
  type: string;
  occurredAt: string;
}

export function checkUnpublishedBeyondSla(
  input: { records: UnpublishedRecord[]; nowMs: number; slaMs: number },
  checkedAt: string,
): CheckResult {
  const diagnostics: string[] = [];
  let oldestAgeMs = 0;
  for (const record of input.records) {
    const ageMs = input.nowMs - Date.parse(record.occurredAt);
    if (ageMs > input.slaMs) {
      oldestAgeMs = Math.max(oldestAgeMs, ageMs);
      diagnostics.push(
        `${record.id} (${record.type}) unpublished after ${Math.round(ageMs / 1000)}s — SLA ${Math.round(input.slaMs / 1000)}s`,
      );
    }
  }
  return diagnostics.length === 0
    ? green('unpublished_beyond_sla', checkedAt, { pending: input.records.length })
    : red('unpublished_beyond_sla', checkedAt, cap(diagnostics), {
        overdue: diagnostics.length,
        oldestAgeMs,
      });
}

// 4 ─ published (or committed) but not persisted -----------------------------
// Delegates to the Level-1 diff — one ID space across Postgres and BigQuery.
export interface PublishedNotPersistedInput {
  committedIds: string[];
  publishedIds: string[];
  persistedIds: string[];
}

export function checkPublishedNotPersisted(
  input: PublishedNotPersistedInput,
  checkedAt: string,
): CheckResult {
  const report = reconcileLevel1(input);
  if (report.missingPersisted.length === 0) {
    return green('published_not_persisted', checkedAt, {
      committed: report.counts.committed,
      persisted: report.counts.persisted,
    });
  }
  return red(
    'published_not_persisted',
    checkedAt,
    cap(report.missingPersisted.map((id) => `${id} never reached signals_raw`)),
    { missing: report.missingPersisted.length },
  );
}

// 5 ─ schema-invalid / quarantined records -----------------------------------
export function checkQuarantinedRecords(
  input: { quarantined: Array<{ id: string; reason: string }> },
  checkedAt: string,
): CheckResult {
  if (input.quarantined.length === 0) {
    return green('quarantined_records', checkedAt, { quarantined: 0 });
  }
  return red(
    'quarantined_records',
    checkedAt,
    cap(input.quarantined.map((row) => `${row.id}: ${row.reason}`)),
    { quarantined: input.quarantined.length },
  );
}

// 6 ─ booking / payment / refund count divergence ----------------------------
// Authoritative domain counts vs the analytics events that mirror them.
export interface DivergencePair {
  label: string;
  authoritative: number;
  events: number;
}

export function checkTransactionCountDivergence(
  input: { pairs: DivergencePair[]; tolerance?: number },
  checkedAt: string,
): CheckResult {
  const tolerance = input.tolerance ?? 0;
  const diagnostics: string[] = [];
  let maxDelta = 0;
  for (const pair of input.pairs) {
    const delta = Math.abs(pair.authoritative - pair.events);
    maxDelta = Math.max(maxDelta, delta);
    if (delta > tolerance) {
      diagnostics.push(
        `${pair.label}: authoritative ${pair.authoritative} events ${pair.events} (Δ${delta} > tolerance ${tolerance})`,
      );
    }
  }
  return diagnostics.length === 0
    ? green('transaction_count_divergence', checkedAt, { maxDelta })
    : red('transaction_count_divergence', checkedAt, diagnostics, { maxDelta });
}

// 7 ─ per-family volume swings ----------------------------------------------
export interface VolumeFamily {
  family: string;
  current: number;
  baseline: number;
}

export function checkVolumeSwing(
  input: { families: VolumeFamily[]; maxSwingPct: number },
  checkedAt: string,
): CheckResult {
  const diagnostics: string[] = [];
  let maxSwing = 0;
  for (const family of input.families) {
    if (family.baseline === 0) {
      if (family.current > 0) {
        diagnostics.push(
          `${family.family}: appeared from zero baseline (current ${family.current})`,
        );
        maxSwing = Number.POSITIVE_INFINITY;
      }
      continue;
    }
    const swingPct = Math.abs(family.current - family.baseline) / family.baseline * 100;
    maxSwing = Math.max(maxSwing, swingPct);
    if (swingPct > input.maxSwingPct) {
      diagnostics.push(
        `${family.family}: current ${family.current} baseline ${family.baseline} swing ${swingPct.toFixed(1)}% > ${input.maxSwingPct}%`,
      );
    }
  }
  return diagnostics.length === 0
    ? green('volume_swing', checkedAt, { maxSwingPct: maxSwing })
    : red('volume_swing', checkedAt, cap(diagnostics), { families: diagnostics.length });
}

// 8 ─ freshness --------------------------------------------------------------
export function checkFreshness(
  input: { newestPersistedAt: string | null; nowMs: number; maxAgeMs: number },
  checkedAt: string,
): CheckResult {
  if (input.newestPersistedAt === null) {
    return red(
      'freshness',
      checkedAt,
      ['no rows persisted in the window — pipeline stale at the root'],
      { ageMs: -1 },
    );
  }
  const ageMs = input.nowMs - Date.parse(input.newestPersistedAt);
  if (ageMs > input.maxAgeMs) {
    return red(
      'freshness',
      checkedAt,
      [`newest persisted row is ${Math.round(ageMs / 60_000)}min old — freshness budget ${Math.round(input.maxAgeMs / 60_000)}min`],
      { ageMs },
    );
  }
  return green('freshness', checkedAt, { ageMs: Math.max(0, ageMs) });
}

// 9 ─ query cost -------------------------------------------------------------
export function checkQueryCost(
  input: { runs: Array<{ name: string; estimatedBytes: number }>; budgetBytes: number },
  checkedAt: string,
): CheckResult {
  const diagnostics = input.runs
    .filter((run) => run.estimatedBytes > input.budgetBytes)
    .map(
      (run) =>
        `${run.name}: estimated ${run.estimatedBytes} bytes exceeds budget ${input.budgetBytes} bytes`,
    );
  const total = input.runs.reduce((sum, run) => sum + run.estimatedBytes, 0);
  return diagnostics.length === 0
    ? green('query_cost', checkedAt, { totalBytes: total })
    : red('query_cost', checkedAt, diagnostics, { totalBytes: total });
}
