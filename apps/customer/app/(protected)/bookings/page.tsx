import { listCustomerBookings } from '../../../application/bookings/list-customer-bookings';
import { BookingListPage } from '../../../presentation/bookings/BookingListPage';

export default function BookingsPage() {
  const bookings = listCustomerBookings([]);
  return <BookingListPage bookings={bookings} />;
}
