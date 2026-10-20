// Nightly checks (plan B5): green path, red path with the expected
// diagnostic, owner + runbook for every check, and the data-trust statement
// ("specified checks passed at T" — never an export-completeness claim).

import { describe, expect, it } from 'vitest';
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
} from '@navdhya/analytics/src/server/checks/checks';
import { renderOwnerAlert, CHECK_RUNBOOKS } from '@navdhya/analytics/src/server/checks/alerts';
import { computeDataTrust } from '@navdhya/analytics/src/server/checks/data-trust';
import { CHECK_IDS, CHECK_OWNERS } from '@navdhya/analytics/src/server/checks/types';

const AT = '2026-10-13T00:00:00.000Z';
const NOW = Date.parse(AT);

describe('nightly checks (B5)', () => {
  it('missing expected domain events: green when observed ≥ expected, red naming the gap', () => {
    expect(
      checkMissingExpectedDomainEvents(
        { expected: { payment_succeeded: 10, booking_confirmed: 4 }, observed: { payment_succeeded: 12, booking_confirmed: 4 } },
        AT,
      ),
    ).toMatchObject({ status: 'green' });

    const red = checkMissingExpectedDomainEvents(
      { expected: { payment_succeeded: 10 }, observed: { payment_succeeded: 7 } },
      AT,
    );
    expect(red.status).toBe('red');
    expect(red.diagnostics).toEqual(['payment_succeeded: expected 10 observed 7 (missing 3)']);
  });

  it('duplicate event IDs: red naming the duplicated IDs', () => {
    expect(checkDuplicateEventIds({ persistedIds: ['a', 'b'] }, AT).status).toBe('green');
    const red = checkDuplicateEventIds({ persistedIds: ['a', 'b', 'a', 'a'] }, AT);
    expect(red.status).toBe('red');
    expect(red.diagnostics).toEqual(['a persisted 3 times']);
  });

  it('unpublished beyond SLA: red only for records older than the SLA', () => {
    const green = checkUnpublishedBeyondSla(
      {
        records: [{ id: 'u1', type: 'payment_succeeded', occurredAt: new Date(NOW - 60_000).toISOString() }],
        nowMs: NOW,
        slaMs: 300_000,
      },
      AT,
    );
    expect(green.status).toBe('green');

    const red = checkUnpublishedBeyondSla(
      {
        records: [{ id: 'u1', type: 'payment_succeeded', occurredAt: new Date(NOW - 600_000).toISOString() }],
        nowMs: NOW,
        slaMs: 300_000,
      },
      AT,
    );
    expect(red.status).toBe('red');
    expect(red.diagnostics[0]).toContain('u1 (payment_succeeded) unpublished after');
    expect(red.diagnostics[0]).toContain('SLA 300s');
  });

  it('published-not-persisted: red when a Level-1 ID never reached signals_raw', () => {
    expect(
      checkPublishedNotPersisted(
        { committedIds: ['e1', 'e2'], publishedIds: ['e1', 'e2'], persistedIds: ['e1', 'e2'] },
        AT,
      ).status,
    ).toBe('green');

    const red = checkPublishedNotPersisted(
      { committedIds: ['e1', 'e2'], publishedIds: ['e1', 'e2'], persistedIds: ['e1'] },
      AT,
    );
    expect(red.status).toBe('red');
    expect(red.diagnostics).toEqual(['e2 never reached signals_raw']);
  });

  it('quarantined records: red with id + reason', () => {
    expect(checkQuarantinedRecords({ quarantined: [] }, AT).status).toBe('green');
    const red = checkQuarantinedRecords(
      { quarantined: [{ id: 'e9', reason: 'missing required param page_type' }] },
      AT,
    );
    expect(red.status).toBe('red');
    expect(red.diagnostics).toEqual(['e9: missing required param page_type']);
  });

  it('transaction count divergence: booking/payment/refund pairs vs tolerance', () => {
    const green = checkTransactionCountDivergence(
      {
        pairs: [
          { label: 'bookings', authoritative: 8, events: 8 },
          { label: 'payments', authoritative: 10, events: 10 },
          { label: 'refunds', authoritative: 2, events: 2 },
        ],
      },
      AT,
    );
    expect(green.status).toBe('green');

    const red = checkTransactionCountDivergence(
      { pairs: [{ label: 'payments', authoritative: 10, events: 8 }] },
      AT,
    );
    expect(red.status).toBe('red');
    expect(red.diagnostics).toEqual(['payments: authoritative 10 events 8 (Δ2 > tolerance 0)']);
  });

  it('volume swing: within threshold green; beyond red; zero-baseline appearance red', () => {
    expect(
      checkVolumeSwing(
        { families: [{ family: 'commerce', current: 110, baseline: 100 }], maxSwingPct: 40 },
        AT,
      ).status,
    ).toBe('green');

    const swing = checkVolumeSwing(
      { families: [{ family: 'commerce', current: 50, baseline: 100 }], maxSwingPct: 40 },
      AT,
    );
    expect(swing.status).toBe('red');
    expect(swing.diagnostics[0]).toContain('swing 50.0% > 40%');

    const appeared = checkVolumeSwing(
      { families: [{ family: 'new_family', current: 5, baseline: 0 }], maxSwingPct: 40 },
      AT,
    );
    expect(appeared.status).toBe('red');
    expect(appeared.diagnostics[0]).toContain('appeared from zero baseline');
  });

  it('freshness: recent row green; stale or missing row red', () => {
    expect(
      checkFreshness({ newestPersistedAt: new Date(NOW - 60_000).toISOString(), nowMs: NOW, maxAgeMs: 900_000 }, AT).status,
    ).toBe('green');

    const stale = checkFreshness(
      { newestPersistedAt: new Date(NOW - 3_600_000).toISOString(), nowMs: NOW, maxAgeMs: 900_000 },
      AT,
    );
    expect(stale.status).toBe('red');
    expect(stale.diagnostics[0]).toContain('60min old — freshness budget 15min');

    expect(checkFreshness({ newestPersistedAt: null, nowMs: NOW, maxAgeMs: 900_000 }, AT).status).toBe('red');
  });

  it('query cost: within budget green; any over-budget run red', () => {
    expect(
      checkQueryCost(
        { runs: [{ name: 'funnels', estimatedBytes: 1024 }], budgetBytes: 4096 },
        AT,
      ).status,
    ).toBe('green');

    const red = checkQueryCost(
      { runs: [{ name: 'funnels', estimatedBytes: 8192 }], budgetBytes: 4096 },
      AT,
    );
    expect(red.status).toBe('red');
    expect(red.diagnostics).toEqual(['funnels: estimated 8192 bytes exceeds budget 4096 bytes']);
  });

  it('every check has an owner, a runbook, and an alert only when red', () => {
    for (const id of CHECK_IDS) {
      expect(CHECK_OWNERS[id], `owner missing for ${id}`).toBeTruthy();
      expect(CHECK_RUNBOOKS[id], `runbook missing for ${id}`).toBeTruthy();

      const greenResult = { id, status: 'green' as const, checkedAt: AT, diagnostics: [], detail: {} };
      expect(renderOwnerAlert(greenResult)).toBeNull();

      const redResult = { ...greenResult, status: 'red' as const, diagnostics: ['boom'] };
      const alert = renderOwnerAlert(redResult);
      expect(alert).toMatchObject({ check: id, owner: CHECK_OWNERS[id], severity: 'page', diagnostics: ['boom'] });
      expect(alert!.runbook.length).toBeGreaterThan(0);
    }
  });
});

describe('data-trust badge (B5)', () => {
  it('GREEN: statement is "specified checks passed at T" and claims nothing about GA4', () => {
    const badge = computeDataTrust(
      CHECK_IDS.map((id) => ({ id, status: 'green' as const, checkedAt: AT, diagnostics: [], detail: {} })),
      AT,
    );
    expect(badge).toEqual({
      status: 'green',
      passedAt: AT,
      failedChecks: [],
      statement: `Specified checks passed at ${AT}.`,
    });
    expect(badge.statement).not.toMatch(/GA4|complete/i);
  });

  it('RED: lists failed checks and explicitly disclaims export completeness', () => {
    const badge = computeDataTrust(
      [
        { id: 'freshness', status: 'red' as const, checkedAt: AT, diagnostics: ['stale'], detail: {} },
        { id: 'query_cost', status: 'red' as const, checkedAt: AT, diagnostics: ['over'], detail: {} },
      ],
      AT,
    );
    expect(badge.status).toBe('red');
    expect(badge.passedAt).toBeNull();
    expect(badge.failedChecks).toEqual(['freshness', 'query_cost']);
    expect(badge.statement).toContain('FAILED');
    expect(badge.statement).toContain('not GA4 or export completeness');
  });
});
