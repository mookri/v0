import { StatusBadge } from '@/components/dashboard/StatusBadge';
import {
  ConsoleEmptyState,
  ConsolePanel,
  ConsoleResultBody,
  ConsoleTable,
} from '@/components/console/ConsolePanel';
import { RequestInfoButton } from '@/components/console/admin/RequestInfoButton';
import { ReviewActions } from '@/components/console/admin/ReviewActions';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/admin/managers` — W1b slice 2. Backed by Laravel
 * GET /api/v1/onboarding/review/queue (`capability:onboarding:review:view`):
 * the tenant-wide queue of partner applications awaiting a decision, with
 * per-item SLA flags. Decisions themselves (`:approve`/`:reject`/`:request-info`)
 * are step-up gated and land in a follow-up slice; this panel is the queue view.
 */

export interface ReviewQueueRow {
  id: string;
  object?: string;
  track: string;
  status: string;
  submitted_at: string | null;
  sla_breached: boolean | null;
  sla_remaining_ms: number | null;
}

export function ManagersTable({ rows }: { rows: ReviewQueueRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No applications in review"
        description="Partner applications submitted for review appear here with their SLA flags."
      />
    );
  }

  return (
    <ConsoleTable headers={['Track', 'Status', 'Submitted', 'SLA', 'Actions']}>
      {rows.map((item) => (
        <tr key={item.id} data-testid="review-row">
          <td className="px-4 py-3">{item.track}</td>
          <td className="px-4 py-3">
            <StatusBadge status={item.status.toLowerCase()} />
          </td>
          <td className="px-4 py-3 text-muted-foreground">
            {item.submitted_at ? new Date(item.submitted_at).toLocaleString() : '—'}
          </td>
          <td className="px-4 py-3">
            {item.sla_breached === null ? (
              <span className="text-muted-foreground">—</span>
            ) : item.sla_breached ? (
              <span className="font-medium text-red-600">Breached</span>
            ) : (
              <span className="text-muted-foreground">
                {item.sla_remaining_ms !== null
                  ? `${Math.max(0, Math.round(item.sla_remaining_ms / 3600000))}h left`
                  : '—'}
              </span>
            )}
          </td>
          <td className="px-4 py-3">
            <div className="flex flex-wrap items-center gap-2">
              <RequestInfoButton applicationId={item.id} />
              <ReviewActions applicationId={item.id} />
            </div>
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function ManagersPanel() {
  const result = readConsole<ReviewQueueRow[]>(await bffJSON<ReviewQueueRow[]>('/api/admin/managers'));

  return (
    <ConsolePanel
      title="Managers"
      description="Operational leadership assignments and the onboarding review queue."
    >
      <ConsoleResultBody result={result} resource="review items">
        {(rows) => <ManagersTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
