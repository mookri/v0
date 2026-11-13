'use client';

import React, { useEffect, useState } from 'react';

interface SaaSInvoice {
  id: string;
  document_number: string;
  status: string;
  currency: string;
  total_minor: number;
  issued_at?: string | null;
}

export default function BillingPage() {
  const [plan, setPlan] = useState<{ plan_code?: string; status?: string; base_currency?: string } | null>(null);
  const [invoices, setInvoices] = useState<SaaSInvoice[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    fetch('/api/ops/billing', { cache: 'no-store' })
      .then(async (res) => {
        const body = (await res.json().catch(() => ({}))) as {
          data?: { plan_code?: string; status?: string; base_currency?: string; invoices?: SaaSInvoice[] };
          message?: string;
        };
        if (!active) return;
        if (!res.ok) {
          setError(body.message || `SaaS billing unavailable (${res.status})`);
          setLoading(false);
          return;
        }
        setPlan(body.data ?? null);
        setInvoices(body.data?.invoices ?? []);
        setLoading(false);
      })
      .catch(() => {
        if (!active) return;
        setError('Unable to load SaaS billing surface.');
        setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);

  return (
    <div className="space-y-6 text-[#2A2421]">
      <header className="border-b border-[#EFE3D3] pb-4">
        <p className="text-xs font-semibold uppercase tracking-widest text-[#B74717]">Isolated SaaS Plan Boundary (G-BILL-1)</p>
        <h1 className="text-2xl font-bold text-[#2C2033]">Tenant Subscription & SaaS Billing</h1>
        <p className="mt-1 text-sm">
          Strictly isolated from devotee booking settlements and partner payout clearing ledgers.
        </p>
      </header>

      {error ? (
        <div role="alert" className="rounded-xl border border-[#B74717] bg-[#EFE3D3] px-4 py-3 text-sm text-[#B74717]">
          {error}
        </div>
      ) : null}

      {loading ? (
        <p className="text-sm">Loading tenant subscription details...</p>
      ) : (
        <div className="space-y-6">
          <section className="rounded-xl border border-[#EFE3D3] bg-white p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p className="text-xs uppercase font-semibold text-[#B74717]">Plan Code</p>
              <p className="mt-1 text-lg font-bold text-[#2C2033]">{plan?.plan_code || 'standard'}</p>
            </div>
            <div>
              <p className="text-xs uppercase font-semibold text-[#B74717]">Subscription State</p>
              <p className="mt-1 text-lg font-bold text-[#2C2033]">{plan?.status || 'active'}</p>
            </div>
            <div>
              <p className="text-xs uppercase font-semibold text-[#B74717]">Billing Currency</p>
              <p className="mt-1 text-lg font-bold text-[#2C2033]">{plan?.base_currency || 'INR'}</p>
            </div>
          </section>

          <section className="rounded-xl border border-[#EFE3D3] bg-white p-5 space-y-3">
            <h2 className="text-base font-bold text-[#2C2033]">SaaS Platform Invoices</h2>
            {invoices.length === 0 ? (
              <p className="text-sm">No SaaS subscription invoices issued yet.</p>
            ) : (
              <table className="w-full text-left text-sm">
                <thead className="border-b border-[#EFE3D3] text-xs uppercase text-[#2C2033]">
                  <tr>
                    <th className="py-2">Invoice #</th>
                    <th className="py-2">Total</th>
                    <th className="py-2">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#EFE3D3]">
                  {invoices.map((inv) => (
                    <tr key={inv.id}>
                      <td className="py-2 font-mono text-xs">{inv.document_number}</td>
                      <td className="py-2 font-semibold">{inv.currency} {(inv.total_minor / 100).toFixed(2)}</td>
                      <td className="py-2">{inv.status}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </section>
        </div>
      )}
    </div>
  );
}

