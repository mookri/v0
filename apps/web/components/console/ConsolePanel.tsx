import type { ReactNode } from 'react';
import type { ConsoleResult } from '@/lib/console/envelope';

/**
 * Console presentation kit — W1a,
 * `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`.
 *
 * Pure (no `next/*`, no data access) so it renders identically in a server
 * component, in `renderToStaticMarkup`, and in jsdom. The palette follows the
 * locked Navdhya distribution: Ivory canvas, Aubergine structure, Flame accents
 * for attention states, no green in the identity (emerald appears only as a
 * status token already established by the dashboards).
 */

export function ConsolePanel({
  title,
  description,
  children,
}: {
  title: string;
  description?: string;
  children: ReactNode;
}) {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-serif font-bold">{title}</h1>
        {description ? <p className="text-muted-foreground text-sm">{description}</p> : null}
      </div>
      {children}
    </div>
  );
}

const NOTICE_TONE_CLASS = {
  info: 'border-border bg-card text-foreground',
  warning: 'border-amber-500/40 bg-amber-500/10 text-foreground',
  danger: 'border-red-500/40 bg-red-500/10 text-foreground',
} as const;

const NOTICE_DOT_CLASS = {
  info: 'bg-primary',
  warning: 'bg-amber-500',
  danger: 'bg-red-500',
} as const;

export function ConsoleNotice({
  tone = 'info',
  title,
  children,
}: {
  tone?: keyof typeof NOTICE_TONE_CLASS;
  title: string;
  children?: ReactNode;
}) {
  return (
    <div
      className={`flex items-start gap-3 rounded-2xl border p-4 text-sm ${NOTICE_TONE_CLASS[tone]}`}
      role={tone === 'danger' ? 'alert' : 'status'}
    >
      <span className={`mt-1 size-2 shrink-0 rounded-full ${NOTICE_DOT_CLASS[tone]}`} aria-hidden="true" />
      <div className="min-w-0">
        <p className="font-semibold">{title}</p>
        {children ? <div className="mt-1 text-muted-foreground">{children}</div> : null}
      </div>
    </div>
  );
}

export function ConsoleEmptyState({ title, description }: { title: string; description?: string }) {
  return (
    <div className="rounded-2xl border border-dashed border-border bg-card px-6 py-12 text-center">
      <p className="font-medium">{title}</p>
      {description ? <p className="mt-1 text-sm text-muted-foreground">{description}</p> : null}
    </div>
  );
}

export function ConsoleTable({ headers, children }: { headers: string[]; children: ReactNode }) {
  return (
    <div className="overflow-x-auto rounded-2xl border border-border bg-card">
      <table className="w-full text-sm">
        <thead className="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
          <tr>
            {headers.map((header) => (
              <th key={header} scope="col" className="px-4 py-3 font-semibold">
                {header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-border">{children}</tbody>
      </table>
    </div>
  );
}

/**
 * Maps a console read result to JSX so no panel can quietly present an
 * unconfigured or failed backend as an empty table.
 *
 * `children` only ever receives a real payload — `data` is called with a
 * non-null value, so the render branch is the one that touches real rows.
 */
export function ConsoleResultBody<T>({
  result,
  resource,
  children,
}: {
  result: ConsoleResult<T>;
  /** Lower-case label used in notices, e.g. `roles`. */
  resource: string;
  children: (value: T) => ReactNode;
}) {
  if (result.kind === 'fallback') {
    return (
      <ConsoleNotice tone="warning" title="Backend not configured">
        Set <code>CUSTOMER_BACKEND=laravel</code>, <code>NAVDHYA_API_BASE</code> and{' '}
        <code>CUSTOMER_TENANT_ID</code> to load real {resource}. This panel deliberately does not render an
        empty table, which would read as &ldquo;no {resource} exist&rdquo;.
      </ConsoleNotice>
    );
  }

  if (result.kind === 'error') {
    return (
      <ConsoleNotice tone="danger" title={`Could not load ${resource} (${result.code})`}>
        {result.message}
      </ConsoleNotice>
    );
  }

  if (result.value === null) {
    return (
      <ConsoleNotice tone="warning" title={`No ${resource} payload`}>
        The backend answered without a payload, so there is nothing to render.
      </ConsoleNotice>
    );
  }

  return <>{children(result.value)}</>;
}
