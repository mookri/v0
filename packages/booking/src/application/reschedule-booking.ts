import { BookingRepository } from '@navdhya/booking/src/ports/BookingRepository';
import { BookingId } from '@navdhya/booking/src/domain/BookingId';
import { BookingSchedule } from '@navdhya/booking/src/domain/BookingSchedule';
import { canReschedule } from '@navdhya/booking/src/domain/booking-rules';

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
