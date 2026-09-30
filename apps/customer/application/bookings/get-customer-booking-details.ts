import type { Booking } from '@navdhya/booking/src/domain/Booking';
import { getBookingActions } from '@navdhya/booking/src/domain/booking-rules';

export type BookingAction = 'cancel' | 'reschedule' | 'confirm' | 'view-puja' | 'contact-support' | 'book-again';

export interface CustomerBookingDetails {
  id: string;
  status: Booking['status'];
  schedule: { date: string; time: string };
  puja: { id: string; name: string; image?: string };
  pandit?: { id: string; name: string };
  actions: BookingAction[];
}

export function toCustomerBookingDetails(input: {
  booking: Booking;
  puja: { id: string; name: string; image?: string };
  pandit?: { id: string; name: string };
}): CustomerBookingDetails {
  const { booking, puja, pandit } = input;
  const capabilityActions = getBookingActions(booking);
  const actions: BookingAction[] = [];
  if (capabilityActions.includes('cancel')) actions.push('cancel');
  if (capabilityActions.includes('reschedule')) actions.push('reschedule');
  actions.push('view-puja', 'contact-support');
  if (['COMPLETED', 'CANCELLED'].includes(booking.status)) actions.push('book-again');
  const start = new Date(booking.schedule.startTime);
  return {
    id: booking.id,
    status: booking.status,
    schedule: {
      date: start.toISOString().slice(0, 10),
      time: start.toISOString().slice(11, 16),
    },
    puja,
    pandit,
    actions,
  };
}

export type BookingAccess = { ok: true; booking: Booking } | { ok: false; reason: 'NOT_FOUND' | 'FORBIDDEN' };

export function authorizeBooking(booking: Booking | null, actorUserId: string): BookingAccess {
  if (!booking) return { ok: false, reason: 'NOT_FOUND' };
  if (booking.customer.userId !== actorUserId) return { ok: false, reason: 'NOT_FOUND' };
  return { ok: true, booking };
}
