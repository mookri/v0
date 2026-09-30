import { customerNavigation } from './navigation-model';

export function BottomNavigation({ activeRouteId, badges }: { activeRouteId?: string; badges?: Record<string, number> }) {
  return (
    <nav aria-label="Customer primary" data-shell="bottom-nav">
      {customerNavigation
        .filter((n) => n.visibility === 'visible')
        .map((item) => (
          <a
            key={item.id}
            href={item.href}
            aria-current={item.routeId === activeRouteId ? 'page' : undefined}
            data-route-id={item.routeId}
          >
            {item.label}
            {badges?.[item.routeId] ? <span data-badge={badges[item.routeId]} /> : null}
          </a>
        ))}
    </nav>
  );
}
