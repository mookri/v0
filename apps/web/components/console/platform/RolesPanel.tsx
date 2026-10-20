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
 * `/platform/roles` — W1a. Backed by Laravel `GET /api/v1/platform/roles`,
 * which is tenant-scoped: the role/SoD seam takes `tenant_id` in the query and
 * `platform:role:view` gates every read (see `RoleController::scopeToTenant`).
 * The BFF owns that plumbing; this panel only renders what it returns.
 */

export interface RoleRow {
  id: string;
  object?: string;
  code: string;
  name: string;
  state: string;
}

export function RolesTable({ rows }: { rows: RoleRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No roles in this tenant"
        description="Roles are tenant-scoped. Create one to start granting access."
      />
    );
  }

  return (
    <ConsoleTable headers={['Code', 'Name', 'State']}>
      {rows.map((role) => (
        <tr key={role.id} data-testid="role-row">
          <td className="px-4 py-3 font-mono text-xs">{role.code}</td>
          <td className="px-4 py-3">{role.name}</td>
          <td className="px-4 py-3">
            <StatusBadge status={role.state} />
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function RolesPanel() {
  const result = readConsole<RoleRow[]>(await bffJSON<RoleRow[]>('/api/platform/roles'));

  return (
    <ConsolePanel
      title="Roles & Permissions"
      description="Role configuration, access control, and permissions for the active tenant."
    >
      <ConsoleResultBody result={result} resource="roles">
        {(roles) => <RolesTable rows={roles} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
