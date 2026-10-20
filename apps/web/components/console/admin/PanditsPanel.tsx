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
 * `/admin/pandits` — W1b. Backed by Laravel GET /api/v1/pandit/admin/profiles
 * (cursor-paginated, `capability:pandit:profile:view`). The backend returns
 * `{ id, display_name, status, verification_status }` per profile; the panel
 * normalises the upper-case status strings so the curated badge variants apply.
 */

export interface PanditRow {
  id: string;
  display_name: string;
  status: string;
  verification_status: string;
}

export function PanditsTable({ rows }: { rows: PanditRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No pandits in this tenant"
        description="Pandit profiles appear here once they are submitted for review."
      />
    );
  }

  return (
    <ConsoleTable headers={['Name', 'Account status', 'Verification']}>
      {rows.map((pandit) => (
        <tr key={pandit.id} data-testid="pandit-row">
          <td className="px-4 py-3">{pandit.display_name || '—'}</td>
          <td className="px-4 py-3">
            <StatusBadge status={pandit.status.toLowerCase()} />
          </td>
          <td className="px-4 py-3">
            <StatusBadge status={pandit.verification_status.toLowerCase()} />
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function PanditsPanel() {
  const result = readConsole<PanditRow[]>(await bffJSON<PanditRow[]>('/api/admin/pandits'));

  return (
    <ConsolePanel
      title="Pandits"
      description="Pandit verification, quality, and availability monitoring."
    >
      <ConsoleResultBody result={result} resource="pandit profiles">
        {(rows) => <PanditsTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
