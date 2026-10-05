'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import type { VendorViewError } from './format';

// Vendor creation (client): POST /api/vendors. The mock and Laravel both
// answer 409 CONFLICT when the partner-vendor application is missing or
// unapproved — surface the envelope message verbatim.

export function VendorCreateView() {
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    const data = new FormData(event.currentTarget);
    const res = await fetch('/api/vendors', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: String(data.get('name') ?? ''),
        description: String(data.get('description') ?? '') || null,
        city: String(data.get('city') ?? '') || null,
        state: String(data.get('state') ?? '') || null,
      }),
    });
    const body = (await res.json()) as { data?: { id: string } } & VendorViewError;

    if (!res.ok || !body.data) {
      setError(`${body.error?.code ?? 'ERROR'} — ${body.error?.message ?? 'Vendor creation failed.'}`);
      setSubmitting(false);
      return;
    }
    router.push(`/vendors/${body.data.id}`);
  }

  return (
    <main className="p-6 max-w-2xl mx-auto">
      <Link href="/vendors" className="inline-flex items-center text-indigo-600 hover:text-indigo-800 mb-6">
        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
        </svg>
        Back to Vendors
      </Link>

      <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Create Vendor</h1>
        <p className="text-sm text-gray-500 mb-6">
          Requires an approved partner-vendor application on your account.
        </p>

        <form onSubmit={onSubmit} className="space-y-4">
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Vendor name *</span>
            <input
              name="name"
              required
              minLength={2}
              maxLength={255}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="Gita Bhandar"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-1">Description</span>
            <textarea
              name="description"
              rows={3}
              maxLength={2000}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="Puja samagri and ritual supplies"
            />
          </label>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label className="block">
              <span className="block text-sm font-medium text-gray-700 mb-1">City</span>
              <input
                name="city"
                maxLength={120}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </label>
            <label className="block">
              <span className="block text-sm font-medium text-gray-700 mb-1">State</span>
              <input
                name="state"
                maxLength={120}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </label>
          </div>

          {error && (
            <p role="alert" className="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
              {error}
            </p>
          )}

          <div className="flex items-center gap-3 pt-2">
            <button
              type="submit"
              disabled={submitting}
              className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
            >
              {submitting ? 'Creating…' : 'Create Vendor'}
            </button>
            <Link href="/vendors" className="text-sm text-gray-500 hover:text-gray-700">
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </main>
  );
}
