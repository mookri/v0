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
  { routeId: 'customer.profile', method: 'GET', path: '/api/profile', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.saved', method: 'GET', path: '/api/saved', access: 'authenticated', capability: 'user', cache: 'private-short' },
  { routeId: 'customer.notifications', method: 'GET', path: '/api/notifications', access: 'authenticated', capability: 'notification', cache: 'private-short' },
  { routeId: 'customer.discover', method: 'GET', path: '/api/discover', access: 'authenticated', capability: 'discovery', cache: 'private-short' },
  { routeId: 'customer.settings', method: 'GET', path: '/api/settings', access: 'authenticated', capability: 'user', cache: 'private-short' },
];
