import { customerNavigation } from './navigation-model';

export function SidebarNavigation({ activeRouteId }: { activeRouteId?: string }) {
  return (
    <nav aria-label="Customer primary" data-shell="sidebar-nav">
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
          </a>
        ))}
    </nav>
  );
}
