'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import type { VendorProduct } from '@/application/vendor';
import { VendorTabs } from './VendorTabs';
import { describeError, formatMinor, type VendorViewError } from './format';

// Vendor products (client): catalog rows (limit list) + create form and
// inline name/price edits. Status is backend-owned (items start DRAFT).

export function VendorProductsView({ vendorId, products }: { vendorId: string; products: VendorProduct[] }) {
  const router = useRouter();
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  function applyStatus(res: Response, body: { data?: unknown } & VendorViewError, success: string) {
    if (!res.ok || !body.data) {
      setStatus(`Failed: ${describeError(body, 'Request failed.')}`);
      setBusy(false);
      return false;
    }
    setStatus(success);
    setBusy(false);
    router.refresh();
    return true;
  }

  async function createProduct(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setStatus(null);
    const form = event.currentTarget;
    const data = new FormData(form);
    const res = await fetch(`/api/vendors/${vendorId}/products`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        sku: String(data.get('sku') ?? ''),
        name: String(data.get('name') ?? ''),
        description: String(data.get('description') ?? '') || null,
        price_minor: Math.round(Number(String(data.get('price') ?? '0')) * 100),
      }),
    });
    const body = (await res.json()) as { data?: VendorProduct } & VendorViewError;
    if (applyStatus(res, body, 'Product created.')) form.reset();
  }

  async function saveProduct(event: React.FormEvent<HTMLFormElement>, productId: string) {
    event.preventDefault();
    setBusy(true);
    setStatus(null);
    const data = new FormData(event.currentTarget);
    const res = await fetch(`/api/vendors/${vendorId}/products/${productId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: String(data.get('name') ?? ''),
        price_minor: Math.round(Number(String(data.get('price') ?? '0')) * 100),
      }),
    });
    const body = (await res.json()) as { data?: VendorProduct } & VendorViewError;
    applyStatus(res, body, 'Product updated.');
  }

  return (
    <main className="p-6 max-w-4xl mx-auto">
      <VendorTabs vendorId={vendorId} active="products" />

      <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm mb-6">
        <h1 className="text-2xl font-bold text-gray-900 mb-4">Add Product</h1>
        <form onSubmit={createProduct} className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
            <span className="block text-sm font-medium text-gray-700 mb-1">Name *</span>
            <input
              name="name"
              required
              minLength={2}
              maxLength={255}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="Brass Diya"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Description</span>
            <input
              name="description"
              maxLength={2000}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Price (₹) *</span>
            <input
              name="price"
              required
              type="number"
              min={0}
              step="0.01"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="99.00"
            />
          </label>
          <div className="sm:col-span-2">
            <button
              type="submit"
              disabled={busy}
              className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
            >
              {busy ? 'Saving…' : 'Add Product'}
            </button>
          </div>
        </form>
      </div>

      {status && (
        <p
          role="status"
          className={`text-sm rounded-lg px-3 py-2 border mb-6 ${
            status.startsWith('Failed') ? 'text-red-600 bg-red-50 border-red-200' : 'text-green-700 bg-green-50 border-green-200'
          }`}
        >
          {status}
        </p>
      )}

      {products.length === 0 ? (
        <div className="text-center py-12 text-gray-500">No products yet — add your first SKU above.</div>
      ) : (
        <ul className="space-y-4">
          {products.map((product) => (
            <li key={product.id} className="bg-white rounded-xl p-5 shadow-sm border border-gray-200">
              <div className="flex items-center justify-between gap-4 mb-3">
                <div>
                  <span className="text-sm font-mono text-gray-500">{product.sku}</span>
                  <span
                    className={`ml-3 inline-flex items-center px-2 py-1 text-xs font-medium rounded-full capitalize ${
                      product.status === 'ACTIVE'
                        ? 'bg-green-100 text-green-800'
                        : product.status === 'ARCHIVED'
                          ? 'bg-gray-200 text-gray-700'
                          : 'bg-yellow-100 text-yellow-800'
                    }`}
                  >
                    {product.status.toLowerCase()}
                  </span>
                </div>
                <span className="text-sm text-gray-500">{formatMinor(product.price_minor)}</span>
              </div>
              <form onSubmit={(event) => void saveProduct(event, product.id)} className="flex flex-wrap items-end gap-3">
                <label className="flex-1 min-w-40">
                  <span className="block text-xs font-medium text-gray-500 mb-1">Name</span>
                  <input
                    name="name"
                    defaultValue={product.name}
                    minLength={2}
                    maxLength={255}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </label>
                <label className="w-32">
                  <span className="block text-xs font-medium text-gray-500 mb-1">Price (₹)</span>
                  <input
                    name="price"
                    type="number"
                    min={0}
                    step="0.01"
                    defaultValue={(product.price_minor / 100).toString()}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </label>
                <button
                  type="submit"
                  disabled={busy}
                  className="px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
                >
                  Save
                </button>
              </form>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}
