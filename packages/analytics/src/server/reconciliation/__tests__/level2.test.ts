// Level-2 analytics parity (plan B5 acceptance): staging-week drift %,
// consent expectation, duplicate suppression, reporting-delay exclusion,
// and threshold-breach alerts.

import { describe, expect, it } from 'vitest';
import {
  computeLevel2Parity,
  renderLevel2Alert,
  type DatedIdRow,
  type Ga4ExportRow,
} from '@navdhya/analytics/src/server/reconciliation/level2';

const WINDOW = { since: '2026-10-01', until: '2026-10-08' }; // 7 days
const NOW = Date.parse('2026-10-08T00:00:00.000Z');

function dayKey(day: number): string {
  return `2026-10-0${day}`;
}

/** Authoritative: 10 succeeded payments per day, stable transaction IDs. */
function authoritative(days: number[], perDay = 10): DatedIdRow[] {
  return days.flatMap((day) =>
    Array.from({ length: perDay }, (_, i) => ({ date: dayKey(day), id: `pay_${day}_${i}` })),
  );
}

/** GA4 export rows: `count` purchases per day with unique event IDs. */
function ga4(days: number[], count: number): Ga4ExportRow[] {
  return days.flatMap((day) =>
    Array.from({ length: count }, (_, i) => ({
      date: dayKey(day),
      id: `evt_${day}_${i}`,
      eventName: 'purchase',
    })),
  );
}

describe('level-2 parity (B5)', () => {
  it('staging week: consent-adjusted expectation, drift % computed, delay days excluded', () => {
    const report = computeLevel2Parity({
      window: WINDOW,
      authoritative: authoritative([1, 2, 3, 4, 5, 6, 7]),
      ga4Export: ga4([1, 2, 3, 4, 5, 6], 9), // 9 of 10 expected (consent 0.9); day 7 lagging
      ga4EventName: 'purchase',
      consentedGa4Fraction: 0.9,
      options: { nowMs: NOW, reportingDelayDays: 2 },
    });

    expect(report.comparableDays).toBe(6); // Oct 7 inside the export lag → excluded
    expect(report.days.find((d) => d.date === '2026-10-07')?.status).toBe('excluded');
    expect(report.days.find((d) => d.date === '2026-10-07')?.driftPct).toBeNull();

    for (const day of report.days.filter((d) => d.status !== 'excluded')) {
      expect(day.expectedGa4Count).toBe(9);
      expect(day.ga4Count).toBe(9);
      expect(day.driftPct).toBe(0);
      expect(day.status).toBe('ok');
    }
    expect(report.windowDriftPct).toBe(0);
    expect(report.status).toBe('ok');
    expect(renderLevel2Alert(report)).toBeNull();
    expect(report.notes.join(' ')).toContain('consent');
    expect(report.notes.join(' ')).toContain('export lag');
  });

  it('duplicate suppression: repeated eventUniqueIds count once', () => {
    const rows = ga4([1, 2, 3], 9);
    rows.push({ ...rows[0]! }, { ...rows[0]! }); // duplicate export rows
    const report = computeLevel2Parity({
      window: WINDOW,
      authoritative: authoritative([1, 2, 3]),
      ga4Export: rows,
      ga4EventName: 'purchase',
      consentedGa4Fraction: 0.9,
      options: { nowMs: NOW, reportingDelayDays: 0 },
    });
    expect(report.notes.join(' ')).toContain('2 GA4 rows collapsed');
    expect(report.status).toBe('ok'); // dedup absorbed the duplicates — no false drift
  });

  it('threshold breach: below-expectation export → alert-level drift + owner alert', () => {
    const report = computeLevel2Parity({
      window: WINDOW,
      authoritative: authoritative([1, 2, 3, 4, 5]),
      ga4Export: [...ga4([1, 2, 3, 4], 9), ...ga4([5], 6)], // day 5 lost three purchases
      ga4EventName: 'purchase',
      consentedGa4Fraction: 0.9,
      options: { nowMs: NOW, reportingDelayDays: 0, warnPct: 5, alertPct: 10 },
    });

    expect(report.status).toBe('alert');
    expect(report.breaches).toHaveLength(1);
    expect(report.breaches[0]!.date).toBe('2026-10-05');
    expect(report.breaches[0]!.driftPct).toBeCloseTo(-33.33, 1);
    expect(report.breaches[0]!.ga4Count).toBe(6);
    expect(report.breaches[0]!.expectedGa4Count).toBe(9);
    expect(report.windowDriftPct).toBeCloseTo(-6.67, 1); // 42 of 45 expected

    const alert = renderLevel2Alert(report);
    expect(alert).toMatchObject({ owner: 'growth', severity: 'page' });
    expect(alert!.breaches).toHaveLength(1);
    expect(alert!.runbook).toContain('Level-1');
  });

  it('threshold bands: alert on breach, warn without a breach', () => {
    // -10% per day with alertPct 10 → alert-level day + breach.
    const alertReport = computeLevel2Parity({
      window: WINDOW,
      authoritative: authoritative([1, 2]),
      ga4Export: ga4([1, 2], 9), // 9 vs 10 expected (fraction 1)
      ga4EventName: 'purchase',
      consentedGa4Fraction: 1,
      options: { nowMs: NOW, reportingDelayDays: 0, warnPct: 5, alertPct: 10 },
    });
    expect(alertReport.status).toBe('alert');
    expect(alertReport.breaches).toHaveLength(2);

    // Same -10% drift, alert band widened to 50 → warn only, no breach alert.
    const warnReport = computeLevel2Parity({
      window: WINDOW,
      authoritative: authoritative([1, 2]),
      ga4Export: [...ga4([1], 10), ...ga4([2], 9)],
      ga4EventName: 'purchase',
      consentedGa4Fraction: 1,
      options: { nowMs: NOW, reportingDelayDays: 0, warnPct: 5, alertPct: 50 },
    });
    expect(warnReport.status).toBe('warn');
    expect(warnReport.breaches).toHaveLength(0);
    expect(renderLevel2Alert(warnReport)).toBeNull();
  });

  it('surplus where consent expects none → alert', () => {
    const report = computeLevel2Parity({
      window: WINDOW,
      authoritative: authoritative([1]),
      ga4Export: ga4([1], 3),
      ga4EventName: 'purchase',
      consentedGa4Fraction: 0,
      options: { nowMs: NOW, reportingDelayDays: 0 },
    });
    expect(report.days[0]).toMatchObject({ expectedGa4Count: 0, ga4Count: 3, status: 'alert', driftPct: 100 });
    expect(report.status).toBe('alert');
  });

  it('validates the consent fraction', () => {
    expect(() =>
      computeLevel2Parity({
        window: WINDOW,
        authoritative: [],
        ga4Export: [],
        ga4EventName: 'purchase',
        consentedGa4Fraction: 1.5,
      }),
    ).toThrow(/within 0\.\.1/);
  });
});
