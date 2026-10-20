export interface AnalyticsEvent {
  product: 'customer';
  routeId: string;
  event: string;
  requestId: string;
  bookingId?: string;
}

export function analyticsEvent(
  routeId: string,
  event: string,
  requestId: string,
  extra?: { bookingId?: string },
): AnalyticsEvent {
  return { product: 'customer', routeId, event, requestId, ...extra };
}

export const CUSTOMER_EVENTS = {
  homeViewed: 'customer.home.viewed',
  bookingsViewed: 'customer.bookings.viewed',
  bookingDetailViewed: 'customer.booking.detail.viewed',
  bookingCancelStarted: 'customer.booking.cancel.started',
  bookingCancelCompleted: 'customer.booking.cancel.completed',
  bookingCancelFailed: 'customer.booking.cancel.failed',
  profileViewed: 'customer.profile.viewed',
  profileUpdated: 'customer.profile.updated',
  settingsViewed: 'customer.settings.viewed',
} as const;
