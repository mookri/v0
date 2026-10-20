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
 * `/platform/api` — W1a. This used to proxy `/api/v1/platform/api`, a path
 * that does not exist: the panel rendered an empty shell forever. It is now
 * backed by the two surfaces that actually exist —
 * `GET /platform/api-keys` (machine credentials, gated by `platform:apikey:view`
 * inside `ApiKeyController::scopeToTenant`) and `GET /platform/oauth/clients`
 * (federation clients, `platform:user:view`).
 *
 * A key's secret is never returned by the backend and is never rendered here;
 * only `prefix` is shown.
 */

export interface ApiKeyRow {
  id: string;
  object?: string;
  name: string;
  prefix: string;
  scopes?: string[];
  rate_tier?: string | null;
  expires_at?: string | null;
  last_used_at?: string | null;
  revoked_at?: string | null;
}

export interface OAuthClientRow {
  id: string;
  client_id: string;
  client_type?: string;
  name: string;
  revoked?: boolean;
  allowed_grants?: string[];
  status?: string;
  require_pkce?: boolean;
}

export interface ApiManagement {
  api_keys: ApiKeyRow[];
  oauth_clients: OAuthClientRow[];
}

export function ApiKeysTable({ rows }: { rows: ApiKeyRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No machine API keys"
        description="Issue a key to let a machine act with a bounded scope and rate tier."
      />
    );
  }

  return (
    <ConsoleTable headers={['Name', 'Prefix', 'Rate tier', 'Last used', 'State']}>
      {rows.map((key) => (
        <tr key={key.id} data-testid="api-key-row">
          <td className="px-4 py-3">{key.name}</td>
          <td className="px-4 py-3 font-mono text-xs">{key.prefix}</td>
          <td className="px-4 py-3 text-muted-foreground">{key.rate_tier ?? '—'}</td>
          <td className="px-4 py-3 text-muted-foreground">{key.last_used_at ?? 'Never'}</td>
          <td className="px-4 py-3">
            <StatusBadge status={key.revoked_at ? 'cancelled' : 'active'} />
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export function OAuthClientsTable({ rows }: { rows: OAuthClientRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No OAuth clients"
        description="Clients registered for federation sign-in appear here with their grants and PKCE requirement."
      />
    );
  }

  return (
    <ConsoleTable headers={['Name', 'Client ID', 'Type', 'PKCE', 'State']}>
      {rows.map((client) => (
        <tr key={client.id} data-testid="oauth-client-row">
          <td className="px-4 py-3">{client.name || '—'}</td>
          <td className="px-4 py-3 font-mono text-xs">{client.client_id}</td>
          <td className="px-4 py-3 text-muted-foreground">{client.client_type ?? '—'}</td>
          <td className="px-4 py-3 text-muted-foreground">{client.require_pkce ? 'Required' : 'Optional'}</td>
          <td className="px-4 py-3">
            <StatusBadge status={client.revoked ? 'cancelled' : (client.status ?? 'active')} />
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function ApiPanel() {
  const result = readConsole<ApiManagement>(await bffJSON<ApiManagement>('/api/platform/api'));

  return (
    <ConsolePanel
      title="API Management"
      description="Machine credentials and federation clients for the active tenant."
    >
      <ConsoleResultBody result={result} resource="API credentials">
        {({ api_keys, oauth_clients }) => (
          <div className="space-y-8">
            <section className="space-y-3">
              <h2 className="text-lg font-semibold">Machine API keys</h2>
              <ApiKeysTable rows={Array.isArray(api_keys) ? api_keys : []} />
            </section>
            <section className="space-y-3">
              <h2 className="text-lg font-semibold">OAuth clients</h2>
              <OAuthClientsTable rows={Array.isArray(oauth_clients) ? oauth_clients : []} />
            </section>
          </div>
        )}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
