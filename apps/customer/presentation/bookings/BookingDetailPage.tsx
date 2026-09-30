import { CustomerBookingDetails } from '../../application/bookings/get-customer-booking-details';

export function BookingDetailPage({ details, error }: { details: CustomerBookingDetails | null; error?: string }) {
  if (error) {
    return (
      <main>
        <h1>Booking unavailable</h1>
        <p role="alert">{error}</p>
        <a href="/bookings">Back to bookings</a>
      </main>
    );
  }
  if (!details) {
    return (
      <main>
        <h1>Booking not found</h1>
        <a href="/bookings">Back to bookings</a>
      </main>
    );
  }
  return (
    <main>
      <h1>Booking</h1>
      <p>Status: {details.status}</p>
      <p>
        {details.schedule.date} at {details.schedule.time}
      </p>
      <p>Puja: {details.puja.name}</p>
      {details.pandit ? <p>Pandit: {details.pandit.name}</p> : null}
      <ul>
        {details.actions.map((a) => (
          <li key={a}>{a}</li>
        ))}
      </ul>
    </main>
  );
}
