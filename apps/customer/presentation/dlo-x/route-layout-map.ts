export const routeLayoutMap: Record<string, { shell: string; layout: string; appBar: string }> = {
  'customer.home': { shell: 'customer-app', layout: 'dashboard', appBar: 'standard' },
  'customer.bookings': { shell: 'customer-app', layout: 'list', appBar: 'standard' },
  'customer.booking.detail': { shell: 'customer-app', layout: 'detail', appBar: 'back' },
  'customer.discover': { shell: 'customer-app', layout: 'list', appBar: 'standard' },
  'customer.saved': { shell: 'customer-app', layout: 'list', appBar: 'standard' },
  'customer.profile': { shell: 'customer-app', layout: 'profile', appBar: 'standard' },
  'customer.settings': { shell: 'customer-app', layout: 'settings', appBar: 'standard' },
  'customer.notifications': { shell: 'customer-app', layout: 'list', appBar: 'standard' },
  'customer.support': { shell: 'customer-app', layout: 'detail', appBar: 'standard' },
};

export type NavigationType = 'internal' | 'browser-back' | 'browser-forward' | 'deep-link' | 'redirect' | 'external-entry';

export interface RouteViewEvent {
  product: 'customer';
  routeId: string;
  previousRouteId?: string;
  navigationType: NavigationType;
}
