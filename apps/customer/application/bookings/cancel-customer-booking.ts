import type { Booking } from '../../../../packages/booking/src/domain/Booking';
import { canCancel } from '../../../../packages/booking/src/domain/booking-rules';

export interface CancelResult {
  ok: boolean;
  idempotencyKey: string;
  reason?: 'INVALID_TRANSITION' | 'FORBIDDEN' | 'NOT_FOUND';
}

export function cancelCustomerBooking(
  booking: Booking | null,
  actorUserId: string,
  idempotencyKey: string,
): CancelResult {
  if (!booking) return { ok: false, idempotencyKey, reason: 'NOT_FOUND' };
  if (booking.customer.userId !== actorUserId) return { ok: false, idempotencyKey, reason: 'FORBIDDEN' };
  if (!canCancel(booking)) return { ok: false, idempotencyKey, reason: 'INVALID_TRANSITION' };
  return { ok: true, idempotencyKey };
}
