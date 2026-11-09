import {
  ConsoleEmptyState,
  ConsolePanel,
  ConsoleResultBody,
  ConsoleTable,
} from '@/components/console/ConsolePanel';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/admin/customers/360` — Devotee 360 & Spiritual Insight Panel (E7).
 * Backed by capability:customers:support:view.
 * Conforms strictly to Sacred Ember palette: Warm Ivory (#FFF7ED), Aubergine Ink (#2C2033),
 * Navdhya Flame (#F16A24), Soft Sand (#EFE3D3), Deep Text (#2A2421). Zero green.
 */

export interface Customer360Summary {
  id: string;
  user_id: number;
  name: string;
  phone: string;
  email: string;
  gotra: string | null;
  nakshatra: string | null;
  rashi: string | null;
  tradition: 'AMANTA' | 'PURNIMANTA';
  reminders_count: number;
  next_reminder_title: string | null;
  next_reminder_at: string | null;
  consent_state: string;
  cadence: string;
}

export function Customer360Table({ rows }: { rows: Customer360Summary[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No devotee profiles found"
        description="Devotee spiritual profiles, reminders, and engagement summaries will appear here."
      />
    );
  }

  return (
    <ConsoleTable headers={['Devotee', 'Spiritual Lineage', 'Tradition', 'Upcoming Reminder', 'Consent & Status']}>
      {rows.map((devotee) => (
        <tr key={devotee.id} data-testid="customer-360-row" className="hover:bg-[#FFF7ED]/50 transition-colors">
          <td className="px-4 py-3 font-medium text-[#2C2033]">
            <div>{devotee.name}</div>
            <div className="text-xs text-[#2A2421]/60 font-mono">{devotee.phone || devotee.email}</div>
          </td>
          <td className="px-4 py-3 text-sm text-[#2A2421]">
            {devotee.gotra || devotee.nakshatra ? (
              <div className="flex flex-col gap-0.5">
                {devotee.gotra && <span className="font-semibold text-[#F16A24]">Gotra: {devotee.gotra}</span>}
                {devotee.nakshatra && (
                  <span className="text-xs text-[#2A2421]/75">
                    {devotee.nakshatra} {devotee.rashi ? `· ${devotee.rashi}` : ''}
                  </span>
                )}
              </div>
            ) : (
              <span className="text-xs text-[#2A2421]/40 italic">Not entered</span>
            )}
          </td>
          <td className="px-4 py-3 text-xs">
            <span className="inline-block px-2 py-0.5 rounded border border-[#EFE3D3] bg-[#FFF7ED] text-[#2C2033] font-mono">
              {devotee.tradition}
            </span>
          </td>
          <td className="px-4 py-3 text-sm text-[#2A2421]">
            {devotee.next_reminder_title ? (
              <div>
                <span className="font-medium text-[#B74717]">{devotee.next_reminder_title}</span>
                <div className="text-xs text-[#2A2421]/60 font-mono">
                  {devotee.next_reminder_at ? new Date(devotee.next_reminder_at).toLocaleDateString() : '—'}
                </div>
              </div>
            ) : (
              <span className="text-xs text-[#2A2421]/40">No reminders</span>
            )}
          </td>
          <td className="px-4 py-3 text-xs">
            <span
              className={`inline-block px-2.5 py-1 rounded-full font-medium ${
                devotee.consent_state === 'GRANTED'
                  ? 'bg-[#F16A24]/10 text-[#B74717] border border-[#F16A24]/30'
                  : 'bg-[#EFE3D3]/50 text-[#2A2421]/60 border border-[#EFE3D3]'
              }`}
            >
              {devotee.consent_state}
            </span>
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function Customer360Panel() {
  const result = readConsole<Customer360Summary[]>(
    await bffJSON<Customer360Summary[]>('/api/admin/customers/360')
  );

  return (
    <ConsolePanel
      title="Customer 360"
      description="Devotee spiritual profiles, Panchang reminders, and privacy consent posture."
    >
      <ConsoleResultBody result={result} resource="devotee records">
        {(rows) => <Customer360Table rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}

