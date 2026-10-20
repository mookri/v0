'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import type { VendorInventoryRow } from '@/application/vendor';
import { VendorTabs } from './VendorTabs';
import { describeError, formatDate, type VendorViewError } from './format';

// Inventory (client): stock rows + signed adjust (POST inventory:adjust
// via the BFF). Negative results answer 409 CONFLICT — show verbatim.

export function VendorInventoryView({ vendorId, rows }: { vendorId: string; rows: VendorInventoryRow[] }) {
  const router = useRouter();
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function adjust(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setStatus(null);
    const form = event.currentTarget;
    const data = new FormData(form);
    const res = await fetch(`/api/vendors/${vendorId}/inventory/adjust`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        sku: String(data.get('sku') ?? ''),
        delta: Number.parseInt(String(data.get('delta') ?? '0'), 10),
      }),
    });
    const body = (await res.json()) as { data?: VendorInventoryRow } & VendorViewError;

    if (!res.ok || !body.data) {
      setStatus(`Failed: ${describeError(body, 'Adjustment failed.')}`);
      setBusy(false);
      return;
    }
    setStatus(`Adjusted ${body.data.sku} → ${body.data.on_hand} in stock.`);
    form.reset();
    setBusy(false);
    router.refresh();
  }

  return (
    <main className="p-6 max-w-4xl mx-auto">
      <VendorTabs vendorId={vendorId} active="inventory" />

      <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm mb-6">
        <h1 className="text-2xl font-bold text-gray-900 mb-4">Adjust Stock</h1>
        <form onSubmit={adjust} className="flex flex-wrap items-end gap-3">
          <label className="flex-1 min-w-40">
            <span className="block text-sm font-medium text-gray-700 mb-1">SKU *</span>
            <input
              name="sku"
              required
              maxLength={64}
              list="vendor-inventory-skus"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="SKU-1"
            />
            <datalist id="vendor-inventory-skus">
              {rows.map((row) => (
                <option key={row.id} value={row.sku} />
              ))}
            </datalist>
          </label>
          <label className="w-40">
            <span className="block text-sm font-medium text-gray-700 mb-1">Delta *</span>
            <input
              name="delta"
              required
              type="number"
              step={1}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="5 or -3"
            />
          </label>
          <button
            type="submit"
            disabled={busy}
            className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
          >
            {busy ? 'Applying…' : 'Apply Adjustment'}
          </button>
        </form>
        {status && (
          <p
            role="status"
            className={`mt-4 text-sm rounded-lg px-3 py-2 border ${
              status.startsWith('Failed') ? 'text-red-600 bg-red-50 border-red-200' : 'text-green-700 bg-green-50 border-green-200'
            }`}
          >
            {status}
          </p>
        )}
      </div>

      {rows.length === 0 ? (
        <div className="text-center py-12 text-gray-500">No stock recorded yet — apply an adjustment above.</div>
      ) : (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead>
              <tr className="text-left text-gray-500 border-b border-gray-200">
                <th className="px-4 py-3 font-medium">SKU</th>
                <th className="px-4 py-3 font-medium">On hand</th>
                <th className="px-4 py-3 font-medium">Updated</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id} className="border-b border-gray-100 last:border-0">
                  <td className="px-4 py-3 font-mono">{row.sku}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`font-semibold ${row.on_hand <= 0 ? 'text-red-600' : 'text-gray-900'}`}
                    >
                      {row.on_hand}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-gray-500">{formatDate(row.updated_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </main>
  );
}
