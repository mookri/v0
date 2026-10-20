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
 * `/admin/vendors` — W1b. Backed by Laravel GET /api/v1/vendor/admin/vendors
 * (cursor-paginated, `capability:vendor:profile:view`). The backend shapes
 * each row with `name`, `city`, `status`, `verification_status`.
 */

export interface VendorRow {
  id: string;
  name: string;
  city: string;
  status: string;
  verification_status: string;
}

export function VendorsTable({ rows }: { rows: VendorRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No vendors in this tenant"
        description="Supply partners appear here once their applications are approved."
      />
    );
  }

  return (
    <ConsoleTable headers={['Name', 'City', 'Status', 'Verification']}>
      {rows.map((vendor) => (
        <tr key={vendor.id} data-testid="vendor-row">
          <td className="px-4 py-3">{vendor.name || '—'}</td>
          <td className="px-4 py-3 text-muted-foreground">{vendor.city || '—'}</td>
          <td className="px-4 py-3">
            <StatusBadge status={vendor.status.toLowerCase()} />
          </td>
          <td className="px-4 py-3">
            <StatusBadge status={vendor.verification_status.toLowerCase()} />
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function VendorsPanel() {
  const result = readConsole<VendorRow[]>(await bffJSON<VendorRow[]>('/api/admin/vendors'));

  return (
    <ConsolePanel
      title="Vendors"
      description="Supply partner management and fulfillment oversight."
    >
      <ConsoleResultBody result={result} resource="vendors">
        {(rows) => <VendorsTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
