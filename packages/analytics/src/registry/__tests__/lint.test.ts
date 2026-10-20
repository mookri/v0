// packages/analytics/src/registry/__tests__/lint.test.ts
// B1 acceptance: every lint gate has a negative fixture proving it fires,
// and the shipped registry + baseline + fixtures pass clean.

import { describe, expect, it } from 'vitest';
import { lintRegistry, type LintGate } from '@navdhya/analytics/src/registry/lint';
import { EVENTS, EVENT_MAP } from '@navdhya/analytics/src/registry/events/index';
import { buildBaseline, readBaseline, readFixtures } from '@navdhya/analytics/src/registry/generate';
import type { EventDef, ParamType } from '@navdhya/analytics/src/registry/types';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';

function clone(): EventDef[] {
  return structuredClone(EVENTS);
}

function gatesIn(diags: { gate: LintGate }[]): LintGate[] {
  return [...new Set(diags.map((d) => d.gate))];
}

const baseFixture = (): SignalEnvelope => ({
  id: 'FIXTURE00000000000000000090',
  name: 'page_view',
  version: 1,
  occurredAt: '2026-10-12T10:00:00.000Z',
  surface: 'web',
  appVersion: '1.0.0',
  session: { id: 'sess_lint_001', seq: 1 },
  actor: { uidHash: null, anonId: 'anon_lint_001' },
  consent: { analytics: true, ads: false },
  context: { routeTemplate: '/pujas' },
  params: { page_type: 'catalog' },
});

describe('shipped registry is clean', () => {
  it('passes ALL gates with committed baseline and fixtures', () => {
    const diags = lintRegistry(EVENTS, { baseline: readBaseline(), fixtures: readFixtures() });
    expect(diags).toEqual([]);
  });
});

describe('negative fixtures — one per gate class', () => {
  it('G1 structure: forbidden param type (only string|number|boolean)', () => {
    const events = clone();
    const pv = events.find((e) => e.name === 'page_view')!;
    pv.params[0]!.type = 'object' as unknown as ParamType;
    const diags = lintRegistry(events);
    expect(diags.some((d) => d.gate === 'structure' && d.message.includes('forbidden param type'))).toBe(true);
  });

  it('G1 structure: duplicate event names and bad sampling config', () => {
    const events = clone();
    events.push(structuredClone(events[0]!));
    const vi = events.find((e) => e.name === 'view_item')!;
    vi.sampling = { mode: 'fixed', rate: 5 };
    const diags = lintRegistry(events);
    expect(diags.some((d) => d.gate === 'structure' && d.message.includes('duplicate event'))).toBe(true);
    expect(diags.some((d) => d.gate === 'structure' && d.message.includes('0 < rate'))).toBe(true);
  });

  it('G2 delivery-policy: P0/server events can never route through gtm_ga4; empty sinks rejected', () => {
    const events = clone();
    const bc = events.find((e) => e.name === 'booking_confirmed')!;
    bc.allowedSinks = ['gtm_ga4', 'collector_bigquery'];
    const sv = events.find((e) => e.name === 'sign_up')!;
    sv.producer = 'server';
    sv.allowedSinks = ['gtm_ga4'];
    const pv = events.find((e) => e.name === 'page_view')!;
    pv.allowedSinks = [];
    const diags = lintRegistry(events);
    expect(diags.some((d) => d.gate === 'delivery-policy' && d.message.includes('P0'))).toBe(true);
    expect(diags.some((d) => d.gate === 'delivery-policy' && d.message.includes('server-produced'))).toBe(true);
    expect(diags.some((d) => d.gate === 'delivery-policy' && d.message.includes('non-empty'))).toBe(true);
  });

  it('G3 pii-names: direct-identifier param names are forbidden', () => {
    const events = clone();
    events.find((e) => e.name === 'page_view')!.params.push({
      name: 'user_email',
      type: 'string',
      required: false,
      description: 'should never exist',
    });
    const diags = lintRegistry(events);
    expect(diags.some((d) => d.gate === 'pii-names' && d.param === 'user_email')).toBe(true);
  });

  it('G4 high-cardinality: flagged params require hcReviewed', () => {
    const events = clone();
    events.find((e) => e.name === 'page_view')!.params.push({
      name: 'session_fingerprint',
      type: 'string',
      required: false,
      description: 'high cardinality by nature',
      highCardinality: true,
    });
    const diags = lintRegistry(events);
    expect(diags.some((d) => d.gate === 'high-cardinality' && d.param === 'session_fingerprint')).toBe(true);
  });

  it('G6 volume: >25 params/event and >500 events', () => {
    const events = clone();
    const pv = events.find((e) => e.name === 'page_view')!;
    for (let i = 0; i < 26; i++) {
      pv.params.push({ name: `filler_${i}`, type: 'string', required: false, description: 'filler' });
    }
    expect(lintRegistry(events).some((d) => d.gate === 'volume')).toBe(true);

    const flood: EventDef[] = Array.from({ length: 501 }, (_, i) => ({
      ...structuredClone(EVENTS[0]!),
      name: `flood_event_${i}`,
    }));
    expect(lintRegistry(flood).some((d) => d.gate === 'volume' && d.message.includes('500-name'))).toBe(true);
  });

  it('G7 compat: breaking param change requires a version bump', () => {
    const baseline = buildBaseline(EVENTS);

    const noBump = clone();
    const vi = noBump.find((e) => e.name === 'view_item')!;
    vi.params = vi.params.filter((p) => p.name !== 'item_category'); // remove optional param
    const diagsNoBump = lintRegistry(noBump, { baseline });
    expect(diagsNoBump.some((d) => d.gate === 'compat' && d.message.includes('requires version'))).toBe(true);

    const bumped = clone();
    const vi2 = bumped.find((e) => e.name === 'view_item')!;
    vi2.params = vi2.params.filter((p) => p.name !== 'item_category');
    vi2.version = 2;
    expect(lintRegistry(bumped, { baseline }).filter((d) => d.gate === 'compat')).toEqual([]);
  });

  it('G7 compat: baseline event deleted without explicit baseline removal', () => {
    const baseline = buildBaseline(EVENTS);
    const without = clone().filter((e) => e.name !== 'login');
    const diags = lintRegistry(without, { baseline });
    expect(diags.some((d) => d.gate === 'compat' && d.event === 'login' && d.message.includes('baseline'))).toBe(true);
  });

  it('G5 fixtures: unknown events, missing params, type mismatches, PII values', () => {
    const unknown: SignalEnvelope = { ...baseFixture(), name: 'not_registered' };
    expect(
      lintRegistry(EVENTS, { fixtures: [unknown] }).some(
        (d) => d.gate === 'fixtures' && d.message.includes('unknown event'),
      ),
    ).toBe(true);

    const missing = baseFixture();
    delete (missing.params as Record<string, unknown>).page_type;
    expect(lintRegistry(EVENTS, { fixtures: [missing] }).some((d) => d.gate === 'fixtures' && d.message.includes('missing required'))).toBe(true);

    const mistyped: SignalEnvelope = { ...baseFixture(), params: { page_type: 123 as unknown as string } };
    expect(lintRegistry(EVENTS, { fixtures: [mistyped] }).some((d) => d.gate === 'fixtures' && d.message.includes('type mismatch'))).toBe(true);

    const pii: SignalEnvelope = { ...baseFixture(), params: { page_type: 'reach me at person@example.com' } };
    expect(lintRegistry(EVENTS, { fixtures: [pii] }).some((d) => d.gate === 'fixtures' && d.message.includes('PII-shaped'))).toBe(true);

    const structurallyInvalid = { ...baseFixture(), session: { id: 's' } };
    expect(lintRegistry(EVENTS, { fixtures: [structurallyInvalid] }).some((d) => d.gate === 'fixtures' && d.message.includes('invalid envelope'))).toBe(true);
  });
});

describe('EVENT_MAP integrity', () => {
  it('indexes every event exactly once', () => {
    expect(EVENT_MAP.size).toBe(EVENTS.length);
    for (const e of EVENTS) expect(EVENT_MAP.get(e.name)).toEqual(e);
  });
});
