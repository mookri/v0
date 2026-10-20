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
 * `/admin/bookings` — W2. Backed by Laravel GET /api/v1/platform/ops/bookings
 * (`platform:ops:view`): the platform-wide booking review list.
 */

export interface BookingRow {
  id: string;
  object?: string;
  user_id: number | string;
  service_sku: string | null;
  status: string;
  scheduled_at: string | null;
  total_minor: number | null;
  currency: string | null;
  created_at: string | null;
}

export function BookingsTable({ rows }: { rows: BookingRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No bookings in this tenant"
        description="Platform-wide booking review appears here once reservations are made."
      />
    );
  }

  return (
    <ConsoleTable headers={['Service', 'Status', 'Scheduled', 'Total', 'Created']}>
      {rows.map((booking) => (
        <tr key={booking.id} data-testid="booking-row">
          <td className="px-4 py-3 font-mono text-xs">{booking.service_sku ?? '—'}</td>
          <td className="px-4 py-3">
            <StatusBadge status={booking.status.toLowerCase()} />
          </td>
          <td className="px-4 py-3 text-muted-foreground">
            {booking.scheduled_at ? new Date(booking.scheduled_at).toLocaleString() : '—'}
          </td>
          <td className="px-4 py-3">
            {booking.total_minor !== null && booking.currency
              ? `${booking.currency} ${(booking.total_minor / 100).toFixed(2)}`
              : '—'}
          </td>
          <td className="px-4 py-3 text-muted-foreground">
            {booking.created_at ? new Date(booking.created_at).toLocaleString() : '—'}
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function BookingsPanel() {
  const result = readConsole<BookingRow[]>(await bffJSON<BookingRow[]>('/api/admin/bookings'));

  return (
    <ConsolePanel
      title="Bookings"
      description="Platform-wide booking review and issue handling."
    >
      <ConsoleResultBody result={result} resource="bookings">
        {(rows) => <BookingsTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}
