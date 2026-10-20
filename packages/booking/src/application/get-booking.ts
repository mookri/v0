import { BookingRepository } from '@navdhya/booking/src/ports/BookingRepository';
import { BookingId } from '@navdhya/booking/src/domain/BookingId';
import { Booking } from '@navdhya/booking/src/domain/Booking';

export async function getBooking(repo: BookingRepository, id: BookingId): Promise<Booking | null> {
  return repo.findById(id);
}
