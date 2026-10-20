import {
  ConsolePanel,
  ConsoleResultBody,
} from '@/components/console/ConsolePanel';
import { ReindexButton } from '@/components/console/platform/ReindexButton';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/platform/system` — W2. Backed by Laravel GET /api/v1/platform/system
 * (`platform:ops:view`): readiness checks (central DB, tenant DB, cache, queue).
 */

export interface SystemHealth {
  status: string;
  checks: Record<string, string>;
}

export function SystemHealthCard({ health }: { health: SystemHealth }) {
  const entries = Object.entries(health.checks);

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2">
        <span
          className={`size-3 rounded-full ${health.status === 'ready' ? 'bg-emerald-500' : 'bg-red-500'}`}
          aria-hidden="true"
        />
        <p className="font-semibold">{health.status === 'ready' ? 'All systems operational' : 'Degraded'}</p>
      </div>
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {entries.map(([name, status]) => (
          <div
            key={name}
            data-testid="system-check"
            className="rounded-2xl border border-border bg-card p-4 text-center"
          >
            <p className={`text-sm font-semibold ${status === 'ok' ? 'text-emerald-600' : 'text-red-600'}`}>
              {status === 'ok' ? 'OK' : 'Failed'}
            </p>
            <p className="text-xs text-muted-foreground">{name.replace(/_/g, ' ')}</p>
          </div>
        ))}
      </div>
    </div>
  );
}

export async function SystemPanel() {
  const result = readConsole<SystemHealth>(await bffJSON<SystemHealth>('/api/platform/system'));

  return (
    <ConsolePanel
      title="System Health"
      description="Server status, uptime, performance, and infrastructure."
    >
      <ConsoleResultBody result={result} resource="system health">
        {(health) => <SystemHealthCard health={health} />}
      </ConsoleResultBody>

      <div className="rounded-2xl border border-border bg-card p-4">
        <h3 className="text-sm font-semibold">Search index</h3>
        <p className="mt-1 text-xs text-muted-foreground">
          Rebuild the search index from the current corpus (runs the
          <code className="mx-1">search:reindex</code> job). Capability
          <code className="mx-1">search:index:reindex</code> required.
        </p>
        <div className="mt-3">
          <ReindexButton />
        </div>
      </div>
    </ConsolePanel>
  );
}
