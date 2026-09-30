import { BookingStatus } from '../domain/BookingStatus';

export type BookingSection = 'upcoming' | 'active' | 'completed' | 'cancelled';

export function bookingSection(status: BookingStatus): BookingSection {
  switch (status) {
    case 'DRAFT':
    case 'PENDING_PAYMENT':
    case 'PAYMENT_CONFIRMED':
    case 'CONFIRMED':
    case 'ASSIGNED':
      return 'upcoming';
    case 'IN_PROGRESS':
      return 'active';
    case 'COMPLETED':
    case 'REFUNDED':
      return 'completed';
    case 'CANCELLED':
    case 'FAILED':
    case 'EXPIRED':
      return 'cancelled';
  }
}

export function statusLabel(status: BookingStatus): string {
  return status.toLowerCase().replace(/_/g, ' ');
}

export function formatBookingDate(startTime: Date): { date: string; time: string } {
  const d = new Date(startTime);
  return { date: d.toISOString().slice(0, 10), time: d.toISOString().slice(11, 16) };
}
