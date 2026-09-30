export interface RouteReference {
  routeId: string;
  params?: Record<string, string>;
}

export function resolveRouteReference(ref: RouteReference): string | null {
  if (ref.routeId === 'customer.booking.detail' && ref.params?.bookingId) {
    return `/bookings/${ref.params.bookingId}`;
  }
  const staticRoutes: Record<string, string> = {
    'customer.home': '/',
    'customer.bookings': '/bookings',
    'customer.profile': '/profile',
    'customer.support': '/support',
  };
  return staticRoutes[ref.routeId] ?? null;
}

export interface CustomerNotification {
  id: string;
  title: string;
  message: string;
  createdAt: string;
  read: boolean;
  destination?: RouteReference;
}

export function markNotificationRead(
  notifications: CustomerNotification[],
  id: string,
): CustomerNotification[] | null {
  const found = notifications.some((n) => n.id === id);
  if (!found) return null;
  return notifications.map((n) => (n.id === id ? { ...n, read: true } : n));
}
