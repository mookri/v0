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
 * `/admin/support` — W1b slice 2. Backed by Laravel
 * GET /api/v1/customers/support/staff/tickets (`capability:customers:support:view`):
 * the tenant-wide support queue, newest first, with the filing customer masked
 * the way every other surface masks contact points.
 */

export interface SupportTicketRow {
  id: string;
  object?: string;
  subject: string;
  category: string;
  status: string;
  customer: { id: string; name: string; email: string } | null;
  created_at: string | null;
}

export function SupportTable({ rows }: { rows: SupportTicketRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No support tickets"
        description="Escalated customer and partner support issues appear here."
      />
    );
  }

  return (
    <ConsoleTable headers={['Subject', 'Category', 'Customer', 'Status']}>
      {rows.map((ticket) => (
        <tr key={ticket.id} data-testid="support-row">
          <td className="px-4 py-3">{ticket.subject}</td>
          <td className="px-4 py-3 text-muted-foreground">{ticket.category}</td>
          <td className="px-4 py-3 text-muted-foreground">
            {ticket.customer ? `${ticket.customer.name} (${ticket.customer.email})` : '—'}
          </td>
          <td className="px-4 py-3">
            <StatusBadge status={ticket.status.toLowerCase()} />
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function SupportPanel() {
  const result = readConsole<SupportTicketRow[]>(await bffJSON<SupportTicketRow[]>('/api/admin/support'));

  return (
    <ConsolePanel
      title="Support"
      description="Escalated customer and partner support issues."
    >
      <ConsoleResultBody result={result} resource="support tickets">
        {(rows) => <SupportTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
