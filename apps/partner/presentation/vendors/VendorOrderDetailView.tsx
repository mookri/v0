'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import type { VendorOrder } from '@/application/vendor';
import { VendorTabs } from './VendorTabs';
import { describeError, formatDate, formatMinor, type VendorViewError } from './format';

// Order detail (client): line snapshots + lifecycle actions. Advance runs
// NEW→PROCESSING→DISPATCHED→DELIVERED; cancel only from NEW/PROCESSING —
// terminal conflicts (409) surface verbatim.

const NEXT_LABEL: Record<string, string> = {
  NEW: 'Start Processing',
  PROCESSING: 'Mark Dispatched',
  DISPATCHED: 'Mark Delivered',
};

export function VendorOrderDetailView({ vendorId, order }: { vendorId: string; order: VendorOrder }) {
  const router = useRouter();
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function act(action: 'advance' | 'cancel') {
    setBusy(true);
    setStatus(null);
    const res = await fetch(`/api/vendors/${vendorId}/orders/${order.id}/${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: '{}',
    });
    const body = (await res.json()) as { data?: VendorOrder } & VendorViewError;

    if (!res.ok || !body.data) {
      setStatus(`Failed: ${describeError(body, 'Action failed.')}`);
      setBusy(false);
      return;
    }
    setStatus(action === 'advance' ? `Moved to ${body.data.status.toLowerCase()}.` : 'Order cancelled.');
    setBusy(false);
    router.refresh();
  }

  const cancellable = order.status === 'NEW' || order.status === 'PROCESSING';
  const advanceLabel = NEXT_LABEL[order.status];

  const statusTone =
    order.status === 'DELIVERED'
      ? 'bg-green-100 text-green-800'
      : order.status === 'CANCELLED'
        ? 'bg-red-100 text-red-800'
        : order.status === 'NEW'
          ? 'bg-blue-100 text-blue-800'
          : 'bg-yellow-100 text-yellow-800';

  return (
    <main className="p-6 max-w-4xl mx-auto">
      <VendorTabs vendorId={vendorId} active="orders" />

      <Link
        href={`/vendors/${vendorId}/orders`}
        className="inline-flex items-center text-indigo-600 hover:text-indigo-800 mb-6"
      >
        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
        </svg>
        Back to Orders
      </Link>

      <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm mb-6">
        <div className="flex flex-wrap items-start justify-between gap-4 mb-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{order.reference}</h1>
            <p className="text-sm text-gray-500 mt-1">
              {order.customer_name ?? 'No customer name'} · {formatDate(order.created_at)}
            </p>
          </div>
          <span className={`inline-flex px-3 py-1 rounded-full text-sm font-medium ${statusTone}`}>
            {order.status.toLowerCase()}
          </span>
        </div>

        <dl className="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm mb-6">
          <div>
            <dt className="text-gray-500">Total</dt>
            <dd className="text-gray-900 font-semibold">{formatMinor(order.total_minor)}</dd>
          </div>
          <div>
            <dt className="text-gray-500">Currency</dt>
            <dd className="text-gray-900 font-semibold">{order.currency}</dd>
          </div>
          <div>
            <dt className="text-gray-500">Updated</dt>
            <dd className="text-gray-900 font-semibold">{formatDate(order.updated_at)}</dd>
          </div>
        </dl>

        <div className="flex flex-wrap gap-3">
          {advanceLabel && (
            <button
              type="button"
              disabled={busy}
              onClick={() => void act('advance')}
              className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
            >
              {advanceLabel}
            </button>
          )}
          {cancellable && (
            <button
              type="button"
              disabled={busy}
              onClick={() => void act('cancel')}
              className="px-4 py-2 bg-white text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition-colors disabled:opacity-60"
            >
              Cancel Order
            </button>
          )}
          {!advanceLabel && !cancellable && (
            <p className="text-sm text-gray-500">This order is closed — no further transitions are available.</p>
          )}
        </div>

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

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="text-left text-gray-500 border-b border-gray-200">
              <th className="px-4 py-3 font-medium">SKU</th>
              <th className="px-4 py-3 font-medium">Item</th>
              <th className="px-4 py-3 font-medium">Qty</th>
              <th className="px-4 py-3 font-medium">Unit</th>
              <th className="px-4 py-3 font-medium">Line total</th>
            </tr>
          </thead>
          <tbody>
            {(order.items ?? []).map((item) => (
              <tr key={item.id} className="border-b border-gray-100 last:border-0">
                <td className="px-4 py-3 font-mono">{item.sku}</td>
                <td className="px-4 py-3 text-gray-900">{item.name}</td>
                <td className="px-4 py-3">{item.quantity}</td>
                <td className="px-4 py-3">{formatMinor(item.unit_minor)}</td>
                <td className="px-4 py-3 font-medium">{formatMinor(item.line_minor)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </main>
  );
}
