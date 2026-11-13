'use client';

import React from 'react';
import { useState, useEffect } from 'react';
import {
  CANONICAL_GOTRAS,
  CANONICAL_NAKSHATRAS,
  SERVICE_VENUES,
  type ServiceVenue,
} from '@navdhya/validation';
import { customerApi } from '@/adapters/backend/customer-api-client';
import { resolveBookStart } from '@/application/bookings/resolve-book-start';
import { confirmBookingDraft } from '@/application/bookings/confirm-booking-draft';
import type { BookingDraft } from '@/application/bookings/start-booking-from-intent';
import { generateIdempotencyKey, storeIdempotencyKey } from '@/application/idempotency';

interface BookStartPageProps {
  searchParams: Promise<{ intent?: string }>;
}

// datetime-local value (local wall time) for the slot picker's minimum.
function toLocalInput(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

// Only a fully-picked YYYY-MM-DDTHH:mm value becomes an ISO schedule;
// partial input never reaches the reservation API.
function slotToIso(value: string): string | null {
  if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(value)) return null;
  const iso = new Date(value);
  return Number.isNaN(iso.getTime()) ? null : iso.toISOString();
}

const FRIENDLY_ERRORS: Record<string, string> = {
  NOT_FOUND: 'This puja is no longer available.',
  PUJA_SKU_UNAVAILABLE: 'This ritual is not currently bookable.',
  INVALID_SERVICE_VENUE: 'Choose whether the ritual happens at Home, Temple, or Online.',
  INVALID_GOTRA: 'Please select a canonical Gotra from the list.',
  INVALID_NAKSHATRA: 'Please select a canonical Nakshatra from the list.',
};

export default function BookStartPage({ searchParams }: BookStartPageProps) {
  const [isCreating, setIsCreating] = useState(false);
  const [error, setError] = useState('');
  const [slotValue, setSlotValue] = useState('');
  const [serviceVenue, setServiceVenue] = useState<ServiceVenue>('home');
  const [gotra, setGotra] = useState('');
  const [nakshatra, setNakshatra] = useState('');
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
      // The intent carries the catalog item id; the reservation API wants
      // the SKU. Resolve it through the puja read and post that — never
      // the id (confirmBookingDraft fails closed if no SKU exists).
      const booking = await confirmBookingDraft(
        {
          getPuja: async (id) => (await customerApi.getPuja(id)) as { sku?: string },
          createBooking: (payload, idempotencyKey) => customerApi.createBooking(payload, idempotencyKey),
        },
        { ...draft, serviceVenue, gotra, nakshatra },
      );

      // Redirect to booking detail
      window.location.href = `/bookings/${(booking as { id: string }).id}`;
    } catch (e) {
      const code = e instanceof Error ? e.message : '';
      setError(FRIENDLY_ERRORS[code] ?? (code || 'Failed to create booking'));
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

  // Web entry collects partial data; the customer completes it. A puja
  // intent without a slot opens the picker (we never invent a schedule);
  // a ready intent goes straight to review.
  const intent = result.intent;
  const needsSlotOnly =
    result.status === 'incomplete' &&
    intent.pujaId !== undefined &&
    result.needs.length === 1 &&
    result.needs[0] === 'slot';
  const slotIso = slotToIso(slotValue);

  let draft: BookingDraft | null = null;
  if (result.status === 'ready') {
    draft = result.draft;
  } else if (needsSlotOnly && intent.pujaId !== undefined && slotIso !== null) {
    draft = {
      pujaId: intent.pujaId,
      panditId: intent.panditId,
      slotRef: slotIso,
      idempotencyKey: intent.idempotencyKey,
      source: intent.source,
    };
  }

  if (!draft) {
    if (result.status !== 'incomplete') {
      return null;
    }
    if (needsSlotOnly) {
      return (
        <main className="p-6 max-w-2xl mx-auto">
          <h1 className="text-2xl font-bold mb-6">Choose a date and time</h1>
          <dl className="space-y-4 mb-6">
            <div>
              <dt className="text-gray-500 text-sm">Puja</dt>
              <dd className="font-medium text-gray-900">{intent.pujaId}</dd>
            </div>
            {intent.panditId && (
              <div>
                <dt className="text-gray-500 text-sm">Pandit</dt>
                <dd className="font-medium text-gray-900">{intent.panditId}</dd>
              </div>
            )}
          </dl>
          <label htmlFor="slot" className="block text-sm font-medium text-gray-700 mb-2">
            Preferred start time
          </label>
          <input
            id="slot"
            type="datetime-local"
            value={slotValue}
            min={toLocalInput(new Date(Date.now() + 60_000))}
            onChange={(e) => setSlotValue(e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <p className="mt-3 text-sm text-gray-500">
            Pick when the ritual should start — you choose the time, we never pick one for you.
          </p>
          <a href="/discover" className="mt-4 inline-block text-indigo-600 hover:underline">← Back to discover</a>
        </main>
      );
    }
    return (
      <main className="p-6 max-w-2xl mx-auto">
        <h1 className="text-2xl font-bold mb-4">Almost there</h1>
        <p className="text-gray-700 mb-4">To complete your booking we still need: {result.needs.join(', ')}.</p>
        <a href="/discover" className="text-indigo-600 hover:underline">Continue</a>
      </main>
    );
  }

  // Draft ready for review. The intent's key is stable per handoff, so
  // a retry of the same web intent dedups server-side; a generated key
  // only covers in-app flows that arrive without one.
  const idempotencyKey = draft.idempotencyKey || generateIdempotencyKey();
  storeIdempotencyKey(draft.pujaId, idempotencyKey);

  return (
    <main className="p-6 max-w-2xl mx-auto text-[#2A2421]">
      <h1 className="text-2xl font-bold mb-6 text-[#2C2033]">Review your booking</h1>

      <dl className="space-y-4 mb-6 rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
        <div>
          <dt className="text-[#2C2033] text-sm font-medium">Puja</dt>
          <dd className="font-medium text-[#2A2421]">{draft.pujaId}</dd>
        </div>
        {draft.panditId && (
          <div>
            <dt className="text-[#2C2033] text-sm font-medium">Pandit</dt>
            <dd className="font-medium text-[#2A2421]">{draft.panditId}</dd>
          </div>
        )}
        <div>
          <dt className="text-[#2C2033] text-sm font-medium">Slot</dt>
          <dd className="font-medium text-[#2A2421]">{draft.slotRef ? new Date(draft.slotRef).toLocaleString() : 'TBD'}</dd>
        </div>
      </dl>

      <div className="space-y-4 mb-6 rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
        <div>
          <label htmlFor="service_venue" className="block text-sm font-medium text-[#2C2033] mb-1">
            Ritual Venue
          </label>
          <select
            id="service_venue"
            value={serviceVenue}
            onChange={(e) => setServiceVenue(e.target.value as ServiceVenue)}
            className="w-full rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] px-3 py-2.5 text-[#2A2421] focus:outline-none focus:ring-2 focus:ring-[#F16A24]"
            disabled={isCreating}
          >
            {SERVICE_VENUES.map((v) => (
              <option key={v} value={v}>
                {v === 'home' ? 'At Home / Residence' : v === 'temple' ? 'At Temple Sanctuary' : 'Online Live Stream'}
              </option>
            ))}
          </select>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label htmlFor="booking_gotra" className="block text-sm font-medium text-[#2C2033] mb-1">
              Gotra (Sankalpa, optional)
            </label>
            <select
              id="booking_gotra"
              value={gotra}
              onChange={(e) => setGotra(e.target.value)}
              className="w-full rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] px-3 py-2.5 text-[#2A2421] focus:outline-none focus:ring-2 focus:ring-[#F16A24]"
              disabled={isCreating}
            >
              <option value="">Not specified</option>
              {CANONICAL_GOTRAS.map((g) => (
                <option key={g} value={g}>{g}</option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="booking_nakshatra" className="block text-sm font-medium text-[#2C2033] mb-1">
              Nakshatra (optional)
            </label>
            <select
              id="booking_nakshatra"
              value={nakshatra}
              onChange={(e) => setNakshatra(e.target.value)}
              className="w-full rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] px-3 py-2.5 text-[#2A2421] focus:outline-none focus:ring-2 focus:ring-[#F16A24]"
              disabled={isCreating}
            >
              <option value="">Not specified</option>
              {CANONICAL_NAKSHATRAS.map((n) => (
                <option key={n} value={n}>{n.replace(/_/g, ' ')}</option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {error && (
        <div className="mb-4 p-3 bg-[#FFF7ED] border border-[#B74717] rounded-lg text-[#B74717] text-sm" role="alert">
          {error}
        </div>
      )}

      <button
        onClick={() => handleCreateBooking({ pujaId: draft.pujaId, slotRef: draft.slotRef ?? '', idempotencyKey })}
        disabled={isCreating || !draft.slotRef}
        className="w-full px-6 py-3 bg-[#2C2033] text-[#FFF7ED] font-semibold rounded-lg hover:bg-[#2C2033]/90 disabled:opacity-50 transition-colors"
      >
        {isCreating ? 'Creating booking...' : 'Confirm booking'}
      </button>

      <p className="mt-4 text-center text-sm text-[#2A2421]">
        <a href="/discover" className="text-[#B74717] hover:underline">← Back to discover</a>
      </p>
    </main>
  );
}
