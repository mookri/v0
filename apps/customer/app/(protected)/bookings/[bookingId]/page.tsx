import { BookingDetailPage } from '@/presentation/bookings/BookingDetailPage';
import { customerApi } from '@/adapters/backend/customer-api-client';
import type { CustomerBookingDetails } from '@/application/bookings/get-customer-booking-details';

export const dynamic = 'force-dynamic';

export default async function BookingDetail({ params }: { params: Promise<{ bookingId: string }> }) {
  const { bookingId } = await params;
  try {
    const [details, history] = await Promise.all([
      customerApi.getBooking(bookingId),
      customerApi.getBookingHistory(bookingId),
    ]);
    return <BookingDetailPage details={details as CustomerBookingDetails | null} history={history as Array<{ status: string; timestamp: string; note?: string }>} />;
  } catch (e: any) {
    if (e.status === 404) {
      return <BookingDetailPage details={null} error="Booking not found" history={[]} />;
    }
    return <BookingDetailPage details={null} error="Failed to load booking" history={[]} />;
  }
}