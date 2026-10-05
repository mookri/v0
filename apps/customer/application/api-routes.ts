export interface ApiRouteMeta {
  routeId: string;
  method: string;
  path: string;
  access: 'authenticated' | 'public';
  capability: string;
  cache: 'private-short' | 'none';
}

export const apiRoutes: ApiRouteMeta[] = [
  { routeId: 'customer.home', method: 'GET', path: '/api/home', access: 'authenticated', capability: 'orchestration', cache: 'private-short' },
  { routeId: 'customer.bookings', method: 'GET', path: '/api/bookings', access: 'authenticated', capability: 'booking', cache: 'private-short' },
  { routeId: 'customer.booking.detail', method: 'GET', path: '/api/bookings/:bookingId', access: 'authenticated', capability: 'booking', cache: 'private-short' },
  { routeId: 'customer.booking.detail', method: 'POST', path: '/api/bookings/:bookingId/cancel', access: 'authenticated', capability: 'booking', cache: 'none' },
  { routeId: 'customer.booking.detail', method: 'POST', path: '/api/bookings/:bookingId/reschedule', access: 'authenticated', capability: 'booking', cache: 'none' },
  { routeId: 'customer.booking.detail', method: 'GET', path: '/api/bookings/:bookingId/history', access: 'authenticated', capability: 'booking', cache: 'private-short' },
  { routeId: 'customer.profile', method: 'GET', path: '/api/profile', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.profile', method: 'PATCH', path: '/api/profile', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.profile', method: 'POST', path: '/api/profile:deactivate', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.profile', method: 'POST', path: '/api/profile:delete', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.profile', method: 'POST', path: '/api/profile:reactivate', access: 'public', capability: 'user', cache: 'none' },
  { routeId: 'customer.saved', method: 'GET', path: '/api/saved', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.saved', method: 'POST', path: '/api/saved', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.saved', method: 'DELETE', path: '/api/saved/:id', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.notifications', method: 'GET', path: '/api/notifications', access: 'authenticated', capability: 'notification', cache: 'private-short' },
  { routeId: 'customer.notifications', method: 'POST', path: '/api/notifications/:id/read', access: 'authenticated', capability: 'notification', cache: 'none' },
  { routeId: 'customer.notifications', method: 'POST', path: '/api/notifications/read-all', access: 'authenticated', capability: 'notification', cache: 'none' },
  { routeId: 'customer.discover', method: 'GET', path: '/api/discover', access: 'authenticated', capability: 'discovery', cache: 'private-short' },
  { routeId: 'customer.settings', method: 'GET', path: '/api/settings', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.settings', method: 'PATCH', path: '/api/settings', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.addresses', method: 'GET', path: '/api/addresses', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.addresses', method: 'POST', path: '/api/addresses', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.addresses', method: 'PATCH', path: '/api/addresses/:id', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.addresses', method: 'DELETE', path: '/api/addresses/:id', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.addresses', method: 'POST', path: '/api/addresses/:id/default', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.activity', method: 'GET', path: '/api/activity', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.support', method: 'GET', path: '/api/support/tickets', access: 'authenticated', capability: 'support', cache: 'private-short' },
  { routeId: 'customer.support', method: 'POST', path: '/api/support/tickets', access: 'authenticated', capability: 'support', cache: 'none' },
  { routeId: 'customer.support', method: 'GET', path: '/api/support/tickets/:id', access: 'authenticated', capability: 'support', cache: 'private-short' },
  { routeId: 'customer.support', method: 'POST', path: '/api/support/tickets/:id/messages', access: 'authenticated', capability: 'support', cache: 'none' },
  { routeId: 'customer.support', method: 'POST', path: '/api/support/tickets/:id/close', access: 'authenticated', capability: 'support', cache: 'none' },
  { routeId: 'customer.referrals', method: 'GET', path: '/api/referrals', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.reviews', method: 'GET', path: '/api/reviews', access: 'authenticated', capability: 'booking', cache: 'private-short' },
  { routeId: 'customer.booking.detail', method: 'POST', path: '/api/bookings/:bookingId/reviews', access: 'authenticated', capability: 'booking', cache: 'none' },
  { routeId: 'customer.reviews', method: 'DELETE', path: '/api/reviews/:id', access: 'authenticated', capability: 'booking', cache: 'none' },
  { routeId: 'customer.verifications', method: 'GET', path: '/api/verifications', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.verifications', method: 'POST', path: '/api/verifications', access: 'authenticated', capability: 'user', cache: 'none' },
  { routeId: 'customer.verifications', method: 'POST', path: '/api/verifications/confirm', access: 'authenticated', capability: 'user', cache: 'none' },

  // Puja / Catalog (P7)
  { routeId: 'customer.pujas', method: 'GET', path: '/api/pujas', access: 'authenticated', capability: 'catalog', cache: 'private-short' },
  { routeId: 'customer.puja.detail', method: 'GET', path: '/api/pujas/:pujaId', access: 'authenticated', capability: 'catalog', cache: 'private-short' },
  { routeId: 'customer.puja.quote', method: 'POST', path: '/api/pujas/:pujaId/quote', access: 'authenticated', capability: 'catalog', cache: 'none' },

  // Booking creation (P8)
  { routeId: 'customer.bookings.create', method: 'POST', path: '/api/bookings', access: 'authenticated', capability: 'booking', cache: 'none' },

  // Pandit directory (P9)
  { routeId: 'customer.pandits', method: 'GET', path: '/api/pandits', access: 'authenticated', capability: 'pandit:read', cache: 'private-short' },
  { routeId: 'customer.pandit.detail', method: 'GET', path: '/api/pandits/:panditId', access: 'authenticated', capability: 'pandit:read', cache: 'private-short' },
  { routeId: 'customer.pandit.availability', method: 'GET', path: '/api/pandits/:panditId/availability', access: 'authenticated', capability: 'pandit:read', cache: 'private-short' },

  // Temple directory (P11)
  { routeId: 'customer.temples', method: 'GET', path: '/api/temples', access: 'authenticated', capability: 'temple:read', cache: 'private-short' },
  { routeId: 'customer.temple.detail', method: 'GET', path: '/api/temples/:templeId', access: 'authenticated', capability: 'temple:read', cache: 'private-short' },
];