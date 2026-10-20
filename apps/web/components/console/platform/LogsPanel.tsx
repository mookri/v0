import { StatusBadge } from '@/components/dashboard/StatusBadge';
import {
  ConsoleEmptyState,
  ConsolePanel,
  ConsoleResultBody,
  ConsoleTable,
} from '@/components/console/ConsolePanel';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/platform/logs` — W2. Backed by Laravel GET /api/v1/platform/audit/logs
 * (`platform:audit:view`): the hash-chained audit trail for the configured
 * tenant, newest first. Read-only — the chain is never mutated from here.
 */

export interface AuditLogRow {
  id: string;
  object?: string;
  actor: { principal_id?: string; kind?: string; user_id?: string } | null;
  action: string;
  resource: { module?: string; resource_type?: string; resource_id?: string } | null;
  request_id: string | null;
  occurred_at: string | null;
}

export function LogsTable({ rows }: { rows: AuditLogRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No audit entries"
        description="Audit rows appear here as platform actions are recorded."
      />
    );
  }

  return (
    <ConsoleTable headers={['Occurred', 'Action', 'Resource', 'Actor', 'Request ID']}>
      {rows.map((row) => (
        <tr key={row.id} data-testid="audit-row">
          <td className="px-4 py-3 text-muted-foreground">
            {row.occurred_at ? new Date(row.occurred_at).toLocaleString() : '—'}
          </td>
          <td className="px-4 py-3 font-mono text-xs">{row.action}</td>
          <td className="px-4 py-3 text-muted-foreground">
            {row.resource ? `${row.resource.module ?? ''}/${row.resource.resource_type ?? ''}`.replace(/^\/|\/$/g, '') || '—' : '—'}
          </td>
          <td className="px-4 py-3 text-muted-foreground">{row.actor?.principal_id ?? row.actor?.user_id ?? '—'}</td>
          <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{row.request_id ?? '—'}</td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function LogsPanel() {
  const result = readConsole<AuditLogRow[]>(await bffJSON<AuditLogRow[]>('/api/platform/logs'));

  return (
    <ConsolePanel
      title="Audit Logs"
      description="System-wide activity log for the configured tenant (hash-chained)."
    >
      <ConsoleResultBody result={result} resource="audit entries">
        {(rows) => <LogsTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
