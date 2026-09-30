import { BookingRepository } from '../ports/BookingRepository';
import { BookingSummary } from '../contracts/BookingSummary';

export async function listUserBookings(repo: BookingRepository, userId: string): Promise<BookingSummary[]> {
  const bookings = await repo.listByUser(userId);
  return bookings.map((b) => ({
    id: b.id,
    status: b.status,
    itemCount: b.items.length,
    startTime: b.schedule.startTime,
    createdAt: b.createdAt,
  }));
}
