import { BookingItem } from '../domain/BookingItem';
import { BookingSchedule } from '../domain/BookingSchedule';
import { BookingCustomer } from '../domain/BookingCustomer';

export interface CreateBookingInput {
  customer: BookingCustomer;
  items: BookingItem[];
  schedule: BookingSchedule;
  priceQuoteId?: string;
}
