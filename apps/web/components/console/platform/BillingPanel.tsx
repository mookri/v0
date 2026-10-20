import {
  ConsoleEmptyState,
  ConsolePanel,
  ConsoleResultBody,
  ConsoleTable,
} from '@/components/console/ConsolePanel';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/platform/billing` — W2. Backed by Laravel GET /api/v1/platform/billing
 * (`platform:billing:view`): the configured tenant's plan, status, currency and
 * (for now) an empty invoice list, reported honestly rather than faked.
 */

export interface BillingInfo {
  plan_code: string;
  status: string;
  base_currency: string;
  invoices: { id: string; amount: string; status: string; issued_at: string }[];
}

export function BillingTable({ info }: { info: BillingInfo }) {
  const rows: { label: string; value: string }[] = [
    { label: 'Plan', value: info.plan_code },
    { label: 'Status', value: info.status },
    { label: 'Base currency', value: info.base_currency },
    { label: 'Invoices on file', value: String(info.invoices?.length ?? 0) },
  ];

  return (
    <div className="space-y-6">
      <ConsoleTable headers={['Field', 'Value']}>
        {rows.map((row) => (
          <tr key={row.label} data-testid="billing-row">
            <td className="px-4 py-3 font-medium">{row.label}</td>
            <td className="px-4 py-3 text-muted-foreground">{row.value || '—'}</td>
          </tr>
        ))}
      </ConsoleTable>

      {Array.isArray(info.invoices) && info.invoices.length > 0 ? (
        <ConsoleTable headers={['Invoice', 'Amount', 'Status', 'Issued']}>
          {info.invoices.map((invoice) => (
            <tr key={invoice.id} data-testid="invoice-row">
              <td className="px-4 py-3 font-mono text-xs">{invoice.id}</td>
              <td className="px-4 py-3">{invoice.amount}</td>
              <td className="px-4 py-3 text-muted-foreground">{invoice.status}</td>
              <td className="px-4 py-3 text-muted-foreground">{invoice.issued_at}</td>
            </tr>
          ))}
        </ConsoleTable>
      ) : (
        <ConsoleEmptyState
          title="No invoices yet"
          description="Invoice history is not yet wired in the kernel; only the active plan is reported above."
        />
      )}
    </div>
  );
}

export async function BillingPanel() {
  const result = readConsole<BillingInfo>(await bffJSON<BillingInfo>('/api/platform/billing'));

  return (
    <ConsolePanel
      title="Billing & Plans"
      description="Subscriptions, invoices, payments, and financial overview."
    >
      <ConsoleResultBody result={result} resource="billing">
        {(info) => <BillingTable info={info} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
