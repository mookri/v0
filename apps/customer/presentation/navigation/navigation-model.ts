export interface NavigationItem {
  id: string;
  label: string;
  routeId: string;
  href: string;
  icon: string;
  priority: number;
  visibility: 'visible' | 'hidden' | 'disabled';
}

export const customerNavigation: NavigationItem[] = [
  { id: 'nav-home', label: 'Home', routeId: 'customer.home', href: '/', icon: 'home', priority: 1, visibility: 'visible' },
  { id: 'nav-bookings', label: 'Bookings', routeId: 'customer.bookings', href: '/bookings', icon: 'bookings', priority: 2, visibility: 'visible' },
  { id: 'nav-discover', label: 'Discover', routeId: 'customer.discover', href: '/discover', icon: 'discover', priority: 3, visibility: 'visible' },
  { id: 'nav-saved', label: 'Saved', routeId: 'customer.saved', href: '/saved', icon: 'saved', priority: 4, visibility: 'visible' },
  { id: 'nav-profile', label: 'Profile', routeId: 'customer.profile', href: '/profile', icon: 'profile', priority: 5, visibility: 'visible' },
];

export function navigationForRoute(routeId: string): NavigationItem | undefined {
  return customerNavigation.find((n) => n.routeId === routeId);
}
