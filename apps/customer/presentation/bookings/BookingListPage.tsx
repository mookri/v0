import { CustomerBookingCard } from '@/application/bookings/list-customer-bookings';

export function BookingListPage({ bookings, error }: { bookings: CustomerBookingCard[]; error?: string }) {
  if (error) {
    return (
      <main>
        <h1>Bookings</h1>
        <p role="alert">Could not load bookings.</p>
        <a href="/bookings">Retry</a> <a href="/">Go home</a>
      </main>
    );
  }
  if (bookings.length === 0) {
    return (
      <main>
        <h1>Bookings</h1>
        <p>No bookings yet. Explore rituals to get started.</p>
      </main>
    );
  }
  return (
    <main>
      <h1>Bookings</h1>
      <ul>
        {bookings.map((b) => (
          <li key={b.id}>
            <a href={`/bookings/${b.id}`}>
              {b.date} — {b.status}
            </a>
          </li>
        ))}
      </ul>
    </main>
  );
}
