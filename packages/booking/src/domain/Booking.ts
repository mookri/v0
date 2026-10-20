import { BookingId } from './BookingId';
import { BookingStatus } from './BookingStatus';
import { BookingItem } from './BookingItem';
import { BookingSchedule } from './BookingSchedule';
import { BookingCustomer } from './BookingCustomer';

export interface Booking {
  id: BookingId;
  customer: BookingCustomer;
  items: BookingItem[];
  schedule: BookingSchedule;
  status: BookingStatus;
  priceQuoteId?: string;
  createdAt: Date;
  updatedAt: Date;
}
