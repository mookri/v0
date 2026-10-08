'use client';

import Link from 'next/link';
import { generateIdempotencyKey } from '@/application/idempotency';
import { pujaBookStartUrl } from '@/application/bookings/puja-book-start-url';

interface PujaDetailPageProps {
  puja: {
    id: string;
    title: string;
    kind: 'puja';
    sku: string;
    currency: string;
    price_minor: number;
    description?: string;
  };
  error?: string;
}

function formatPrice(minor: number, currency: string): string {
  const major = minor / 100;
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(major);
}

export function PujaDetailPageView({ puja, error }: PujaDetailPageProps) {
  if (error) {
    return (
      <main className="p-6 max-w-2xl mx-auto">
        <h1 className="text-2xl font-bold mb-4 text-red-600" role="alert">Puja unavailable</h1>
        <p className="text-gray-700">{error}</p>
        <Link href="/discover" className="mt-4 inline-block text-indigo-600 hover:underline">
          ← Back to discover
        </Link>
      </main>
    );
  }

  const price = formatPrice(puja.price_minor, puja.currency);

  return (
    <main className="p-6 max-w-2xl mx-auto">
      <Link href="/discover" className="mb-6 inline-flex items-center gap-1 text-gray-500 hover:text-gray-700">
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
        </svg>
        Back to discover
      </Link>

      <article className="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <header className="p-6 border-b border-gray-200">
          <h1 className="text-2xl font-bold text-gray-900">{puja.title}</h1>
          <p className="mt-1 text-gray-500">SKU: <code className="text-gray-700 font-mono text-sm">{puja.sku}</code></p>
        </header>

        {puja.description && (
          <section className="p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900 mb-2">About this ritual</h2>
            <p className="text-gray-700 whitespace-pre-wrap">{puja.description}</p>
          </section>
        )}

        <footer className="p-6 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex items-baseline gap-2">
            <span className="text-gray-500">Starting from</span>
            <span className="text-2xl font-bold text-gray-900">{price}</span>
          </div>

          {/* Click-time intent build: the receiver needs a versioned,
              encoded intent, and building it here keeps the timestamps
              fresh without render/hydration drift. */}
          <button
            type="button"
            onClick={() => {
              window.location.href = pujaBookStartUrl(
                puja.id,
                generateIdempotencyKey(),
                new Date().toISOString(),
              );
            }}
            className="w-full sm:w-auto px-6 py-3 bg-indigo-600 text-white rounded-lg text-center hover:bg-indigo-700 transition-colors"
          >
            Book this puja
          </button>
        </footer>
      </article>
    </main>
  );
}