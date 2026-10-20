import {
  ConsoleEmptyState,
  ConsolePanel,
  ConsoleResultBody,
  ConsoleTable,
} from '@/components/console/ConsolePanel';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/platform/settings` — W2. Backed by Laravel GET /api/v1/platform/settings
 * (`platform:config:manage`): the configured tenant's code, name, timezone,
 * locale, plan and status. Feature-flag overrides are not yet wired, so the
 * panel reports what the kernel actually knows.
 */

export interface TenantSettings {
  code: string;
  name: string;
  state: string;
  status: string;
  plan_code: string;
  base_currency: string;
  timezone: string;
  locale: string;
}

export function SettingsTable({ settings }: { settings: TenantSettings }) {
  const rows: { label: string; value: string }[] = [
    { label: 'Code', value: settings.code },
    { label: 'Name', value: settings.name },
    { label: 'State', value: settings.state },
    { label: 'Status', value: settings.status },
    { label: 'Plan', value: settings.plan_code },
    { label: 'Base currency', value: settings.base_currency },
    { label: 'Timezone', value: settings.timezone },
    { label: 'Locale', value: settings.locale },
  ];

  return (
    <ConsoleTable headers={['Setting', 'Value']}>
      {rows.map((row) => (
        <tr key={row.label} data-testid="settings-row">
          <td className="px-4 py-3 font-medium">{row.label}</td>
          <td className="px-4 py-3 text-muted-foreground">{row.value || '—'}</td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function SettingsPanel() {
  const result = readConsole<TenantSettings>(await bffJSON<TenantSettings>('/api/platform/settings'));

  return (
    <ConsolePanel
      title="Settings"
      description="Global configuration for the active tenant."
    >
      <ConsoleResultBody result={result} resource="settings">
        {(settings) => <SettingsTable settings={settings} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
