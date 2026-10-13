import { BookingRepository } from '@navdhya/booking/src/ports/BookingRepository';
import { BookingId } from '@navdhya/booking/src/domain/BookingId';
import { canTransition } from '@navdhya/booking/src/domain/booking-rules';
import { BookingSignalSink } from '@navdhya/booking/src/ports/BookingSignalSink';

/**
 * Booking state-transition boundary (plan B2): confirmation emits
 * `booking_confirmed` through the caller-injected signal sink BEFORE control
 * leaves the function — so emission runs inside whatever transaction wraps
 * the call (the sink closes over `tx.outbox`). The capability declares the
 * port; composition/tests wire the analytics emitter adapter (architecture
 * rule: capabilities never import analytics).
 *
 * `emit` carries value/currency/serviceCategory because the booking domain
 * does NOT own pricing truth (items reference `priceQuoteId`) or catalog
 * category — the caller with access to those facts supplies them; fabricating
 * zeros here would poison reconciliation.
 */
export interface BookingConfirmedEmit {
  sink: BookingSignalSink;
  value: number;
  currency: string;
  serviceCategory: string;
  city?: string;
}

export async function confirmBooking(
  repo: BookingRepository,
  id: BookingId,
  emit?: BookingConfirmedEmit,
): Promise<void> {
  const booking = await repo.findById(id);
  if (!booking) throw new Error('Booking not found');
  if (!canTransition(booking.status, 'CONFIRMED') && !canTransition(booking.status, 'PAYMENT_CONFIRMED')) {
    throw new Error(`Cannot confirm booking from status ${booking.status}`);
  }
  booking.status = booking.status === 'PAYMENT_CONFIRMED' ? 'CONFIRMED' : booking.status;
  if (booking.status === 'DRAFT') booking.status = 'CONFIRMED';
  booking.updatedAt = new Date();
  await repo.update(booking);
  if (emit) {
    await emit.sink.bookingConfirmed({
      aggregateId: booking.id,
      value: emit.value,
      currency: emit.currency,
      serviceCategory: emit.serviceCategory,
      ...(emit.city !== undefined ? { city: emit.city } : {}),
    });
  }
}
