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
 * `/platform/users` — W1a. Backed by Laravel `GET /api/v1/platform/users`,
 * which lists **memberships of one tenant** (`UserAdminController::index`), not
 * a global user table: `state` is the membership state, `user.status` is the
 * account state. Suspended accounts revoke credentials, so the two badges are
 * deliberately shown side by side rather than collapsed into one.
 */

export interface TenantUserRow {
  id: string;
  object?: string;
  state: string;
  user: {
    id: string;
    name: string;
    email: string;
    status: string;
  } | null;
}

export function UsersTable({ rows }: { rows: TenantUserRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No members in this tenant"
        description="Users appear once they hold an active membership in the tenant being viewed."
      />
    );
  }

  return (
    <ConsoleTable headers={['Name', 'Email', 'Membership', 'Account']}>
      {rows.map((row) => (
        <tr key={row.id} data-testid="user-row">
          <td className="px-4 py-3">{row.user?.name ?? '—'}</td>
          <td className="px-4 py-3 text-muted-foreground">{row.user?.email ?? '—'}</td>
          <td className="px-4 py-3">
            <StatusBadge status={row.state} />
          </td>
          <td className="px-4 py-3">
            <StatusBadge status={row.user?.status ?? 'pending'} />
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function UsersPanel() {
  const result = readConsole<TenantUserRow[]>(await bffJSON<TenantUserRow[]>('/api/platform/users'));

  return (
    <ConsolePanel
      title="Users"
      description="All members of the active tenant with account and membership state."
    >
      <ConsoleResultBody result={result} resource="users">
        {(rows) => <UsersTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
