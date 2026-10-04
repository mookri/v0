'use client';

import React from 'react';
import { useState, useEffect } from 'react';
import { customerApi } from '@/adapters/backend/customer-api-client';
import { resolveBookStart } from '@/application/bookings/resolve-book-start';
import { generateIdempotencyKey, storeIdempotencyKey } from '@/application/idempotency';

interface BookStartPageProps {
  searchParams: Promise<{ intent?: string }>;
}

export default function BookStartPage({ searchParams }: BookStartPageProps) {
  const [isCreating, setIsCreating] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState<ReturnType<typeof resolveBookStart> | null>(null);

  // Resolve intent on mount
  useEffect(() => {
    searchParams.then(({ intent }) => {
      setResult(resolveBookStart(intent, new Date().toISOString()));
    });
  }, [searchParams]);

  if (!result) {
    return <main className="p-6">Loading...</main>;
  }

  const handleCreateBooking = async (draft: { pujaId: string; slotRef: string; idempotencyKey: string }) => {
    setIsCreating(true);
    setError('');

    try {
      const booking = await customerApi.createBooking({
        service_sku: draft.pujaId,
        scheduled_at: draft.slotRef,
        duration_minutes: 60,
      }, draft.idempotencyKey);

      // Redirect to booking detail
      window.location.href = `/bookings/${(booking as { id: string }).id}`;
    } catch (e: any) {
      setError(e.message ?? 'Failed to create booking');
    } finally {
      setIsCreating(false);
    }
  };

  if (result.status === 'missing' || result.status === 'invalid') {
    return (
      <main className="p-6 max-w-2xl mx-auto">
        <h1 className="text-2xl font-bold mb-4">Start a booking</h1>
        <p className="text-gray-700 mb-4">
          {result.status === 'missing'
            ? 'Choose a puja to begin — no booking link was provided.'
            : 'This booking link is no longer valid. Please start again.'}
        </p>
        <a href="/discover" className="text-indigo-600 hover:underline">Browse pujas</a>
      </main>
    );
  }

  if (result.status === 'incomplete') {
    return (
      <main className="p-6 max-w-2xl mx-auto">
        <h1 className="text-2xl font-bold mb-4">Almost there</h1>
        <p className="text-gray-700 mb-4">To complete your booking we still need: {result.needs.join(', ')}.</p>
        <a href="/discover" className="text-indigo-600 hover:underline">Continue</a>
      </main>
    );
  }

  // status === 'ready'
  const draft = result.draft;
  const idempotencyKey = generateIdempotencyKey();
  storeIdempotencyKey(draft.pujaId, idempotencyKey);

  return (
    <main className="p-6 max-w-2xl mx-auto">
      <h1 className="text-2xl font-bold mb-6">Review your booking</h1>

      <dl className="space-y-4 mb-6">
        <div>
          <dt className="text-gray-500 text-sm">Puja</dt>
          <dd className="font-medium text-gray-900">{draft.pujaId}</dd>
        </div>
        {draft.panditId && (
          <div>
            <dt className="text-gray-500 text-sm">Pandit</dt>
            <dd className="font-medium text-gray-900">{draft.panditId}</dd>
          </div>
        )}
        <div>
          <dt className="text-gray-500 text-sm">Slot</dt>
          <dd className="font-medium text-gray-900">{draft.slotRef ? new Date(draft.slotRef).toLocaleString() : 'TBD'}</dd>
        </div>
      </dl>

      {error && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm" role="alert">
          {error}
        </div>
      )}

      <button
        onClick={() => handleCreateBooking({ pujaId: draft.pujaId, slotRef: draft.slotRef ?? '', idempotencyKey })}
        disabled={isCreating || !draft.slotRef}
        className="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
      >
        {isCreating ? 'Creating booking...' : 'Confirm booking'}
      </button>

      <p className="mt-4 text-center text-sm text-gray-500">
        <a href="/discover" className="text-indigo-600 hover:underline">← Back to discover</a>
      </p>
    </main>
  );
}