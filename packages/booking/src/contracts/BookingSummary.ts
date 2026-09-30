import { BookingId } from '../domain/BookingId';
import { BookingStatus } from '../domain/BookingStatus';

export interface BookingSummary {
  id: BookingId;
  status: BookingStatus;
  itemCount: number;
  startTime: Date;
  createdAt: Date;
}
