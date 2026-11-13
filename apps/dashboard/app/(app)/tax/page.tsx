'use client';

import React, { useState } from 'react';

interface MaskedTaxRow {
  id: string;
  subject_type: string;
  subject_id: string;
  regime?: string;
  masked_ref?: string;
  status: string;
}

export default function TaxRegisterPage() {
  const [purpose, setPurpose] = useState('');
  const [rows, setRows] = useState<MaskedTaxRow[] | null>(null);
  const [recordedPurpose, setRecordedPurpose] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleLoad = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    const trimmed = purpose.trim();
    if (trimmed.length < 5) {
      setError('Enter an explicit filing or audit purpose (at least 5 characters) before querying tax records.');
      return;
    }

    setLoading(true);
    try {
      const res = await fetch(`/api/ops/tax?purpose=${encodeURIComponent(trimmed)}`, {
        cache: 'no-store',
      });
      const body = (await res.json().catch(() => ({}))) as {
        data?: { registrations: MaskedTaxRow[]; audit_purpose: string };
        message?: string;
      };
      if (!res.ok) {
        setError(body.message || `Tax query denied (${res.status})`);
        setLoading(false);
        return;
      }
      setRows(body.data?.registrations ?? []);
      setRecordedPurpose(body.data?.audit_purpose ?? trimmed);
    } catch {
      setError('Failed to reach tax register endpoint.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6 text-[#2A2421]">
      <header className="border-b border-[#EFE3D3] pb-4">
        <p className="text-xs font-semibold uppercase tracking-widest text-[#B74717]">Compliance & Statutory Audit</p>
        <h1 className="text-2xl font-bold text-[#2C2033]">Purpose-Audited Tax Register</h1>
        <p className="mt-1 text-sm">
          Every read requires a recorded compliance purpose and returns masked tax identifiers only.
        </p>
      </header>

      <form onSubmit={handleLoad} className="rounded-xl border border-[#EFE3D3] bg-white p-5 flex flex-wrap gap-3 items-end">
        <div className="flex-1 min-w-[260px]">
          <label htmlFor="tax-purpose" className="block text-xs font-semibold uppercase text-[#2C2033]">
            Access Purpose (Audit-Logged)
          </label>
          <input
            id="tax-purpose"
            type="text"
            value={purpose}
            onChange={(e) => setPurpose(e.target.value)}
            placeholder="e.g. Monthly TDS 194C / GSTR-1 reconciliation"
            className="mt-1 w-full rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] px-3 py-2 text-sm text-[#2A2421]"
          />
        </div>
        <button
          type="submit"
          disabled={loading}
          className="rounded-lg bg-[#2C2033] px-5 py-2.5 text-sm font-semibold text-[#FFF7ED] hover:bg-[#3d2e47]"
        >
          {loading ? 'Auditing Access...' : 'Load Masked Tax Register'}
        </button>
      </form>

      {error ? (
        <div role="alert" className="rounded-xl border border-[#B74717] bg-[#EFE3D3] px-4 py-3 text-sm text-[#B74717]">
          {error}
        </div>
      ) : null}

      {rows !== null ? (
        <section className="rounded-xl border border-[#EFE3D3] bg-white p-5 space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-base font-bold text-[#2C2033]">Masked Registrations ({rows.length})</h2>
            <span className="rounded-full bg-[#EFE3D3] px-3 py-1 text-xs font-medium text-[#2C2033]">
              Audit Logged: {recordedPurpose}
            </span>
          </div>
          {rows.length === 0 ? (
            <p className="text-sm">No tax registrations found for this tenant.</p>
          ) : (
            <table className="w-full text-left text-sm">
              <thead className="border-b border-[#EFE3D3] text-xs uppercase text-[#2C2033]">
                <tr>
                  <th className="py-2">Subject</th>
                  <th className="py-2">Regime</th>
                  <th className="py-2">Masked Reference</th>
                  <th className="py-2">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#EFE3D3]">
                {rows.map((r) => (
                  <tr key={r.id}>
                    <td className="py-2.5 font-mono text-xs">{r.subject_type}:{r.subject_id}</td>
                    <td className="py-2.5">{r.regime || '—'}</td>
                    <td className="py-2.5 font-mono text-xs">{r.masked_ref || 'REDACTED'}</td>
                    <td className="py-2.5">{r.status}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>
      ) : null}
    </div>
  );
}

