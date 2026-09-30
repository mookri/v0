import type { Booking } from '../../../packages/booking/src/domain/Booking';
import type { BookingId } from '../../../packages/booking/src/domain/BookingId';
import type { BookingRepository } from '../../../packages/booking/src/ports/BookingRepository';
import type { DbClient } from '../client';

// Capability-owned tables: bookings, booking_items, booking_status_history.
// No cross-capability JOIN writes here — Puja/Pandit/Pricing come via contracts.
export class PostgresBookingRepository implements BookingRepository {
  private readonly store = new Map<BookingId, Booking>();

  constructor(private readonly _db: DbClient) {}

  async findById(id: BookingId): Promise<Booking | null> {
    return this.store.get(id) ?? null;
  }

  async save(booking: Booking): Promise<void> {
    this.store.set(booking.id, booking);
  }

  async update(booking: Booking): Promise<void> {
    if (!this.store.has(booking.id)) throw new Error('Booking not found');
    this.store.set(booking.id, booking);
  }

  async listByUser(userId: string): Promise<Booking[]> {
    return [...this.store.values()].filter((b) => b.customer.userId === userId);
  }
}
