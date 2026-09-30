import { test, expect } from 'vitest';
import { canTransitionFulfillment } from '@navdhya/fulfillment/src/domain/fulfillment-order';
import { transitionOrder } from '@navdhya/fulfillment/src/ports/gateways';
import { isOfferAcceptable, rankCandidates } from '@navdhya/fulfillment/src/assignment/assignment';
import { stableExceptionKey } from '@navdhya/fulfillment/src/exceptions/exception';
import { overlaps, withBuffer, hasConflict } from '@navdhya/scheduling/src/schedule';
import { commitSchedule } from '@navdhya/scheduling/src/application';

const order = (status: 'READY' | 'ASSIGNMENT_PENDING' | 'COMPLETED' = 'READY') => ({
  id: 'f1', bookingId: 'b1', status, serviceDate: '2026-10-05', timezone: 'Asia/Kolkata', recipeVersion: 3, version: 2,
});

test('fulfillment lifecycle stays independent of booking states', () => {
  expect(canTransitionFulfillment('READY', 'ASSIGNMENT_PENDING')).toBe(true);
  expect(canTransitionFulfillment('COMPLETED', 'READY')).toBe(false);
  expect(transitionOrder(order(), 'ASSIGNMENT_PENDING')?.version).toBe(3);
  expect(transitionOrder(order('COMPLETED'), 'READY')).toBeNull();
});

test('offers expire and rank deterministically', () => {
  const now = '2026-09-30T00:00:00.000Z';
  expect(isOfferAcceptable({ id: 'o', fulfillmentId: 'f', partnerId: 'p', expiresAt: '2026-10-01T00:00:00.000Z', status: 'PENDING' }, now)).toBe(true);
  expect(isOfferAcceptable({ id: 'o', fulfillmentId: 'f', partnerId: 'p', expiresAt: '2020-01-01T00:00:00.000Z', status: 'PENDING' }, now)).toBe(false);
  expect(rankCandidates([{ id: 'b', score: 1 }, { id: 'a', score: 1 }]).map((c) => c.id)).toEqual(['a', 'b']);
});

test('exception keys are stable per window (no scheduler dupes)', () => {
  expect(stableExceptionKey('f', 'NO_ASSIGNMENT', '2026-10-05')).toBe(stableExceptionKey('f', 'NO_ASSIGNMENT', '2026-10-05'));
});

test('scheduling rejects overlaps and invalid windows', () => {
  expect(overlaps({ startsAt: '10:00', endsAt: '11:00' }, { startsAt: '10:30', endsAt: '11:30' })).toBe(true);
  expect(overlaps({ startsAt: '10:00', endsAt: '11:00' }, { startsAt: '11:00', endsAt: '12:00' })).toBe(false);
  const buffered = withBuffer({ startsAt: '2026-10-05T10:00:00.000Z', endsAt: '2026-10-05T11:00:00.000Z' }, 30);
  expect(hasConflict([{ startsAt: '2026-10-05T09:00:00.000Z', endsAt: '2026-10-05T09:45:00.000Z' }], buffered)).toBe(true);
});

test('commit is transactional on conflicts', async () => {
  const store = {
    rows: [{ id: 's1', resourceId: 'p1', resourceType: 'PARTNER' as const, workOrderId: 'w0', startsAt: '2026-10-05T10:00:00.000Z', endsAt: '2026-10-05T11:00:00.000Z', timezone: 'Asia/Kolkata', status: 'CONFIRMED' as const }],
    async listByResource() { return this.rows; },
    async save(r: (typeof this.rows)[number]) { this.rows.push(r); },
    async update() {},
  };
  const clash = await commitSchedule(store, { id: 's2', resourceId: 'p1', workOrderId: 'w1', window: { startsAt: '2026-10-05T10:30:00.000Z', endsAt: '2026-10-05T11:30:00.000Z' }, timezone: 'Asia/Kolkata' });
  expect(clash).toEqual({ ok: false, reason: 'SCHEDULE_CONFLICT' });
  const free = await commitSchedule(store, { id: 's3', resourceId: 'p1', workOrderId: 'w1', window: { startsAt: '2026-10-05T12:00:00.000Z', endsAt: '2026-10-05T13:00:00.000Z' }, timezone: 'Asia/Kolkata' });
  expect(free.ok).toBe(true);
  const bad = await commitSchedule(store, { id: 's4', resourceId: 'p1', workOrderId: 'w1', window: { startsAt: '2026-10-05T13:00:00.000Z', endsAt: '2026-10-05T12:00:00.000Z' }, timezone: 'Asia/Kolkata' });
  expect(bad).toEqual({ ok: false, reason: 'TIME_WINDOW_INVALID' });
});
