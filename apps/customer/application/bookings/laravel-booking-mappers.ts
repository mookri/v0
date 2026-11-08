import type { BookingSummary } from '@navdhya/booking/src/contracts/BookingSummary';
import type { CustomerBookingCard } from '@/application/bookings/list-customer-bookings';
import type { BookingAction, CustomerBookingDetails } from '@/application/bookings/get-customer-booking-details';

// Backend reservation → BFF view models (F2). The backend speaks its own
// 11-state UPPERCASE lifecycle; the BFF card contract carries the 6-state
// lowercase BookingSummary vocabulary, so states fold for display
// (parity-customers.md documents the fold; detail `actions` come from the
// backend live list plus the static navigation actions).

export interface LaravelReservation {
  id: string;
  status: string;
  service_sku: string | null;
  scheduled_at: string | null;
  version: number;
  actions: string[];
}

type CardStatus = BookingSummary['status'];

export function toBookingCard(reservation: LaravelReservation): CustomerBookingCard {
  return {
    id: reservation.id,
    // Same 11-state vocabulary both sides (booking-rules ↔ BookingLifecycle).
    status: reservation.status as CardStatus,
    date: reservation.scheduled_at ? reservation.scheduled_at.slice(0, 10) : '',
    // Single-service bookings in v1: the card count is structural.
    itemCount: 1,
  };
}

const STATIC_ACTIONS: BookingAction[] = ['view-puja', 'contact-support'];

export function toBookingDetails(reservation: LaravelReservation): CustomerBookingDetails {
  const actions: BookingAction[] = [];
  if (reservation.actions.includes('cancel')) actions.push('cancel');
  if (reservation.actions.includes('reschedule')) actions.push('reschedule');
  actions.push(...STATIC_ACTIONS);
  if (['COMPLETED', 'CANCELLED'].includes(reservation.status)) actions.push('book-again');
  return {
    id: reservation.id,
    version: reservation.version,
    status: reservation.status as CustomerBookingDetails['status'],
    schedule: {
      date: reservation.scheduled_at ? reservation.scheduled_at.slice(0, 10) : '',
      time: reservation.scheduled_at ? reservation.scheduled_at.slice(11, 16) : '',
    },
    // BFF enriches puja names via catalog in a later slice; the SKU is the
    // stable reference until then.
    puja: { id: reservation.service_sku ?? reservation.id, name: reservation.service_sku ?? 'Ritual service' },
    actions,
  };
}
