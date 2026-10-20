export const CUSTOMER_ROUTES = {
  home: { id: 'customer.home', path: '/' },
  bookings: { id: 'customer.bookings', path: '/bookings' },
  bookingDetail: { id: 'customer.booking.detail', path: '/bookings/[bookingId]' },
  discover: { id: 'customer.discover', path: '/discover' },
  saved: { id: 'customer.saved', path: '/saved' },
  profile: { id: 'customer.profile', path: '/profile' },
  settings: { id: 'customer.settings', path: '/settings' },
  notifications: { id: 'customer.notifications', path: '/notifications' },
  support: { id: 'customer.support', path: '/support' },
} as const;

export type CustomerRouteId = (typeof CUSTOMER_ROUTES)[keyof typeof CUSTOMER_ROUTES]['id'];
