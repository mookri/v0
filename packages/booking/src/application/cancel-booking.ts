import { BookingRepository } from '../ports/BookingRepository';
import { BookingId } from '../domain/BookingId';
import { canCancel } from '../domain/booking-rules';

export async function cancelBooking(repo: BookingRepository, id: BookingId): Promise<void> {
  const booking = await repo.findById(id);
  if (!booking) throw new Error('Booking not found');
  if (!canCancel(booking)) throw new Error(`Cannot cancel booking in status ${booking.status}`);
  booking.status = 'CANCELLED';
  booking.updatedAt = new Date();
  await repo.update(booking);
}
