import { Booking } from './Booking';
import { BookingStatus } from './BookingStatus';

const LEGAL_TRANSITIONS: Record<BookingStatus, BookingStatus[]> = {
  DRAFT: ['PENDING_PAYMENT', 'CANCELLED', 'EXPIRED'],
  PENDING_PAYMENT: ['PAYMENT_CONFIRMED', 'FAILED', 'EXPIRED', 'CANCELLED'],
  PAYMENT_CONFIRMED: ['CONFIRMED', 'REFUNDED', 'FAILED'],
  CONFIRMED: ['ASSIGNED', 'CANCELLED'],
  ASSIGNED: ['IN_PROGRESS', 'CANCELLED'],
  IN_PROGRESS: ['COMPLETED', 'CANCELLED'],
  COMPLETED: [],
  CANCELLED: [],
  FAILED: [],
  EXPIRED: [],
  REFUNDED: [],
};

export function canTransition(from: BookingStatus, to: BookingStatus): boolean {
  return LEGAL_TRANSITIONS[from]?.includes(to) ?? false;
}

export function canCancel(booking: Booking): boolean {
  return ['DRAFT', 'PENDING_PAYMENT', 'CONFIRMED', 'ASSIGNED', 'IN_PROGRESS'].includes(booking.status);
}

export function canReschedule(booking: Booking): boolean {
  return ['CONFIRMED', 'ASSIGNED'].includes(booking.status);
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
