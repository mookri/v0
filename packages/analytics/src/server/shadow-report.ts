// packages/analytics/src/server/shadow-report.ts
//
// Shadow-vs-baseline comparison over a time window (plan B3 evaluation
// criterion: "shadow dataset comparable to the active baseline: counts,
// schema errors, latency per event family"). Pure computation over exported
// rows — no warehouse driver here (same posture as DATABASE_URL: drivers
// stay at the edges). The B0 active baseline is whatever the inventoried
// active instrumentation produced (ad pipeline, beacons, GA4 realtimes…);
// whoever runs the comparison exports those counts as `baselineRows`.
//
// Honesty note: `occurredAt` is client clock and untrusted for business
// truth — the latency figures are INDICATIVE (drift/skew included), good
// for shadow-vs-baseline deltas, never for SLOs of business truth.

import type { EventName } from '@navdhya/analytics/src/registry/generated/event-types';

export type EventFamily =
  | 'engagement'
  | 'discovery'
  | 'conversion'
  | 'identity'
  | 'errors'
  | 'ads'
  | 'business'
  | 'unknown';

/** Funnel families for "per event family" reporting (plan B3). */
export const FAMILY_BY_EVENT: Record<string, EventFamily> = {
  page_view: 'engagement',
  view_item: 'discovery',
  search: 'discovery',
  zero_results_search: 'discovery',
  checkout_started: 'conversion',
  begin_checkout: 'conversion',
  purchase_viewed: 'conversion',
  sign_up: 'identity',
  login: 'identity',
  kyc_submitted: 'identity',
  app_error: 'errors',
  ad_impression: 'ads',
  ad_click: 'ads',
  booking_confirmed: 'business',
  payment_succeeded: 'business',
  payment_failed: 'business',
};

export function familyOf(eventName: string): EventFamily {
  return FAMILY_BY_EVENT[eventName] ?? 'unknown';
}

export interface ShadowRowInput {
  id: string;
  name: string;
  /** Client clock — untrusted (see header). */
  occurredAt: string;
  /** Ingestion clock — the row's warehouse arrival. */
  receivedAt: string;
}

export interface BaselineRowInput {
  name: string;
  /** When the active baseline observed it. */
  at: string;
}

export interface SchemaRejectionInput {
  /** Event name when known (dead-letter records it). */
  name?: string;
  errors: string[];
}

export interface ShadowReportInputs {
  window: { since: string; until: string };
  shadowRows: ShadowRowInput[];
  baselineRows: BaselineRowInput[];
  rejections?: SchemaRejectionInput[];
}

export interface ShadowComparisonReport {
  window: { since: string; until: string; hours: number };
  totals: { shadow: number; baseline: number; delta: number };
  perEvent: Array<{
    event: string;
    family: EventFamily;
    shadow: number;
    baseline: number;
    delta: number;
    /** null when the baseline never saw the event (nothing to divide by). */
    deltaPct: number | null;
  }>;
  families: Array<{ family: EventFamily; shadow: number; baseline: number; delta: number }>;
  schemaErrors: {
    total: number;
    byEvent: Array<{ name: string; count: number; sampleErrors: string[] }>;
  };
  /** Indicative receivedAt − occurredAt (client clock caveat applies). */
  latencyMs: Array<{ family: EventFamily; count: number; p50: number; p95: number }>;
  notes: string[];
}

function percentileNearestRank(sorted: number[], p: number): number {
  if (sorted.length === 0) return 0;
  const rank = Math.max(1, Math.ceil((p / 100) * sorted.length));
  return sorted[Math.min(rank, sorted.length) - 1]!;
}

function within(iso: string, sinceMs: number, untilMs: number): boolean {
  const ms = Date.parse(iso);
  return !Number.isNaN(ms) && ms >= sinceMs && ms < untilMs;
}

export function compareShadowToBaseline(inputs: ShadowReportInputs): ShadowComparisonReport {
  const sinceMs = Date.parse(inputs.window.since);
  const untilMs = Date.parse(inputs.window.until);
  if (Number.isNaN(sinceMs) || Number.isNaN(untilMs) || untilMs <= sinceMs) {
    throw new Error('shadow-report: invalid window');
  }

  const shadow = inputs.shadowRows.filter((r) => within(r.receivedAt, sinceMs, untilMs));
  const baseline = inputs.baselineRows.filter((r) => within(r.at, sinceMs, untilMs));

  const shadowByEvent = new Map<string, number>();
  for (const row of shadow) shadowByEvent.set(row.name, (shadowByEvent.get(row.name) ?? 0) + 1);
  const baselineByEvent = new Map<string, number>();
  for (const row of baseline) baselineByEvent.set(row.name, (baselineByEvent.get(row.name) ?? 0) + 1);

  const perEvent = [...new Set([...shadowByEvent.keys(), ...baselineByEvent.keys()])]
    .sort()
    .map((event) => {
      const s = shadowByEvent.get(event) ?? 0;
      const b = baselineByEvent.get(event) ?? 0;
      return {
        event,
        family: familyOf(event),
        shadow: s,
        baseline: b,
        delta: s - b,
        deltaPct: b > 0 ? (s - b) / b : null,
      };
    });

  const familyTotals = new Map<EventFamily, { shadow: number; baseline: number }>();
  for (const row of perEvent) {
    const bucket = familyTotals.get(row.family) ?? { shadow: 0, baseline: 0 };
    bucket.shadow += row.shadow;
    bucket.baseline += row.baseline;
    familyTotals.set(row.family, bucket);
  }
  const families = [...familyTotals.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([family, totals]) => ({ family, shadow: totals.shadow, baseline: totals.baseline, delta: totals.shadow - totals.baseline }));

  // Latency per family (indicative): received − occurred, shadow side only.
  const latencyByFamily = new Map<EventFamily, number[]>();
  for (const row of shadow) {
    const occurred = Date.parse(row.occurredAt);
    const received = Date.parse(row.receivedAt);
    if (Number.isNaN(occurred) || Number.isNaN(received)) continue;
    const family = familyOf(row.name);
    const list = latencyByFamily.get(family) ?? [];
    list.push(received - occurred);
    latencyByFamily.set(family, list);
  }
  const latencyMs = [...latencyByFamily.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([family, values]) => {
      const sorted = values.slice().sort((x, y) => x - y);
      return {
        family,
        count: sorted.length,
        p50: percentileNearestRank(sorted, 50),
        p95: percentileNearestRank(sorted, 95),
      };
    });

  // Schema errors (rejected at the edge/ingestion during the window).
  const rejections = inputs.rejections ?? [];
  const byEventMap = new Map<string, { count: number; samples: Set<string> }>();
  for (const rejection of rejections) {
    const name = rejection.name ?? '(unknown)';
    const bucket = byEventMap.get(name) ?? { count: 0, samples: new Set<string>() };
    bucket.count += 1;
    for (const error of rejection.errors) {
      if (bucket.samples.size < 3) bucket.samples.add(error);
    }
    byEventMap.set(name, bucket);
  }
  const byEvent = [...byEventMap.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([name, bucket]) => ({ name, count: bucket.count, sampleErrors: [...bucket.samples] }));

  return {
    window: {
      since: inputs.window.since,
      until: inputs.window.until,
      hours: (untilMs - sinceMs) / 3_600_000,
    },
    totals: { shadow: shadow.length, baseline: baseline.length, delta: shadow.length - baseline.length },
    perEvent,
    families,
    schemaErrors: { total: rejections.length, byEvent },
    latencyMs,
    notes: [
      'Latency uses occurredAt (client clock, untrusted) — indicative only.',
      'Rows counted by receivedAt ∈ [since, until); baseline by observation time.',
      'Source column keeps shadow rows segregated (first_party | shadow).',
    ],
  };
}

/** Render the report as review-friendly markdown (PR/ops artifact). */
export function renderShadowReportMarkdown(report: ShadowComparisonReport): string {
  const lines: string[] = [];
  lines.push(`# Signals shadow comparison — ${report.window.hours}h window`);
  lines.push('');
  lines.push(`Window: ${report.window.since} → ${report.window.until}`);
  lines.push('');
  lines.push(`Totals: shadow=${report.totals.shadow}, baseline=${report.totals.baseline}, delta=${report.totals.delta}`);
  lines.push('');
  lines.push('| event | family | shadow | baseline | delta | delta% |');
  lines.push('|---|---|---:|---:|---:|---:|');
  for (const row of report.perEvent) {
    const pct = row.deltaPct === null ? 'n/a' : `${(row.deltaPct * 100).toFixed(1)}%`;
    lines.push(`| ${row.event} | ${row.family} | ${row.shadow} | ${row.baseline} | ${row.delta} | ${pct} |`);
  }
  lines.push('');
  lines.push(`Schema errors: ${report.schemaErrors.total}`);
  for (const row of report.schemaErrors.byEvent) {
    lines.push(`- ${row.name}: ${row.count} (${row.sampleErrors.join('; ')})`);
  }
  lines.push('');
  lines.push('Latency per family (indicative):');
  for (const row of report.latencyMs) {
    lines.push(`- ${row.family}: n=${row.count} p50=${row.p50}ms p95=${row.p95}ms`);
  }
  for (const note of report.notes) lines.push(`> ${note}`);
  return lines.join('\n');
}

/** Type-level guard used by the CLI: names must come from the registry. */
export function isRegisteredEvent(name: string): name is EventName {
  return name in FAMILY_BY_EVENT;
}
