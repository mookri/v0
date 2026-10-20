import { BookingId } from '@navdhya/booking/src/domain/BookingId';
import { BookingStatus } from '@navdhya/booking/src/domain/BookingStatus';

export interface BookingSummary {
  id: BookingId;
  status: BookingStatus;
  itemCount: number;
  startTime: Date;
  createdAt: Date;
}
