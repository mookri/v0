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
 * `/admin/payouts` — W2. Backed by Laravel GET /api/v1/platform/ops/payouts
 * (`platform:ops:view`): platform-wide payout review.
 */

export interface PayoutRow {
  id: string;
  object?: string;
  vendor_id: string;
  period: string;
  status: string;
  amount_minor: number;
  currency: string;
  reference: string | null;
  created_at: string | null;
}

export function PayoutsTable({ rows }: { rows: PayoutRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No payouts on file"
        description="Payout review and finance exceptions appear here."
      />
    );
  }

  return (
    <ConsoleTable headers={['Vendor', 'Period', 'Status', 'Amount', 'Reference']}>
      {rows.map((payout) => (
        <tr key={payout.id} data-testid="payout-row">
          <td className="px-4 py-3 font-mono text-xs">{payout.vendor_id}</td>
          <td className="px-4 py-3">{payout.period}</td>
          <td className="px-4 py-3">
            <StatusBadge status={payout.status.toLowerCase()} />
          </td>
          <td className="px-4 py-3">
            {payout.currency} {(payout.amount_minor / 100).toFixed(2)}
          </td>
          <td className="px-4 py-3 text-muted-foreground">{payout.reference ?? '—'}</td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function PayoutsPanel() {
  const result = readConsole<PayoutRow[]>(await bffJSON<PayoutRow[]>('/api/admin/payouts'));

  return (
    <ConsolePanel
      title="Payouts"
      description="Payout review and finance exceptions."
    >
      <ConsoleResultBody result={result} resource="payouts">
        {(rows) => <PayoutsTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
