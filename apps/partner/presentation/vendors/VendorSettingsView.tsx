'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import type { VendorProfile } from '@/application/vendor';
import { VendorTabs } from './VendorTabs';
import { describeError, type VendorViewError } from './format';

// Vendor profile settings (client): PATCH /api/vendors/:vendorId, then
// refresh the server-rendered shell so the overview shows new values.

export function VendorSettingsView({ vendor }: { vendor: VendorProfile }) {
  const router = useRouter();
  const [status, setStatus] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  async function onSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setStatus(null);

    const data = new FormData(event.currentTarget);
    const res = await fetch(`/api/vendors/${vendor.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: String(data.get('name') ?? ''),
        description: String(data.get('description') ?? '') || null,
        city: String(data.get('city') ?? '') || null,
        state: String(data.get('state') ?? '') || null,
      }),
    });
    const body = (await res.json()) as { data?: VendorProfile } & VendorViewError;

    if (!res.ok || !body.data) {
      setStatus(`Failed: ${describeError(body, 'Vendor update failed.')}`);
      setSaving(false);
      return;
    }
    setStatus('Saved.');
    setSaving(false);
    router.refresh();
  }

  return (
    <main className="p-6 max-w-4xl mx-auto">
      <VendorTabs vendorId={vendor.id} active="settings" />

      <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm max-w-2xl">
        <h1 className="text-2xl font-bold text-gray-900 mb-6">Profile Settings</h1>

        <form onSubmit={onSubmit} className="space-y-4">
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Vendor name *</span>
            <input
              name="name"
              required
              minLength={2}
              maxLength={255}
              defaultValue={vendor.name}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Description</span>
            <textarea
              name="description"
              rows={3}
              maxLength={2000}
              defaultValue={vendor.description ?? ''}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </label>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label className="block">
              <span className="block text-sm font-medium text-gray-700 mb-1">City</span>
              <input
                name="city"
                maxLength={120}
                defaultValue={vendor.city ?? ''}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </label>
            <label className="block">
              <span className="block text-sm font-medium text-gray-700 mb-1">State</span>
              <input
                name="state"
                maxLength={120}
                defaultValue={vendor.state ?? ''}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </label>
          </div>

          {status && (
            <p
              role="status"
              className={`text-sm rounded-lg px-3 py-2 border ${
                status.startsWith('Failed')
                  ? 'text-red-600 bg-red-50 border-red-200'
                  : 'text-green-700 bg-green-50 border-green-200'
              }`}
            >
              {status}
            </p>
          )}

          <button
            type="submit"
            disabled={saving}
            className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
          >
            {saving ? 'Saving…' : 'Save Changes'}
          </button>
        </form>
      </div>
    </main>
  );
}
