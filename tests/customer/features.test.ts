import { test, expect } from 'vitest';
import { listCustomerBookings } from '../../apps/customer/application/bookings/list-customer-bookings';
import { authorizeBooking, toCustomerBookingDetails } from '../../apps/customer/application/bookings/get-customer-booking-details';
import { cancelCustomerBooking } from '../../apps/customer/application/bookings/cancel-customer-booking';
import { validateProfileUpdate } from '../../apps/customer/application/profile/customer-profile';
import { getCustomerHome } from '../../apps/customer/application/home/get-customer-home';
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

test('booking list maps capability summaries to cards', () => {
  const cards = listCustomerBookings([
    { id: 'b1', status: 'CONFIRMED', itemCount: 1, startTime: new Date('2026-10-05T10:00:00Z'), createdAt: new Date() },
  ]);
  expect(cards).toEqual([{ id: 'b1', status: 'CONFIRMED', date: '2026-10-05', itemCount: 1 }]);
});

test('booking detail derives actions from capability state machine', () => {
  const d = toCustomerBookingDetails({ booking: booking(), puja: { id: 'p1', name: 'Puja' } });
  expect(d.actions).toContain('cancel');
  expect(d.actions).toContain('reschedule');
  const done = toCustomerBookingDetails({ booking: booking({ status: 'COMPLETED' }), puja: { id: 'p1', name: 'Puja' } });
  expect(done.actions).not.toContain('cancel');
  expect(done.actions).toContain('book-again');
});

test('cross-actor booking access is NOT_FOUND, not forbidden leak', () => {
  expect(authorizeBooking(booking(), 'other')).toEqual({ ok: false, reason: 'NOT_FOUND' });
  expect(authorizeBooking(null, 'u1')).toEqual({ ok: false, reason: 'NOT_FOUND' });
  expect(authorizeBooking(booking(), 'u1').ok).toBe(true);
});

test('cancel enforces ownership + state + idempotency key', () => {
  expect(cancelCustomerBooking(booking(), 'u1', 'k1')).toEqual({ ok: true, idempotencyKey: 'k1' });
  expect(cancelCustomerBooking(booking(), 'other', 'k1').reason).toBe('FORBIDDEN');
  expect(cancelCustomerBooking(booking({ status: 'COMPLETED' }), 'u1', 'k1').reason).toBe('INVALID_TRANSITION');
});

test('profile validation separates field errors from success', () => {
  expect(validateProfileUpdate({ email: 'bad' }).ok).toBe(false);
  expect(validateProfileUpdate({ email: 'a@b.com', name: 'Asha' })).toEqual({
    ok: true,
    input: { email: 'a@b.com', name: 'Asha' },
  });
});

test('home composes greeting/actions without capability internals', () => {
  const home = getCustomerHome({ displayName: 'Asha' });
  expect(home.greeting.displayName).toBe('Asha');
  expect(home.quickActions.length).toBeGreaterThan(0);
});
