import { CustomerNotification, resolveRouteReference } from '@/application/notifications/notifications';

export function NotificationsPageView({ items, error }: { items: CustomerNotification[]; error?: string }) {
  if (error) {
    return (
      <main>
        <h1>Notifications</h1>
        <p role="alert">{error}</p>
        <a href="/notifications">Retry</a>
      </main>
    );
  }
  if (items.length === 0) {
    return (
      <main>
        <h1>Notifications</h1>
        <p>You&apos;re all caught up.</p>
      </main>
    );
  }
  return (
    <main>
      <h1>Notifications</h1>
      <ul>
        {items.map((n) => {
          const href = n.destination ? resolveRouteReference(n.destination) : null;
          return (
            <li key={n.id}>
              {href ? <a href={href}>{n.title}</a> : <span>{n.title}</span>}
              <p>{n.message}</p>
            </li>
          );
        })}
      </ul>
    </main>
  );
}
