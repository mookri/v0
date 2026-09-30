import { BookingItem } from '@navdhya/booking/src/domain/BookingItem';
import { BookingSchedule } from '@navdhya/booking/src/domain/BookingSchedule';
import { BookingCustomer } from '@navdhya/booking/src/domain/BookingCustomer';

export interface CreateBookingInput {
  customer: BookingCustomer;
  items: BookingItem[];
  schedule: BookingSchedule;
  priceQuoteId?: string;
}
