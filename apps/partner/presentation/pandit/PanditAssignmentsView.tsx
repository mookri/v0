'use client';

import { useRef, useState } from 'react';
import { useRouter } from 'next/navigation';
import { describeAssignmentError } from '@/presentation/pandit/conflict-messages';

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
  PENDING: 'bg-[#FFF7ED] text-[#B74717] border border-[#F16A24]',
  CONFIRMED: 'bg-[#2C2033] text-[#FFF7ED]',
  IN_PROGRESS: 'bg-[#F16A24] text-[#FFF7ED]',
  DECLINED: 'bg-[#B74717]/15 text-[#B74717] border border-[#B74717]/30',
  COMPLETED: 'bg-[#EFE3D3] text-[#2C2033] border border-[#2C2033]/20',
  CANCELLED: 'bg-[#EFE3D3]/60 text-[#2A2421] border border-[#EFE3D3]',
};

function makeActionKey(id: string, action: string): string {
  const random = typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
    ? crypto.randomUUID()
    : `${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
  return `partner_${id}_${action}_${random}`;
}

export function PanditAssignmentsView({ assignments }: { assignments: PanditAssignment[] }) {
  const router = useRouter();
  const [busy, setBusy] = useState<string | null>(null);
  const inFlightRef = useRef<Set<string>>(new Set());
  const actionKeysRef = useRef<Map<string, string>>(new Map());
  const [checklistById, setChecklistById] = useState<Record<string, boolean>>({});
  const [actionStatus, setActionStatus] = useState<Record<string, string>>({});

  async function act(id: string, action: 'confirm' | 'start' | 'decline' | 'complete') {
    const lockKey = `${id}:${action}`;
    if (inFlightRef.current.has(lockKey)) {
      return;
    }

    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
      setActionStatus((s) => ({
        ...s,
        [id]: describeAssignmentError('DEPENDENCY_UNAVAILABLE'),
      }));
      return;
    }

    inFlightRef.current.add(lockKey);
    const idempotencyKey = actionKeysRef.current.get(lockKey) ?? makeActionKey(id, action);
    actionKeysRef.current.set(lockKey, idempotencyKey);

    setBusy(id);
    setActionStatus((s) => ({ ...s, [id]: '' }));

    try {
      const payload =
        action === 'decline'
          ? { reason: 'Declined by pandit' }
          : action === 'start'
            ? { checklist: { samagri_ready: Boolean(checklistById[id] ?? true), arrived_on_site: true } }
            : {};

      const res = await fetch(`/api/pandit/assignments/${encodeURIComponent(id)}/${action}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Idempotency-Key': idempotencyKey,
        },
        body: JSON.stringify(payload),
      });
      const data = (await res.json()) as { data?: { status?: string }; error?: { code?: string; message?: string } };
      if (!res.ok || !data.data) {
        setActionStatus((s) => ({
          ...s,
          [id]: describeAssignmentError(data.error?.code, data.error?.message),
        }));
      } else {
        actionKeysRef.current.delete(lockKey);
        setActionStatus((s) => ({ ...s, [id]: `Updated to ${data.data?.status ?? action}.` }));
        router.refresh();
      }
    } finally {
      inFlightRef.current.delete(lockKey);
      setBusy(null);
    }
  }

  if (assignments.length === 0) {
    return (
      <div className="rounded-lg border border-dashed border-[#EFE3D3] bg-[#FFF7ED] p-8 text-center text-[#2A2421]">
        No assignments yet.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-lg border border-[#EFE3D3] bg-[#FFF7ED]">
      <table className="w-full text-sm text-[#2A2421]">
        <thead className="bg-[#EFE3D3]/60 text-left text-xs uppercase tracking-wide text-[#2C2033]">
          <tr>
            <th className="px-4 py-3 font-semibold">ID</th>
            <th className="px-4 py-3 font-semibold">Service</th>
            <th className="px-4 py-3 font-semibold">Scheduled</th>
            <th className="px-4 py-3 font-semibold">Status</th>
            <th className="px-4 py-3 font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-[#EFE3D3]">
          {assignments.map((a) => {
            const id = String(a.id);
            const status = String(a.status ?? 'PENDING');
            const tone = STATUS_TONE[status] ?? 'bg-[#EFE3D3] text-[#2A2421]';
            const canConfirm = status === 'PENDING';
            const canDecline = status === 'PENDING';
            const canStart = status === 'CONFIRMED';
            const canComplete = status === 'IN_PROGRESS' || status === 'CONFIRMED';
            return (
              <tr key={id} data-testid="assignment-row">
                <td className="px-4 py-3 font-mono text-xs">{id}</td>
                <td className="px-4 py-3">{a.service_sku ?? '—'}</td>
                <td className="px-4 py-3 text-[#2A2421]/80">
                  {a.scheduled_at ? new Date(a.scheduled_at as string).toLocaleString() : '—'}
                </td>
                <td className="px-4 py-3">
                  <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${tone}`}>
                    {status}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <div className="flex flex-wrap items-center gap-2">
                    {canConfirm ? (
                      <button
                        type="button"
                        onClick={() => act(id, 'confirm')}
                        disabled={busy === id}
                        data-testid="confirm-button"
                        className="min-h-[48px] rounded-md bg-[#2C2033] px-3 py-2 text-xs font-medium text-[#FFF7ED] hover:bg-[#2C2033]/90 disabled:opacity-50"
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
                        className="min-h-[48px] rounded-md border border-[#B74717]/40 bg-[#FFF7ED] px-3 py-2 text-xs font-medium text-[#B74717] hover:bg-[#EFE3D3] disabled:opacity-50"
                      >
                        {busy === id ? '…' : 'Decline'}
                      </button>
                    ) : null}
                    {canStart ? (
                      <>
                        <label className="inline-flex items-center gap-1.5 text-xs text-[#2A2421]">
                          <input
                            type="checkbox"
                            checked={Boolean(checklistById[id] ?? true)}
                            onChange={(e) => setChecklistById((prev) => ({ ...prev, [id]: e.target.checked }))}
                            className="h-4 w-4 accent-[#2C2033]"
                          />
                          Samagri ready
                        </label>
                        <button
                          type="button"
                          onClick={() => act(id, 'start')}
                          disabled={busy === id}
                          data-testid="start-button"
                          className="min-h-[48px] rounded-md bg-[#F16A24] px-3 py-2 text-xs font-semibold text-[#FFF7ED] hover:bg-[#B74717] disabled:opacity-50"
                        >
                          {busy === id ? '…' : 'Start Service'}
                        </button>
                      </>
                    ) : null}
                    {canComplete ? (
                      <button
                        type="button"
                        onClick={() => act(id, 'complete')}
                        disabled={busy === id}
                        data-testid="complete-button"
                        className="min-h-[48px] rounded-md border border-[#2C2033]/30 bg-[#EFE3D3] px-3 py-2 text-xs font-medium text-[#2C2033] hover:bg-[#2C2033] hover:text-[#FFF7ED] disabled:opacity-50"
                      >
                        {busy === id ? '…' : 'Complete'}
                      </button>
                    ) : null}
                    {actionStatus[id] ? <span className="text-xs text-[#2A2421]">{actionStatus[id]}</span> : null}
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
