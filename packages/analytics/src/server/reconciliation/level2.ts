// Level-2 reconciliation — analytics parity (plan B5, implementation bullet 4;
// architecture §7.3): authoritative transactions vs GA4-exported measurements.
//
// A MEASUREMENT comparison, never a replacement for Level-1 — Level-1 answers
// "did the truth survive the chain?" with exact IDs; Level-2 answers "how far
// did GA4 drift from truth?" with percentages, accounting for the reasons a
// healthy system still differs:
//   - consent: events without analytics consent are EXPECTED absent from GA4
//     (expectedGa4Count = round(authoritative × consentedGa4Fraction));
//   - duplicate suppression: both sides deduped by stable ID before counting;
//   - reporting delay: days inside the GA4 export lag are EXCLUDED, not
//     alerted on (a lagging export is not drift);
//   - transport: server → MP delivery is measured losslessly here; delivery
//     loss shows up as negative drift and deserves the alert.
// Breaches render an owner alert; the report never replaces Level-1 results.

export interface DatedIdRow {
  /** Day bucket, YYYY-MM-DD (UTC). */
  date: string;
  /** Stable ID: transaction reference (authoritative) / eventUniqueId (GA4). */
  id: string;
}

export interface Ga4ExportRow extends DatedIdRow {
  eventName: string;
}

export interface Level2Inputs {
  /** Half-open comparison window: since inclusive, until exclusive. */
  window: { since: string; until: string };
  /** Authoritative domain transactions (e.g., succeeded payments). */
  authoritative: DatedIdRow[];
  /** Raw GA4 BigQuery export rows (duplicates possible — deduped here). */
  ga4Export: Ga4ExportRow[];
  /** GA4 wire event being compared (server MP name, e.g. `purchase`). */
  ga4EventName: string;
  /** Fraction of authoritative events expected in GA4 after consent, 0..1. */
  consentedGa4Fraction: number;
  options?: {
    /** GA4 export lag; days within this many days of `now` are excluded. */
    reportingDelayDays?: number;
    nowMs?: number;
    warnPct?: number;
    alertPct?: number;
  };
}

export type DayStatus = 'ok' | 'warn' | 'alert' | 'excluded';

export interface Level2Day {
  date: string;
  authoritativeCount: number;
  ga4Count: number;
  expectedGa4Count: number;
  /** null only for excluded days. */
  driftPct: number | null;
  status: DayStatus;
}

export interface Level2Breach {
  date: string;
  driftPct: number;
  ga4Count: number;
  expectedGa4Count: number;
}

export interface Level2Report {
  window: Level2Inputs['window'];
  metric: string;
  days: Level2Day[];
  comparableDays: number;
  /** Window-level drift over comparable days; null when none comparable. */
  windowDriftPct: number | null;
  status: 'ok' | 'warn' | 'alert';
  breaches: Level2Breach[];
  /** Accounting trail: consent applied, duplicates suppressed, days excluded. */
  notes: string[];
}

const DAY_MS = 24 * 60 * 60 * 1000;

function dateKeys(since: string, until: string): string[] {
  const keys: string[] = [];
  let cursor = Date.parse(`${since}T00:00:00.000Z`);
  const end = Date.parse(`${until}T00:00:00.000Z`);
  if (Number.isNaN(cursor) || Number.isNaN(end)) {
    throw new Error(`level2: invalid window "${since}" → "${until}"`);
  }
  while (cursor < end) {
    keys.push(new Date(cursor).toISOString().slice(0, 10));
    cursor += DAY_MS;
  }
  return keys;
}

function countByDate(rows: DatedIdRow[], match?: (row: DatedIdRow) => boolean): Map<string, Set<string>> {
  const byDate = new Map<string, Set<string>>();
  for (const row of rows) {
    if (match && !match(row)) continue;
    let ids = byDate.get(row.date);
    if (!ids) {
      ids = new Set();
      byDate.set(row.date, ids);
    }
    ids.add(row.id); // duplicate suppression: same ID twice counts once
  }
  return byDate;
}

export function computeLevel2Parity(input: Level2Inputs): Level2Report {
  if (input.consentedGa4Fraction < 0 || input.consentedGa4Fraction > 1) {
    throw new Error(`level2: consentedGa4Fraction must be within 0..1, got ${input.consentedGa4Fraction}`);
  }
  const options = input.options ?? {};
  const warnPct = options.warnPct ?? 5;
  const alertPct = options.alertPct ?? 10;
  const delayDays = options.reportingDelayDays ?? 2;
  const nowMs = options.nowMs ?? Date.now();

  const authoritativeByDate = countByDate(input.authoritative);
  const ga4RowsForEvent = input.ga4Export.filter((row) => row.eventName === input.ga4EventName);
  const ga4ByDate = countByDate(ga4RowsForEvent);

  const suppressedDuplicates =
    ga4RowsForEvent.length -
    [...ga4ByDate.values()].reduce((sum, ids) => sum + ids.size, 0);

  const excludeAfterMs = nowMs - delayDays * DAY_MS;
  const notes: string[] = [
    `consent: expected GA4 = round(authoritative × ${input.consentedGa4Fraction})`,
    `duplicate suppression: ${suppressedDuplicates} GA4 rows collapsed by eventUniqueId`,
  ];

  const days: Level2Day[] = [];
  let sumAuthoritative = 0;
  let sumGa4 = 0;
  let sumExpected = 0;
  const breaches: Level2Breach[] = [];
  let excludedDays = 0;

  for (const date of dateKeys(input.window.since, input.window.until)) {
    const authoritativeCount = authoritativeByDate.get(date)?.size ?? 0;
    const ga4Count = ga4ByDate.get(date)?.size ?? 0;
    const expectedGa4Count = Math.round(authoritativeCount * input.consentedGa4Fraction);
    const dayMs = Date.parse(`${date}T00:00:00.000Z`);

    if (dayMs > excludeAfterMs) {
      excludedDays += 1;
      days.push({ date, authoritativeCount, ga4Count, expectedGa4Count, driftPct: null, status: 'excluded' });
      continue;
    }

    const driftPct =
      expectedGa4Count > 0
        ? ((ga4Count - expectedGa4Count) / expectedGa4Count) * 100
        : ga4Count > 0
          ? 100 // surplus where none was expected — capped, still an alert
          : 0;
    const magnitude = Math.abs(driftPct);
    const status: DayStatus =
      magnitude >= alertPct ? 'alert' : magnitude >= warnPct ? 'warn' : 'ok';

    if (status === 'alert') {
      breaches.push({ date, driftPct, ga4Count, expectedGa4Count });
    }
    sumAuthoritative += authoritativeCount;
    sumGa4 += ga4Count;
    sumExpected += expectedGa4Count;
    days.push({ date, authoritativeCount, ga4Count, expectedGa4Count, driftPct, status });
  }

  if (excludedDays > 0) {
    notes.push(`reporting delay: ${excludedDays} day(s) excluded inside ${delayDays}-day export lag`);
  }

  const comparableDays = days.filter((day) => day.status !== 'excluded').length;
  const windowDriftPct =
    comparableDays === 0
      ? null
      : sumExpected > 0
        ? ((sumGa4 - sumExpected) / sumExpected) * 100
        : sumGa4 > 0
          ? 100
          : 0;

  const status: Level2Report['status'] =
    breaches.length > 0 || (windowDriftPct !== null && Math.abs(windowDriftPct) >= alertPct)
      ? 'alert'
      : windowDriftPct !== null && Math.abs(windowDriftPct) >= warnPct
        ? 'warn'
        : 'ok';

  return {
    window: input.window,
    metric: input.ga4EventName,
    days,
    comparableDays,
    windowDriftPct,
    status,
    breaches,
    notes,
  };
}

export interface Level2Alert {
  owner: string;
  title: string;
  breaches: Level2Breach[];
  windowDriftPct: number | null;
  runbook: string;
  severity: 'page';
}

export const LEVEL2_OWNER = 'growth';
export const LEVEL2_RUNBOOK =
  'Level-2 is a measurement comparison — verify Level-1 first (docs/analytics/signals-platform-plan.md §B5), then check consent fraction, MP receipts, and GA4 export lag before treating drift as loss.';

export function renderLevel2Alert(report: Level2Report): Level2Alert | null {
  if (report.status !== 'alert') return null;
  return {
    owner: LEVEL2_OWNER,
    title: `[signals] Level-2 parity breach: ${report.metric} drift`,
    breaches: report.breaches,
    windowDriftPct: report.windowDriftPct,
    runbook: LEVEL2_RUNBOOK,
    severity: 'page',
  };
}
