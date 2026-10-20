'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

interface PanditAssignment {
  id: string;
  status: string;
  booking_id?: string;
  scheduled_at?: string | null;
  service_sku?: string | null;
  customer_name?: string | null;
  [key: string]: unknown;
}

const STATUS_TONE: Record<string, string> = {
  PENDING: 'bg-blue-100 text-blue-800',
  CONFIRMED: 'bg-green-100 text-green-800',
  DECLINED: 'bg-red-100 text-red-800',
  COMPLETED: 'bg-emerald-100 text-emerald-800',
  CANCELLED: 'bg-gray-100 text-gray-800',
};

export function PanditAssignmentsView({ assignments }: { assignments: PanditAssignment[] }) {
  const router = useRouter();
  const [busy, setBusy] = useState<string | null>(null);
  const [actionStatus, setActionStatus] = useState<Record<string, string>>({});

  async function act(id: string, action: 'confirm' | 'decline' | 'complete') {
    setBusy(id);
    setActionStatus((s) => ({ ...s, [id]: '' }));
    const body = action === 'decline' ? JSON.stringify({ reason: 'Declined by pandit' }) : '{}';
    const res = await fetch(`/api/pandit/assignments/${encodeURIComponent(id)}/${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body,
    });
    const data = (await res.json()) as { data?: { status?: string }; error?: { code?: string; message?: string } };
    if (!res.ok || !data.data) {
      setActionStatus((s) => ({ ...s, [id]: `Failed: ${data.error?.code ?? 'REQUEST_FAILED'}` }));
    } else {
      setActionStatus((s) => ({ ...s, [id]: `Moved to ${data.data?.status ?? action}.` }));
      router.refresh();
    }
    setBusy(null);
  }

  if (assignments.length === 0) {
    return (
      <div className="rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500">
        No assignments yet.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-lg border border-gray-200">
      <table className="w-full text-sm">
        <thead className="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
          <tr>
            <th className="px-4 py-3 font-semibold">ID</th>
            <th className="px-4 py-3 font-semibold">Service</th>
            <th className="px-4 py-3 font-semibold">Scheduled</th>
            <th className="px-4 py-3 font-semibold">Status</th>
            <th className="px-4 py-3 font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200">
          {assignments.map((a) => {
            const id = String(a.id);
            const status = String(a.status ?? 'PENDING');
            const tone = STATUS_TONE[status] ?? 'bg-gray-100 text-gray-800';
            const canConfirm = status === 'PENDING';
            const canDecline = status === 'PENDING';
            const canComplete = status === 'CONFIRMED';
            return (
              <tr key={id} data-testid="assignment-row">
                <td className="px-4 py-3 font-mono text-xs">{id}</td>
                <td className="px-4 py-3">{a.service_sku ?? '—'}</td>
                <td className="px-4 py-3 text-gray-500">
                  {a.scheduled_at ? new Date(a.scheduled_at as string).toLocaleString() : '—'}
                </td>
                <td className="px-4 py-3">
                  <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${tone}`}>
                    {status}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <div className="flex flex-wrap gap-2">
                    {canConfirm ? (
                      <button
                        type="button"
                        onClick={() => act(id, 'confirm')}
                        disabled={busy === id}
                        data-testid="confirm-button"
                        className="rounded-md border border-green-500/40 bg-green-500/10 px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-500/20 disabled:opacity-50"
                      >
                        {busy === id ? '…' : 'Confirm'}
                      </button>
                    ) : null}
                    {canDecline ? (
                      <button
                        type="button"
                        onClick={() => act(id, 'decline')}
                        disabled={busy === id}
                        data-testid="decline-button"
                        className="rounded-md border border-red-500/40 bg-red-500/10 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20 disabled:opacity-50"
                      >
                        {busy === id ? '…' : 'Decline'}
                      </button>
                    ) : null}
                    {canComplete ? (
                      <button
                        type="button"
                        onClick={() => act(id, 'complete')}
                        disabled={busy === id}
                        data-testid="complete-button"
                        className="rounded-md border border-blue-500/40 bg-blue-500/10 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-500/20 disabled:opacity-50"
                      >
                        {busy === id ? '…' : 'Complete'}
                      </button>
                    ) : null}
                    {actionStatus[id] ? <span className="text-xs text-gray-500">{actionStatus[id]}</span> : null}
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
