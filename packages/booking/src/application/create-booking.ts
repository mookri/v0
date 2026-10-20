import { BookingRepository } from '@navdhya/booking/src/ports/BookingRepository';
import { CreateBookingInput } from '@navdhya/booking/src/contracts/CreateBooking';
import { Booking } from '@navdhya/booking/src/domain/Booking';

export async function createBooking(repo: BookingRepository, input: CreateBookingInput): Promise<Booking> {
  const now = new Date();
  const booking: Booking = {
    id: `booking_${Date.now()}`,
    customer: input.customer,
    items: input.items,
    schedule: input.schedule,
    status: 'DRAFT',
    priceQuoteId: input.priceQuoteId,
    createdAt: now,
    updatedAt: now,
  };
  await repo.save(booking);
  return booking;
}
