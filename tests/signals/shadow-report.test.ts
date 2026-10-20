// B3 acceptance — shadow comparison report over a 24h window: counts, schema
// errors, and latency per event family versus the B0 active baseline, with
// out-of-window rows excluded.

import { describe, expect, it } from 'vitest';
import {
  compareShadowToBaseline,
  renderShadowReportMarkdown,
  type BaselineRowInput,
  type SchemaRejectionInput,
  type ShadowRowInput,
} from '@navdhya/analytics/src/server/shadow-report';

const SINCE = '2026-10-10T00:00:00.000Z';
const UNTIL = '2026-10-11T00:00:00.000Z';
const HOUR = 3_600_000;
const MINUTE = 60_000;

function iso(ms: number): string {
  return new Date(ms).toISOString();
}

function buildFixture() {
  const sinceMs = Date.parse(SINCE);
  const shadowRows: ShadowRowInput[] = [];
  const baselineRows: BaselineRowInput[] = [];

  for (let hour = 0; hour < 24; hour += 1) {
    const received = sinceMs + hour * HOUR + 10 * MINUTE;
    // page_view: 10/hour, indicative latency 2000ms
    for (let i = 0; i < 10; i += 1) {
      shadowRows.push({
        id: `EVT_pv_${hour}_${i}`,
        name: 'page_view',
        occurredAt: iso(received - 2_000),
        receivedAt: iso(received),
      });
    }
    // view_item 2/hour + checkout_started 1/hour + app_error 1/hour at 500ms
    for (const [name, count] of [
      ['view_item', 2],
      ['checkout_started', 1],
      ['app_error', 1],
    ] as Array<[string, number]>) {
      for (let i = 0; i < count; i += 1) {
        shadowRows.push({
          id: `EVT_${name}_${hour}_${i}`,
          name,
          occurredAt: iso(received - 500),
          receivedAt: iso(received),
        });
      }
    }
    // Baseline (B0 active instrumentation): 8 page_view, 2 view_item,
    // 1 checkout_started per hour — never saw app_error.
    for (const [name, count] of [
      ['page_view', 8],
      ['view_item', 2],
      ['checkout_started', 1],
    ] as Array<[string, number]>) {
      for (let i = 0; i < count; i += 1) {
        baselineRows.push({ name, at: iso(received + i) });
      }
    }
  }

  // Out-of-window noise that must be excluded from the report.
  shadowRows.push({ id: 'EVT_late', name: 'page_view', occurredAt: SINCE, receivedAt: iso(Date.parse(UNTIL) + HOUR) });
  baselineRows.push({ name: 'page_view', at: iso(sinceMs - HOUR) });

  const rejections: SchemaRejectionInput[] = [
    { name: 'unknown_event', errors: ['registry: unknown event "unknown_event"'] },
    { name: 'unknown_event', errors: ['registry: unknown event "unknown_event"'] },
    { name: 'unknown_event', errors: ['registry: unknown event "unknown_event"'] },
    { errors: ['envelope: session.seq required'] },
  ];

  return { shadowRows, baselineRows, rejections };
}

describe('shadow comparison report (B3)', () => {
  const report = compareShadowToBaseline({ window: { since: SINCE, until: UNTIL }, ...buildFixture() });

  it('covers exactly the 24h window and excludes out-of-window rows', () => {
    expect(report.window.hours).toBe(24);
    expect(report.totals).toEqual({ shadow: 336, baseline: 264, delta: 72 });
  });

  it('diffs per event with honest delta% (null when baseline never saw it)', () => {
    const pageView = report.perEvent.find((row) => row.event === 'page_view')!;
    expect(pageView).toMatchObject({ family: 'engagement', shadow: 240, baseline: 192, delta: 48 });
    expect(pageView.deltaPct).toBeCloseTo(0.25);

    const appError = report.perEvent.find((row) => row.event === 'app_error')!;
    expect(appError).toMatchObject({ family: 'errors', shadow: 24, baseline: 0 });
    expect(appError.deltaPct).toBeNull(); // nothing to divide by — no invented ratio
  });

  it('rolls counts up per event family', () => {
    expect(report.families).toEqual([
      { family: 'conversion', shadow: 24, baseline: 24, delta: 0 },
      { family: 'discovery', shadow: 48, baseline: 48, delta: 0 },
      { family: 'engagement', shadow: 240, baseline: 192, delta: 48 },
      { family: 'errors', shadow: 24, baseline: 0, delta: 24 },
    ]);
  });

  it('reports schema errors with samples', () => {
    expect(report.schemaErrors.total).toBe(4);
    expect(report.schemaErrors.byEvent[0]).toMatchObject({ name: '(unknown)', count: 1 });
    const unknown = report.schemaErrors.byEvent.find((row) => row.name === 'unknown_event')!;
    expect(unknown.count).toBe(3);
    expect(unknown.sampleErrors[0]).toContain('unknown_event');
  });

  it('reports indicative latency per family (client-clock caveat in notes)', () => {
    const engagement = report.latencyMs.find((row) => row.family === 'engagement')!;
    expect(engagement).toMatchObject({ count: 240, p50: 2_000, p95: 2_000 });
    const discovery = report.latencyMs.find((row) => row.family === 'discovery')!;
    expect(discovery).toMatchObject({ count: 48, p50: 500 });
    expect(report.notes.join(' ')).toContain('client clock');
  });

  it('renders a reviewable markdown artifact', () => {
    const markdown = renderShadowReportMarkdown(report);
    expect(markdown).toContain('# Signals shadow comparison — 24h window');
    expect(markdown).toContain('| page_view | engagement | 240 | 192 | 48 | 25.0% |');
    expect(markdown).toContain('Schema errors: 4');
  });

  it('rejects an invalid window instead of comparing nothing', () => {
    expect(() =>
      compareShadowToBaseline({ window: { since: UNTIL, until: SINCE }, shadowRows: [], baselineRows: [] }),
    ).toThrow(/invalid window/);
  });
});
