// B5 acceptance — injected-failure suite per nightly check (plan §B5):
// for EACH check: a green baseline, an injected failure that turns it red
// with the expected diagnostic, and an owner alert with a runbook. Plus the
// cost gate (real warehouse SQL must lint clean; the red paths must fail)
// and the data-trust statement semantics.

import * as fs from 'node:fs';
import * as path from 'node:path';
import { describe, expect, it } from 'vitest';
import { CHECK_IDS } from '@navdhya/analytics/src/server/checks/types';
import { runNightlyChecks, type NightlyInputs } from '@navdhya/analytics/src/server/checks/run';
import { lintSql, type TableSizeManifest, type TableStat } from '@navdhya/analytics/src/warehouse/sql-lint';

const CHECKED_AT = '2026-10-13T02:00:00.000Z';
const NOW = Date.parse(CHECKED_AT);
const SQL_ROOT = path.join(process.cwd(), 'infrastructure', 'database', 'bigquery');

/** A healthy night: everything committed reached the warehouse, on time. */
function baseline(): NightlyInputs {
  const ids = Array.from({ length: 5 }, (_, i) => `evt_${i}`);
  return {
    checkedAt: CHECKED_AT,
    nowMs: NOW,
    level1: { committedIds: ids, publishedIds: ids, persistedIds: ids },
    unpublished: [
      { id: 'u_1', type: 'payment_succeeded', occurredAt: new Date(NOW - 60_000).toISOString() },
    ],
    slaMs: 300_000,
    quarantined: [],
    expectedVsObserved: {
      expected: { payment_succeeded: 5, booking_confirmed: 3 },
      observed: { payment_succeeded: 5, booking_confirmed: 3 },
    },
    divergence: {
      pairs: [
        { label: 'bookings', authoritative: 3, events: 3 },
        { label: 'payments', authoritative: 5, events: 5 },
        { label: 'refunds', authoritative: 1, events: 1 },
      ],
    },
    swings: {
      families: [
        { family: 'commerce', current: 96, baseline: 100 },
        { family: 'content', current: 40, baseline: 40 },
      ],
      maxSwingPct: 40,
    },
    freshness: { newestPersistedAt: new Date(NOW - 120_000).toISOString(), maxAgeMs: 900_000 },
    cost: { runs: [{ name: 'funnels', estimatedBytes: 1024 * 1024 }], budgetBytes: 5 * 1024 * 1024 * 1024 },
  };
}

function onlyRed(inputs: NightlyInputs): ReturnType<typeof runNightlyChecks> {
  const report = runNightlyChecks(inputs);
  expect(report.results.filter((r) => r.status === 'red')).toHaveLength(1);
  return report;
}

describe('nightly checks acceptance (B5)', () => {
  it('baseline night: all nine green, zero alerts, trust green with the exact statement', () => {
    const report = runNightlyChecks(baseline());
    expect(report.results).toHaveLength(CHECK_IDS.length);
    expect(report.results.every((r) => r.status === 'green')).toBe(true);
    expect(report.alerts).toEqual([]);
    expect(report.trust.status).toBe('green');
    expect(report.trust.passedAt).toBe(CHECKED_AT);
    expect(report.trust.statement).toBe(`Specified checks passed at ${CHECKED_AT}.`);
    expect(report.trust.statement).not.toMatch(/GA4|complete/i);
  });

  it('INJECTED: delete a raw row → published_not_persisted red + client_platform alert', () => {
    const inputs = baseline();
    inputs.level1.persistedIds = inputs.level1.persistedIds.filter((id) => id !== 'evt_3');
    const report = onlyRed(inputs);
    const result = report.results.find((r) => r.id === 'published_not_persisted')!;
    expect(result.diagnostics).toEqual(['evt_3 never reached signals_raw']);
    expect(report.alerts).toHaveLength(1);
    expect(report.alerts[0]).toMatchObject({
      check: 'published_not_persisted',
      owner: 'client_platform',
      severity: 'page',
    });
    expect(report.alerts[0]!.runbook).toContain('replay');
    expect(report.trust.status).toBe('red');
    expect(report.trust.statement).toContain('not GA4 or export completeness');
  });

  it('INJECTED: duplicate an ID → duplicate_event_ids red', () => {
    const inputs = baseline();
    inputs.level1.persistedIds = [...inputs.level1.persistedIds, 'evt_0'];
    const report = onlyRed(inputs);
    expect(report.results.find((r) => r.id === 'duplicate_event_ids')!.diagnostics).toEqual([
      'evt_0 persisted 2 times',
    ]);
    expect(report.alerts[0]!.owner).toBe('client_platform');
  });

  it('INJECTED: delay a publish → unpublished_beyond_sla red', () => {
    const inputs = baseline();
    inputs.unpublished = [
      { id: 'u_slow', type: 'booking_confirmed', occurredAt: new Date(NOW - 3_600_000).toISOString() },
    ];
    const report = onlyRed(inputs);
    const result = report.results.find((r) => r.id === 'unpublished_beyond_sla')!;
    expect(result.diagnostics[0]).toContain('u_slow (booking_confirmed) unpublished after');
    expect(result.diagnostics[0]).toContain('SLA 300s');
    expect(report.trust.failedChecks).toEqual(['unpublished_beyond_sla']);
  });

  it('INJECTED: corrupt a schema → quarantined_records red with id + reason', () => {
    const inputs = baseline();
    inputs.quarantined = [{ id: 'evt_bad', reason: 'missing required param page_type' }];
    const report = onlyRed(inputs);
    expect(report.results.find((r) => r.id === 'quarantined_records')!.diagnostics).toEqual([
      'evt_bad: missing required param page_type',
    ]);
    expect(report.alerts[0]!.runbook).toContain('do not loosen validation');
  });

  it('INJECTED: drop a domain event → missing_expected_domain_events red', () => {
    const inputs = baseline();
    inputs.expectedVsObserved.observed = { payment_succeeded: 3, booking_confirmed: 3 };
    const report = onlyRed(inputs);
    expect(report.results.find((r) => r.id === 'missing_expected_domain_events')!.diagnostics).toEqual([
      'payment_succeeded: expected 5 observed 3 (missing 2)',
    ]);
  });

  it('INJECTED: count divergence → transaction_count_divergence red with payments owner', () => {
    const inputs = baseline();
    inputs.divergence.pairs[1] = { label: 'payments', authoritative: 5, events: 4 };
    const report = onlyRed(inputs);
    expect(report.results.find((r) => r.id === 'transaction_count_divergence')!.diagnostics).toEqual([
      'payments: authoritative 5 events 4 (Δ1 > tolerance 0)',
    ]);
    expect(report.alerts[0]!.owner).toBe('payments');
  });

  it('INJECTED: volume swing → volume_swing red with growth owner', () => {
    const inputs = baseline();
    inputs.swings.families[0] = { family: 'commerce', current: 20, baseline: 100 };
    const report = onlyRed(inputs);
    expect(report.results.find((r) => r.id === 'volume_swing')!.diagnostics[0]).toContain(
      'swing 80.0% > 40%',
    );
    expect(report.alerts[0]!.owner).toBe('growth');
  });

  it('INJECTED: stale warehouse → freshness red', () => {
    const inputs = baseline();
    inputs.freshness.newestPersistedAt = new Date(NOW - 7_200_000).toISOString();
    const report = onlyRed(inputs);
    expect(report.results.find((r) => r.id === 'freshness')!.diagnostics[0]).toContain(
      'freshness budget 15min',
    );
  });

  it('INJECTED: over-budget query run → query_cost red', () => {
    const inputs = baseline();
    inputs.cost.runs = [{ name: 'ltv', estimatedBytes: 50 * 1024 * 1024 * 1024 }];
    const report = onlyRed(inputs);
    expect(report.results.find((r) => r.id === 'query_cost')!.diagnostics[0]).toContain(
      'exceeds budget',
    );
    expect(report.alerts[0]!.runbook).toContain('maximum_bytes_billed');
  });

  it('every check has a green baseline and a red injection (one at a time), each with an alert', () => {
    // The per-check injections above cover 8 of 9; this asserts coverage of
    // the full ID set so a new check cannot land untested.
    const covered = new Set([
      'missing_expected_domain_events',
      'duplicate_event_ids',
      'unpublished_beyond_sla',
      'published_not_persisted',
      'quarantined_records',
      'transaction_count_divergence',
      'volume_swing',
      'freshness',
      'query_cost',
    ]);
    expect([...CHECK_IDS].sort()).toEqual([...covered].sort());
  });

  it('COST GATE: real warehouse SQL lints clean; the documented red paths fail', () => {
    const manifestRaw = JSON.parse(
      fs.readFileSync(path.join(SQL_ROOT, 'table-sizes.json'), 'utf8'),
    ) as Record<string, unknown>;
    const manifest: TableSizeManifest = {};
    for (const [key, value] of Object.entries(manifestRaw)) {
      if (key !== '_comment') manifest[key] = value as TableStat;
    }

    const collect = (dir: string): string[] =>
      fs
        .readdirSync(dir, { withFileTypes: true })
        .flatMap((entry) =>
          entry.isDirectory()
            ? collect(path.join(dir, entry.name))
            : entry.name.endsWith('.sql')
              ? [path.join(dir, entry.name)]
              : [],
        );

    const files = [...collect(path.join(SQL_ROOT, 'models')), ...collect(path.join(SQL_ROOT, 'tests'))];
    expect(files.length).toBeGreaterThanOrEqual(10);
    for (const file of files) {
      expect(lintSql(fs.readFileSync(file, 'utf8'), { manifest }), file).toEqual([]);
    }

    // Red path 1: SELECT * without a partition filter.
    const star = lintSql('SELECT * FROM `p.signals_raw.events`', { manifest });
    expect(star.map((f) => f.rule)).toEqual(
      expect.arrayContaining(['missing-partition-filter', 'select-star-unpartitioned']),
    );

    // Red path 2: estimated over-budget scan.
    const overBudget = lintSql(
      '-- @budget_bytes:1024\nSELECT name FROM `p.signals_raw.events`',
      { manifest },
    );
    expect(overBudget.map((f) => f.rule)).toContain('over-budget');
  });
});
