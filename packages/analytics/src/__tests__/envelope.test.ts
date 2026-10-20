// packages/analytics/src/__tests__/envelope.test.ts
// B1 acceptance: envelope round-trip + structural validation gates.

import { describe, expect, it } from 'vitest';
import {
  MAX_BREADCRUMBS,
  newEventId,
  validateEnvelope,
  type SignalEnvelope,
} from '@navdhya/analytics/src/contracts/envelope';

function validEnvelope(): SignalEnvelope {
  return {
    id: 'FIXTURE00000000000000000099',
    name: 'page_view',
    version: 1,
    occurredAt: '2026-10-12T10:00:00.000Z',
    surface: 'web',
    appVersion: '1.0.0',
    session: { id: 'sess_rt_001', seq: 1 },
    actor: { uidHash: null, anonId: 'anon_rt_001' },
    consent: { analytics: true, ads: false },
    context: { routeTemplate: '/pujas' },
    params: { page_type: 'catalog' },
  };
}

describe('SignalEnvelope round-trip', () => {
  it('validates: construct → validate → serialize → validate', () => {
    const env = validEnvelope();
    expect(validateEnvelope(env)).toEqual([]);
    const roundTripped: unknown = JSON.parse(JSON.stringify(env));
    expect(validateEnvelope(roundTripped)).toEqual([]);
  });

  it('requires session.seq (client-delivery metric denominator)', () => {
    const env = validEnvelope() as Partial<SignalEnvelope>;
    delete env.session;
    const errors = validateEnvelope(env);
    expect(errors.some((e) => e.includes('session'))).toBe(true);

    const seqZero = { ...validEnvelope(), session: { id: 's', seq: 0 } };
    expect(validateEnvelope(seqZero).some((e) => e.includes('session.seq'))).toBe(true);
  });

  it('rejects unknown surfaces and non-snake event names', () => {
    expect(validateEnvelope({ ...validEnvelope(), surface: 'mobile' }).some((e) => e.includes('surface'))).toBe(true);
    expect(validateEnvelope({ ...validEnvelope(), name: 'Page View' }).some((e) => e.includes('name'))).toBe(true);
  });

  it('rejects query strings in routeTemplate (PII vector)', () => {
    const env = { ...validEnvelope(), context: { routeTemplate: '/search?q=secret' } };
    expect(validateEnvelope(env).some((e) => e.includes('query strings'))).toBe(true);
  });

  it('rejects non-primitive params and > 25 params', () => {
    const badValue = { ...validEnvelope(), params: { page_type: { nested: true } } };
    expect(validateEnvelope(badValue).some((e) => e.includes('string | number | boolean'))).toBe(true);

    const tooMany: Record<string, string> = {};
    for (let i = 0; i < 26; i++) tooMany[`p_${i}`] = 'x';
    expect(validateEnvelope({ ...validEnvelope(), params: tooMany }).some((e) => e.includes('exceeds limit'))).toBe(true);
  });

  it('bounds breadcrumbs and validates their shape', () => {
    const many = Array.from({ length: MAX_BREADCRUMBS + 1 }, (_, i) => ({ at: i, category: 'c', message: 'm' }));
    expect(validateEnvelope({ ...validEnvelope(), breadcrumbs: many }).some((e) => e.includes('exceeds limit'))).toBe(true);

    const malformed = { ...validEnvelope(), breadcrumbs: [{ at: 'nope', category: 1, message: null }] };
    expect(validateEnvelope(malformed).some((e) => e.includes('breadcrumbs[0]'))).toBe(true);
  });

  it('rejects non-objects and bad occurredAt', () => {
    expect(validateEnvelope(null).length).toBeGreaterThan(0);
    expect(validateEnvelope('x').length).toBeGreaterThan(0);
    expect(validateEnvelope({ ...validEnvelope(), occurredAt: 'not-a-date' }).some((e) => e.includes('occurredAt'))).toBe(true);
  });
});

describe('newEventId (ULID)', () => {
  it('is 26 chars, unique and time-sortable', () => {
    const a = newEventId(1_000);
    const b = newEventId(2_000);
    expect(a).toHaveLength(26);
    expect(a.slice(0, 10) < b.slice(0, 10)).toBe(true); // lexicographic time prefix order

    const ids = new Set(Array.from({ length: 500 }, () => newEventId()));
    expect(ids.size).toBe(500);
  });
});
