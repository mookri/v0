import { BookingDetailPage } from '../../../../presentation/bookings/BookingDetailPage';

export default function BookingDetail({ params }: { params: { bookingId: string } }) {
  void params;
  return <BookingDetailPage details={null} />;
}
