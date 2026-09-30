import { BookingRepository } from '../ports/BookingRepository';
import { BookingId } from '../domain/BookingId';
import { Booking } from '../domain/Booking';

export async function getBooking(repo: BookingRepository, id: BookingId): Promise<Booking | null> {
  return repo.findById(id);
}
