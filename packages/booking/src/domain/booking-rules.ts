import {
  BOOKING_CANCELLABLE_STATUSES,
  BOOKING_LEGAL_TRANSITIONS,
  BOOKING_RESCHEDULABLE_STATUSES,
} from '@navdhya/contracts/src/generated/booking-lifecycle';
import { Booking } from './Booking';
import { BookingStatus } from './BookingStatus';

export function canTransition(from: BookingStatus, to: BookingStatus): boolean {
  return BOOKING_LEGAL_TRANSITIONS[from]?.includes(to) ?? false;
}

export function canCancel(booking: Booking): boolean {
  return BOOKING_CANCELLABLE_STATUSES.includes(booking.status);
}

export function canReschedule(booking: Booking): boolean {
  return BOOKING_RESCHEDULABLE_STATUSES.includes(booking.status);
}

export function canConfirm(booking: Booking): boolean {
  return booking.status === 'PAYMENT_CONFIRMED';
}

export function getBookingActions(booking: Booking): string[] {
  const actions: string[] = [];
  if (canCancel(booking)) actions.push('cancel');
  if (canReschedule(booking)) actions.push('reschedule');
  if (canConfirm(booking)) actions.push('confirm');
  return actions;
}
