import { test, expect } from 'vitest';
import { rescheduleCustomerBooking } from '../../apps/customer/application/bookings/reschedule-customer-booking';
import { listSavedItems } from '../../apps/customer/application/saved/saved-items';
import { getDiscoverFeed } from '../../apps/customer/application/discover/discover-feed';
import {
  markNotificationRead,
  resolveRouteReference,
} from '../../apps/customer/application/notifications/notifications';
import { customerSettingsSections } from '../../apps/customer/application/settings/settings';
import { validateSupportRequest } from '../../apps/customer/application/support/support';
import type { Booking } from '../../packages/booking/src/domain/Booking';

function booking(over: Partial<Booking> = {}): Booking {
  return {
    id: 'b1',
    customer: { userId: 'u1', name: 'A' },
    items: [{ pujaId: 'p1', quantity: 1 }],
    schedule: { startTime: new Date('2026-10-05T10:00:00.000Z'), endTime: new Date('2026-10-05T12:00:00.000Z') },
    status: 'CONFIRMED',
    createdAt: new Date('2026-09-01T00:00:00.000Z'),
    updatedAt: new Date('2026-09-01T00:00:00.000Z'),
    ...over,
  };
}

test('reschedule enforces ownership, state, slot, idempotency', () => {
  const t = new Date('2026-10-06T10:00:00.000Z');
  expect(rescheduleCustomerBooking(booking(), 'u1', t, true, 'k1')).toEqual({ ok: true, idempotencyKey: 'k1' });
  expect(rescheduleCustomerBooking(booking(), 'other', t, true, 'k1').reason).toBe('FORBIDDEN');
  expect(rescheduleCustomerBooking(booking({ status: 'COMPLETED' }), 'u1', t, true, 'k1').reason).toBe('INVALID_TRANSITION');
  expect(rescheduleCustomerBooking(booking(), 'u1', t, false, 'k1').reason).toBe('SLOT_UNAVAILABLE');
});

test('saved items resolve via owning capability, unresolvable dropped', () => {
  const cards = listSavedItems(
    [
      { id: 's1', type: 'puja', resourceId: 'p1', createdAt: '2026-09-01' },
      { id: 's2', type: 'puja', resourceId: 'missing', createdAt: '2026-09-01' },
    ],
    (item) => (item.resourceId === 'p1' ? { title: 'Puja', href: '/discover' } : null),
  );
  expect(cards).toEqual([{ id: 's1', type: 'puja', title: 'Puja', href: '/discover' }]);
});

test('discover feed composes recommendation kinds without engine logic', () => {
  const feed = getDiscoverFeed({ recommended: [{ id: 'r1', title: 'R', kind: 'puja' }] });
  expect(feed.recommended).toHaveLength(1);
  expect(feed.popular).toEqual([]);
});

test('notification destinations resolve to real URLs, reads are server ops', () => {
  expect(resolveRouteReference({ routeId: 'customer.booking.detail', params: { bookingId: 'b1' } })).toBe('/bookings/b1');
  expect(resolveRouteReference({ routeId: 'unknown' })).toBeNull();
  const items = [{ id: 'n1', title: 'T', message: 'M', createdAt: '2026-09-01', read: false }];
  expect(markNotificationRead(items, 'n1')?.[0].read).toBe(true);
  expect(markNotificationRead(items, 'missing')).toBeNull();
});

test('settings split by owner; support validates input', () => {
  expect(customerSettingsSections.map((s) => s.owner)).toContain('identity');
  expect(validateSupportRequest({ topic: '', message: 'short' }).ok).toBe(false);
  expect(validateSupportRequest({ topic: 'booking', message: 'My pandit never arrived, please help' }).ok).toBe(true);
});
