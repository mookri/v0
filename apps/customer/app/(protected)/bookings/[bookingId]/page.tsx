import { BookingDetailPage } from '@/presentation/bookings/BookingDetailPage';

export default async function BookingDetail({ params }: { params: Promise<{ bookingId: string }> }) {
  await params;
  return <BookingDetailPage details={null} />;
}
