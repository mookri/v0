import {
  ConsolePanel,
  ConsoleResultBody,
} from '@/components/console/ConsolePanel';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/platform/security` — W2. Backed by Laravel GET /api/v1/platform/security
 * (`platform:ops:view`): MFA adoption (platform-wide), authz denial counts and
 * audit volume for the configured tenant.
 */

export interface SecurityOverview {
  mfa: { enrolled: number; total_users: number };
  authz: { denied_today: number; denied_total: number };
  audit: { total: number };
}

export function SecurityCard({ overview }: { overview: SecurityOverview }) {
  const mfaPercent =
    overview.mfa.total_users > 0
      ? Math.round((overview.mfa.enrolled / overview.mfa.total_users) * 100)
      : 0;

  const rows: { label: string; value: string }[] = [
    { label: 'MFA enrolled (platform-wide)', value: String(overview.mfa.enrolled) },
    { label: 'Total users (tenant)', value: String(overview.mfa.total_users) },
    { label: 'MFA adoption', value: `${mfaPercent}%` },
    { label: 'Authz denials today', value: String(overview.authz.denied_today) },
    { label: 'Authz denials (total)', value: String(overview.authz.denied_total) },
    { label: 'Audit entries (total)', value: String(overview.audit.total) },
  ];

  return (
    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      {rows.map((row) => (
        <div
          key={row.label}
          data-testid="security-stat"
          className="rounded-2xl border border-border bg-card p-4 text-center"
        >
          <p className="text-2xl font-bold">{row.value}</p>
          <p className="text-xs text-muted-foreground">{row.label}</p>
        </div>
      ))}
    </div>
  );
}

export async function SecurityPanel() {
  const result = readConsole<SecurityOverview>(await bffJSON<SecurityOverview>('/api/platform/security'));

  return (
    <ConsolePanel
      title="Security"
      description="Auth logs, 2FA status, threat detection, and compliance."
    >
      <ConsoleResultBody result={result} resource="security overview">
        {(overview) => <SecurityCard overview={overview} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
