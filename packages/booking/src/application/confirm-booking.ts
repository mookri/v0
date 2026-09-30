import { BookingRepository } from '@navdhya/booking/src/ports/BookingRepository';
import { BookingId } from '@navdhya/booking/src/domain/BookingId';
import { canTransition } from '@navdhya/booking/src/domain/booking-rules';

export async function confirmBooking(repo: BookingRepository, id: BookingId): Promise<void> {
  const booking = await repo.findById(id);
  if (!booking) throw new Error('Booking not found');
  if (!canTransition(booking.status, 'CONFIRMED') && !canTransition(booking.status, 'PAYMENT_CONFIRMED')) {
    throw new Error(`Cannot confirm booking from status ${booking.status}`);
  }
  booking.status = booking.status === 'PAYMENT_CONFIRMED' ? 'CONFIRMED' : booking.status;
  if (booking.status === 'DRAFT') booking.status = 'CONFIRMED';
  booking.updatedAt = new Date();
  await repo.update(booking);
}
