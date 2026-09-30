import { BookingRepository } from '../ports/BookingRepository';
import { BookingId } from '../domain/BookingId';
import { BookingSchedule } from '../domain/BookingSchedule';
import { canReschedule } from '../domain/booking-rules';

export async function rescheduleBooking(
  repo: BookingRepository,
  id: BookingId,
  schedule: BookingSchedule,
): Promise<void> {
  const booking = await repo.findById(id);
  if (!booking) throw new Error('Booking not found');
  if (!canReschedule(booking)) throw new Error(`Cannot reschedule booking in status ${booking.status}`);
  booking.schedule = schedule;
  booking.updatedAt = new Date();
  await repo.update(booking);
}
