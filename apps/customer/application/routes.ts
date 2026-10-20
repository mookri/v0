export type RouteAccess = 'authenticated' | 'public';

export interface CustomerRoute {
  id: string;
  path: string;
  access: RouteAccess;
  product: 'customer';
  layout: string;
  analytics: string;
}

export const customerRoutes: Record<string, CustomerRoute> = {
  home: { id: 'customer.home', path: '/', access: 'authenticated', product: 'customer', layout: 'dashboard', analytics: 'customer.home' },
  bookings: { id: 'customer.bookings', path: '/bookings', access: 'authenticated', product: 'customer', layout: 'list', analytics: 'customer.bookings' },
  bookingDetail: { id: 'customer.booking.detail', path: '/bookings/:bookingId', access: 'authenticated', product: 'customer', layout: 'detail', analytics: 'customer.booking.detail' },
  discover: { id: 'customer.discover', path: '/discover', access: 'authenticated', product: 'customer', layout: 'list', analytics: 'customer.discover' },
  saved: { id: 'customer.saved', path: '/saved', access: 'authenticated', product: 'customer', layout: 'list', analytics: 'customer.saved' },
  profile: { id: 'customer.profile', path: '/profile', access: 'authenticated', product: 'customer', layout: 'profile', analytics: 'customer.profile' },
  notifications: { id: 'customer.notifications', path: '/notifications', access: 'authenticated', product: 'customer', layout: 'list', analytics: 'customer.notifications' },
  settings: { id: 'customer.settings', path: '/settings', access: 'authenticated', product: 'customer', layout: 'settings', analytics: 'customer.settings' },
  support: { id: 'customer.support', path: '/support', access: 'authenticated', product: 'customer', layout: 'detail', analytics: 'customer.support' },
};

export function routeById(id: string): CustomerRoute | null {
  return Object.values(customerRoutes).find((r) => r.id === id) ?? null;
}
