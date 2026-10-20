import type { Booking } from '@navdhya/booking/src/domain/Booking';
import { canReschedule } from '@navdhya/booking/src/domain/booking-rules';

export interface RescheduleResult {
  ok: boolean;
  idempotencyKey: string;
  reason?: 'INVALID_TRANSITION' | 'FORBIDDEN' | 'NOT_FOUND' | 'SLOT_UNAVAILABLE';
}

export function rescheduleCustomerBooking(
  booking: Booking | null,
  actorUserId: string,
  newStartTime: Date,
  slotAvailable: boolean,
  idempotencyKey: string,
): RescheduleResult {
  if (!booking) return { ok: false, idempotencyKey, reason: 'NOT_FOUND' };
  if (booking.customer.userId !== actorUserId) return { ok: false, idempotencyKey, reason: 'FORBIDDEN' };
  if (!canReschedule(booking)) return { ok: false, idempotencyKey, reason: 'INVALID_TRANSITION' };
  if (!slotAvailable) return { ok: false, idempotencyKey, reason: 'SLOT_UNAVAILABLE' };
  void newStartTime;
  return { ok: true, idempotencyKey };
}
