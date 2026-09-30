import { Booking } from '@navdhya/booking/src/domain/Booking';
import { BookingId } from '@navdhya/booking/src/domain/BookingId';

export interface BookingRepository {
  findById(id: BookingId): Promise<Booking | null>;
  save(booking: Booking): Promise<void>;
  update(booking: Booking): Promise<void>;
  listByUser(userId: string): Promise<Booking[]>;
}
