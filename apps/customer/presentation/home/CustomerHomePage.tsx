import { CustomerHomeViewModel } from '@/application/home/get-customer-home';

export function CustomerHomePage({ home }: { home: CustomerHomeViewModel }) {
  return (
    <main>
      <h1>Hello, {home.greeting.displayName}</h1>
      {home.upcomingBooking ? (
        <section>
          <h2>Upcoming</h2>
          <a href={`/bookings/${home.upcomingBooking.id}`}>{home.upcomingBooking.date}</a>
        </section>
      ) : null}
      <section>
        <h2>Quick actions</h2>
        <ul>
          {home.quickActions.map((a) => (
            <li key={a.id}>
              <a href={a.href}>{a.label}</a>
            </li>
          ))}
        </ul>
      </section>
      <section>
        <h2>Recommended</h2>
        <ul>
          {home.recommendations.map((r) => (
            <li key={r.id}>{r.title}</li>
          ))}
        </ul>
      </section>
    </main>
  );
}
