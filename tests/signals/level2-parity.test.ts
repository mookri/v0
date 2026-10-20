// B5 acceptance — Level-2 parity report on a staging week (plan §B5):
// drift % computed against the consent-adjusted expectation, duplicate
// GA4 export rows suppressed, export-lag days excluded, threshold breach
// renders an owner alert — and the measurement never replaces Level-1.

import { describe, expect, it } from 'vitest';
import { reconcileLevel1 } from '@navdhya/analytics/src/server/reconciliation/level1';
import {
  computeLevel2Parity,
  renderLevel2Alert,
  type DatedIdRow,
  type Ga4ExportRow,
} from '@navdhya/analytics/src/server/reconciliation/level2';

const WEEK = { since: '2026-10-05', until: '2026-10-12' }; // Mon–Sun staging week
const NOW = Date.parse('2026-10-12T00:00:00.000Z');
const DAYS = ['2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08', '2026-10-09', '2026-10-10', '2026-10-11'];

function authoritative(): DatedIdRow[] {
  // 12 succeeded payments per day, stable transaction IDs.
  return DAYS.flatMap((date, d) =>
    Array.from({ length: 12 }, (_, i) => ({ date, id: `tx_${d}_${i}` })),
  );
}

/** GA4 export: 10 purchases/day (consent 0.85 → expect round(12×0.85)=10), last day lagging. */
function ga4Export(): Ga4ExportRow[] {
  const rows = DAYS.slice(0, 6).flatMap((date, d) =>
    Array.from({ length: 10 }, (_, i) => ({
      date,
      id: `ga4_${d}_${i}`,
      eventName: 'purchase',
    })),
  );
  // Duplicate export rows for one transaction (export redelivery):
  rows.push(
    { date: '2026-10-07', id: 'ga4_2_3', eventName: 'purchase' },
    { date: '2026-10-07', id: 'ga4_2_3', eventName: 'purchase' },
  );
  // A browser event name that is NOT the compared metric:
  rows.push({ date: '2026-10-07', id: 'pv_1', eventName: 'purchase_viewed' });
  return rows;
}

function stagingWeekReport(overrides: Partial<Parameters<typeof computeLevel2Parity>[0]> = {}) {
  return computeLevel2Parity({
    window: WEEK,
    authoritative: authoritative(),
    ga4Export: ga4Export(),
    ga4EventName: 'purchase',
    consentedGa4Fraction: 0.85,
    options: { nowMs: NOW, reportingDelayDays: 2, warnPct: 5, alertPct: 10 },
    ...overrides,
  });
}

describe('level-2 parity acceptance (B5)', () => {
  it('staging week: drift % computed per day, export lag excluded, duplicates suppressed', () => {
    const report = stagingWeekReport();

    // Oct 11 sits inside the 1-day export lag → excluded, not alerted.
    const lagDay = report.days.find((day) => day.date === '2026-10-11')!;
    expect(lagDay.status).toBe('excluded');
    expect(lagDay.driftPct).toBeNull();
    expect(report.comparableDays).toBe(6);

    // Oct 7 has 12 authoritative, 10 unique GA4 purchases (+2 duplicate rows
    // collapsed, +1 purchase_viewed ignored) → expected round(12×.85)=10 → 0%.
    const day3 = report.days.find((day) => day.date === '2026-10-07')!;
    expect(day3).toMatchObject({ authoritativeCount: 12, ga4Count: 10, expectedGa4Count: 10, driftPct: 0 });

    expect(report.windowDriftPct).toBe(0);
    expect(report.status).toBe('ok');
    expect(renderLevel2Alert(report)).toBeNull();
    expect(report.notes.join(' ')).toContain('2 GA4 rows collapsed');
    expect(report.notes.join(' ')).toContain('export lag');
  });

  it('threshold breach: a day losing the export → alert-level drift + owner alert', () => {
    const rows = ga4Export().filter((row) => !(row.date === '2026-10-09' && row.eventName === 'purchase'));
    const report = stagingWeekReport({ ga4Export: rows });

    const brokenDay = report.days.find((day) => day.date === '2026-10-09')!;
    expect(brokenDay.status).toBe('alert');
    expect(brokenDay.ga4Count).toBe(0);
    expect(brokenDay.expectedGa4Count).toBe(10);
    expect(brokenDay.driftPct).toBe(-100);
    expect(report.status).toBe('alert');
    expect(report.breaches.map((b) => b.date)).toEqual(['2026-10-09']);

    const alert = renderLevel2Alert(report)!;
    expect(alert.owner).toBe('growth');
    expect(alert.severity).toBe('page');
    expect(alert.windowDriftPct).toBeCloseTo(-16.67, 1); // 50 of 60 expected across 6 days
    expect(alert.runbook).toContain('Level-1');
  });

  it('Level-2 is measurement only — Level-1 results stand on their own inputs', () => {
    const committed = ['a', 'b', 'c'];
    const level1 = reconcileLevel1({
      committedIds: committed,
      publishedIds: committed,
      persistedIds: committed,
    });
    expect(level1.ok).toBe(true);

    // A Level-2 breach changes nothing about the Level-1 verdict:
    const rows = ga4Export().filter((row) => row.eventName !== 'purchase');
    const report = stagingWeekReport({ ga4Export: rows });
    expect(report.status).toBe('alert');
    expect(reconcileLevel1({ committedIds: committed, publishedIds: committed, persistedIds: committed }).ok).toBe(
      level1.ok,
    );
  });
});
