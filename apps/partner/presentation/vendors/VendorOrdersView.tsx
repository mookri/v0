'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import type { VendorOrder } from '@/application/vendor';
import { VendorTabs } from './VendorTabs';
import { describeError, formatDate, formatMinor, type VendorViewError } from './format';

// Vendor orders (client): status list + placement form. Line snapshots
// only exist on the detail page (list rows omit items by design).

type OrderListRow = Omit<VendorOrder, 'items'>;

export function VendorOrdersView({ vendorId, orders }: { vendorId: string; orders: OrderListRow[] }) {
  const router = useRouter();
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function placeOrder(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setStatus(null);
    const form = event.currentTarget;
    const data = new FormData(form);
    const res = await fetch(`/api/vendors/${vendorId}/orders`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        reference: String(data.get('reference') ?? ''),
        customer_name: String(data.get('customer_name') ?? '') || null,
        currency: 'INR',
        items: [
          {
            sku: String(data.get('sku') ?? ''),
            name: String(data.get('item_name') ?? ''),
            quantity: Number.parseInt(String(data.get('quantity') ?? '1'), 10),
            unit_minor: Math.round(Number(String(data.get('unit_price') ?? '0')) * 100),
          },
        ],
      }),
    });
    const body = (await res.json()) as { data?: VendorOrder } & VendorViewError;

    if (!res.ok || !body.data) {
      setStatus(`Failed: ${describeError(body, 'Order placement failed.')}`);
      setBusy(false);
      return;
    }
    setStatus(`Order ${body.data.reference} placed.`);
    form.reset();
    setBusy(false);
    router.refresh();
  }

  const statusTone = (orderStatus: string) =>
    orderStatus === 'DELIVERED'
      ? 'bg-green-100 text-green-800'
      : orderStatus === 'CANCELLED'
        ? 'bg-red-100 text-red-800'
        : orderStatus === 'NEW'
          ? 'bg-blue-100 text-blue-800'
          : 'bg-yellow-100 text-yellow-800';

  return (
    <main className="p-6 max-w-4xl mx-auto">
      <VendorTabs vendorId={vendorId} active="orders" />

      <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm mb-6">
        <h1 className="text-2xl font-bold text-gray-900 mb-4">Place Order</h1>
        <form onSubmit={placeOrder} className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Reference *</span>
            <input
              name="reference"
              required
              maxLength={64}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="PO-1001"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Customer</span>
            <input
              name="customer_name"
              maxLength={255}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">SKU *</span>
            <input
              name="sku"
              required
              maxLength={64}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="SKU-1"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Item name *</span>
            <input
              name="item_name"
              required
              maxLength={255}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="Brass Diya"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Quantity *</span>
            <input
              name="quantity"
              required
              type="number"
              min={1}
              step={1}
              defaultValue={1}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Unit price (₹) *</span>
            <input
              name="unit_price"
              required
              type="number"
              min={0}
              step="0.01"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="99.00"
            />
          </label>
          <div className="sm:col-span-3">
            <button
              type="submit"
              disabled={busy}
              className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
            >
              {busy ? 'Placing…' : 'Place Order'}
            </button>
          </div>
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

      {orders.length === 0 ? (
        <div className="text-center py-12 text-gray-500">No orders yet.</div>
      ) : (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead>
              <tr className="text-left text-gray-500 border-b border-gray-200">
                <th className="px-4 py-3 font-medium">Reference</th>
                <th className="px-4 py-3 font-medium">Customer</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium">Total</th>
                <th className="px-4 py-3 font-medium">Created</th>
                <th className="px-4 py-3 font-medium" aria-label="Actions" />
              </tr>
            </thead>
            <tbody>
              {orders.map((order) => (
                <tr key={order.id} className="border-b border-gray-100 last:border-0">
                  <td className="px-4 py-3 font-medium text-gray-900">{order.reference}</td>
                  <td className="px-4 py-3 text-gray-500">{order.customer_name ?? '—'}</td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${statusTone(order.status)}`}>
                      {order.status.toLowerCase()}
                    </span>
                  </td>
                  <td className="px-4 py-3">{formatMinor(order.total_minor)}</td>
                  <td className="px-4 py-3 text-gray-500">{formatDate(order.created_at)}</td>
                  <td className="px-4 py-3 text-right">
                    <Link
                      href={`/vendors/${vendorId}/orders/${order.id}`}
                      className="text-indigo-600 hover:text-indigo-800 font-medium"
                    >
                      View
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </main>
  );
}
