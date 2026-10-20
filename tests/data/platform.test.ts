import { test, expect } from 'vitest';
import { validateEnvelope, dedupKey, lookupEvent } from '@navdhya/events/src/index';
import { AnalyticsClient, CORE_METRICS } from '@navdhya/analytics/src/index';
import { assignVariant } from '@navdhya/experimentation/src/index';
import { joinAttribution } from '@navdhya/attribution/src/index';

const base = {
  eventId: 'e1', eventType: 'BookingConfirmed', eventVersion: 1,
  occurredAt: '2026-09-30T00:00:00.000Z', producer: 'booking', environment: 'production' as const,
  data: { bookingId: 'b1' },
};

test('envelope validation rejects incomplete facts', () => {
  expect(validateEnvelope(base)).toEqual([]);
  expect(validateEnvelope({ ...base, eventId: '' })).toContain('eventId required');
  expect(dedupKey(base)).toBe('booking:e1');
});

test('registry owns every seeded event with consumers', () => {
  for (const type of ['BookingConfirmed', 'PaymentSucceeded', 'SearchPerformed', 'MessageDelivered', 'ExperimentExposed']) {
    const def = lookupEvent(type, 1);
    expect(def).not.toBeNull();
    expect(def!.owner).toBeTruthy();
    expect(def!.consumers.length).toBeGreaterThan(0);
    expect(def!.classification).toBeTruthy();
  }
  expect(lookupEvent('Nope', 1)).toBeNull();
});

test('analytics client never rejects the business path', () => {
  const failing = { publish: async () => { throw new Error('down'); } };
  const client = new AnalyticsClient(failing);
  expect(() => client.track({ eventType: 'x', eventVersion: 1, occurredAt: base.occurredAt, product: 'web', data: {} })).not.toThrow();
  expect(client.flush()).toHaveLength(1);
});

test('metric definitions declare owner, sources, version', () => {
  for (const m of CORE_METRICS) {
    expect(m.owner).toBeTruthy();
    expect(m.sourceEvents.length).toBeGreaterThan(0);
    expect(m.version).toBe(1);
  }
});

test('assignment is deterministic per subject; exposure stays separate', () => {
  const exp = { id: 'rank', name: 'r', status: 'ACTIVE' as const, variants: [{ id: 'a', key: 'a', allocation: 50 }, { id: 'b', key: 'b', allocation: 50 }] };
  const a1 = assignVariant(exp, 'u1', base.occurredAt);
  const a2 = assignVariant(exp, 'u1', base.occurredAt);
  expect(a1.variantId).toBe(a2.variantId);
  expect(a1).not.toHaveProperty('surfaceId');
});

test('paused experiments fall back to control', () => {
  const exp = { id: 'x', name: 'x', status: 'PAUSED' as const, variants: [] };
  expect(assignVariant(exp, 'u1', base.occurredAt).variantId).toBe('control');
});

test('attribution joins without touching business state', () => {
  expect(joinAttribution([{ source: 'search', queryId: 'q1' }])?.source).toBe('search');
  expect(joinAttribution([])).toBeNull();
});
