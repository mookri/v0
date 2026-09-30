import type { CustomerBookingCard } from '../bookings/list-customer-bookings';

export interface QuickAction {
  id: string;
  label: string;
  href: string;
}

export interface CustomerHomeViewModel {
  greeting: { displayName: string };
  upcomingBooking?: CustomerBookingCard;
  quickActions: QuickAction[];
  recommendations: { id: string; title: string }[];
  recentActivity: { id: string; text: string }[];
}

export function getCustomerHome(input: {
  displayName: string;
  upcoming?: CustomerBookingCard;
  recommendations?: { id: string; title: string }[];
  recentActivity?: { id: string; text: string }[];
}): CustomerHomeViewModel {
  return {
    greeting: { displayName: input.displayName },
    upcomingBooking: input.upcoming,
    quickActions: [
      { id: 'book-again', label: 'Book again', href: '/bookings' },
      { id: 'discover', label: 'Discover rituals', href: '/saved' },
      { id: 'support', label: 'Get help', href: '/support' },
    ],
    recommendations: input.recommendations ?? [],
    recentActivity: input.recentActivity ?? [],
  };
}
