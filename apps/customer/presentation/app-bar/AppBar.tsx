'use client';

import { AppBarConfig } from '@/presentation/shells/shell-contract';
import { NotificationBadge } from '@/presentation/notifications/NotificationBadge';

export function AppBar({ config }: { config: AppBarConfig }) {
  return (
    <header data-shell="app-bar" data-mode={config.mode ?? 'standard'}>
      {config.mode === 'back' ? <a href=".." aria-label="Back">Back</a> : null}
      {config.title ? <h1>{config.title}</h1> : null}
      {config.subtitle ? <p>{config.subtitle}</p> : null}
      <div className="flex items-center gap-2">
        {config.trailing?.map((a) => (
          <a key={a.id} href={a.href ?? '#'}>
            {a.label}
          </a>
        ))}
        <NotificationBadge />
      </div>
    </header>
  );
}
