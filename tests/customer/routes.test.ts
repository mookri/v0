import { test, expect } from 'vitest';
import { customerRoutes, routeById } from '../../apps/customer/application/routes';
import { bookingSection } from '../../packages/booking/src/ui/booking-display';
import { analyticsEvent, CUSTOMER_EVENTS } from '../../apps/customer/adapters/analytics/events';

test('route registry covers all customer routes with access + analytics', () => {
  for (const key of ['home', 'bookings', 'bookingDetail', 'discover', 'saved', 'profile', 'notifications', 'settings', 'support']) {
    const r = customerRoutes[key];
    expect(r.id.startsWith('customer.')).toBe(true);
    expect(r.access).toBe('authenticated');
    expect(r.product).toBe('customer');
  }
  expect(routeById('customer.booking.detail')?.path).toBe('/bookings/:bookingId');
  expect(routeById('unknown')).toBeNull();
});

test('booking sections derive from canonical status', () => {
  expect(bookingSection('CONFIRMED')).toBe('upcoming');
  expect(bookingSection('IN_PROGRESS')).toBe('active');
  expect(bookingSection('COMPLETED')).toBe('completed');
  expect(bookingSection('CANCELLED')).toBe('cancelled');
  expect(bookingSection('DRAFT')).toBe('upcoming');
});

test('analytics events carry route identity, never raw paths', () => {
  const e = analyticsEvent('customer.bookings', CUSTOMER_EVENTS.bookingsViewed, 'req1');
  expect(e).toEqual({ product: 'customer', routeId: 'customer.bookings', event: 'customer.bookings.viewed', requestId: 'req1' });
});
