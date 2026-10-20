import { BookingListPage } from '@/presentation/bookings/BookingListPage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';
import type { CustomerBookingCard } from '@/application/bookings/list-customer-bookings';

// The BFF GET /api/bookings already maps reservations to the card
// shape (id/status/date/itemCount) — the page renders that list live
// (B1) and degrades to an empty list on auth/backend failures, like
// the notifications page. Live per-user data: never prerendered.
export const dynamic = 'force-dynamic';

export default async function BookingsPage() {
  try {
    const api = await getServerCustomerApi();
    const data = await api.getBookings();
    const items = ((data as { items?: CustomerBookingCard[] })?.items ?? []).map((b) => ({
      id: String(b.id),
      status: b.status,
      date: String(b.date ?? ''),
      itemCount: Number(b.itemCount ?? 1),
    }));
    return <BookingListPage bookings={items} />;
  } catch {
    return <BookingListPage bookings={[]} />;
  }
}
