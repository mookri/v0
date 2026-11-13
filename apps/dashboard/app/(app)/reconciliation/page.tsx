'use client';

import React, { useCallback, useEffect, useState } from 'react';

interface UncertainIntent {
  id: string;
  booking_id: string;
  amount_minor: number;
  currency: string;
  status: string;
  reconcile_attempts: number;
  last_reconcile_error?: string | null;
}

interface PeriodLock {
  accounting_period: string;
  status: string;
  lock_reason?: string | null;
}

export default function ReconciliationPage() {
  const [intents, setIntents] = useState<UncertainIntent[]>([]);
  const [periods, setPeriods] = useState<PeriodLock[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [banner, setBanner] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const loadData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch('/api/ops/reconciliation', { cache: 'no-store' });
      const body = (await res.json().catch(() => ({}))) as {
        data?: { uncertain_intents: UncertainIntent[]; period_locks: PeriodLock[] };
        message?: string;
      };
      if (!res.ok) {
        setError(body.message || `Reconciliation unavailable (${res.status})`);
        setLoading(false);
        return;
      }
      setIntents(body.data?.uncertain_intents ?? []);
      setPeriods(body.data?.period_locks ?? []);
    } catch {
      setError('Failed to query reconciliation queue.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadData();
  }, [loadData]);

  const retryIntent = async (id: string) => {
    setBanner(null);
    setError(null);
    const res = await fetch(`/api/ops/reconciliation/${encodeURIComponent(id)}/retry`, {
      method: 'POST',
    });
    const body = (await res.json().catch(() => ({}))) as {
      data?: { outcome: string };
      message?: string;
    };
    if (!res.ok) {
      setError(body.message || `Retry failed (${res.status})`);
      return;
    }
    setBanner(`Reconciled intent ${id}: outcome=${body.data?.outcome ?? 'ok'}`);
    await loadData();
  };

  return (
    <div className="space-y-6 text-[#2A2421]">
      <header className="border-b border-[#EFE3D3] pb-4">
        <p className="text-xs font-semibold uppercase tracking-widest text-[#B74717]">Gateway & Subledger</p>
        <h1 className="text-2xl font-bold text-[#2C2033]">Reconciliation & Period Locks</h1>
      </header>

      {error ? (
        <div role="alert" className="rounded-xl border border-[#B74717] bg-[#EFE3D3] px-4 py-3 text-sm text-[#B74717]">
          {error}
        </div>
      ) : null}
      {banner ? (
        <div role="status" className="rounded-xl border border-[#2C2033] bg-white px-4 py-3 text-sm text-[#2C2033]">
          {banner}
        </div>
      ) : null}

      <section className="rounded-xl border border-[#EFE3D3] bg-white p-5 space-y-3">
        <h2 className="text-base font-bold text-[#2C2033]">Uncertain Payment Intents (PAYMENT_UNKNOWN)</h2>
        {loading ? (
          <p className="text-sm">Loading intents...</p>
        ) : intents.length === 0 ? (
          <p className="text-sm">No PAYMENT_UNKNOWN intents awaiting manual intervention.</p>
        ) : (
          <table className="w-full text-left text-sm">
            <thead className="border-b border-[#EFE3D3] text-xs uppercase text-[#2C2033]">
              <tr>
                <th className="py-2">Intent</th>
                <th className="py-2">Booking</th>
                <th className="py-2">Amount</th>
                <th className="py-2">Attempts</th>
                <th className="py-2">Last Error</th>
                <th className="py-2">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#EFE3D3]">
              {intents.map((i) => (
                <tr key={i.id}>
                  <td className="py-2.5 font-mono text-xs">{i.id}</td>
                  <td className="py-2.5 font-mono text-xs">{i.booking_id}</td>
                  <td className="py-2.5 font-semibold">{i.currency} {(i.amount_minor / 100).toFixed(2)}</td>
                  <td className="py-2.5">{i.reconcile_attempts}</td>
                  <td className="py-2.5 text-xs text-[#B74717]">{i.last_reconcile_error || '—'}</td>
                  <td className="py-2.5">
                    <button
                      type="button"
                      onClick={() => void retryIntent(i.id)}
                      className="rounded-lg bg-[#2C2033] px-3 py-1.5 text-xs font-semibold text-[#FFF7ED]"
                    >
                      Re-query Gateway
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </section>

      <section className="rounded-xl border border-[#EFE3D3] bg-white p-5 space-y-3">
        <h2 className="text-base font-bold text-[#2C2033]">Accounting Period Locks</h2>
        {periods.length === 0 ? (
          <p className="text-sm">No closed periods recorded yet (all open).</p>
        ) : (
          <ul className="divide-y divide-[#EFE3D3] text-sm">
            {periods.map((p) => (
              <li key={p.accounting_period} className="py-2 flex items-center justify-between">
                <span className="font-mono font-semibold text-[#2C2033]">{p.accounting_period}</span>
                <span className="rounded-full bg-[#EFE3D3] px-3 py-0.5 text-xs font-semibold text-[#2C2033]">
                  {p.status}
                </span>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}

