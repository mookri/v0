import type { BookingSummary } from '../../../../packages/booking/src/contracts/BookingSummary';

export interface CustomerBookingCard {
  id: string;
  status: BookingSummary['status'];
  date: string;
  itemCount: number;
}

export function toCustomerBookingCard(s: BookingSummary): CustomerBookingCard {
  return {
    id: s.id,
    status: s.status,
    date: new Date(s.startTime).toISOString().slice(0, 10),
    itemCount: s.itemCount,
  };
}

export function listCustomerBookings(summaries: BookingSummary[]): CustomerBookingCard[] {
  return summaries.map(toCustomerBookingCard);
}
