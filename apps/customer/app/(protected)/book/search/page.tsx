'use client';

import React from 'react';
import { useEffect, useState } from 'react';
import { customerApi } from '@/adapters/backend/customer-api-client';
import { generateIdempotencyKey } from '@/application/idempotency';
import {
  instantSearchBookStartUrl,
  windowEnd,
  type InstantSearchResult,
} from '@/application/instant-search';

interface BookSearchPageProps {
  searchParams: Promise<{ puja_id?: string; date?: string; start?: string }>;
}

// Wall-clock options for the slot picker. Capped at 22:30 so every
// 60-minute window still ends the same day (windowEnd refuses to wrap).
const START_OPTIONS: string[] = (() => {
  const options: string[] = [];
  for (let hour = 6; hour <= 22; hour++) {
    options.push(`${String(hour).padStart(2, '0')}:00`);
    if (hour < 22) options.push(`${String(hour).padStart(2, '0')}:30`);
  }
  return options;
})();

function localDateString(daysAhead: number): string {
  const d = new Date(Date.now() + daysAhead * 86_400_000);
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function formatRate(minor: number | null, currency: string): string | null {
  if (minor === null) return null;
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(minor / 100);
}

export default function BookSearchPage({ searchParams }: BookSearchPageProps) {
  const [pujaId, setPujaId] = useState<string | null>(null);
  const [resolved, setResolved] = useState(false);
  const [date, setDate] = useState(localDateString(1));
  const [start, setStart] = useState('10:00');
  const [results, setResults] = useState<InstantSearchResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    searchParams.then(({ puja_id, date: qDate, start: qStart }) => {
      setPujaId(puja_id ?? null);
      if (qDate) setDate(qDate);
      if (qStart && START_OPTIONS.includes(qStart)) setStart(qStart);
      setResolved(true);
    });
  }, [searchParams]);

  if (!resolved) {
    return <main className="p-6">Loading...</main>;
  }

  // Search is puja-centric: a selection can only complete a booking with
  // a puja, so without one there is nothing actionable to show.
  if (!pujaId) {
    return (
      <main className="p-6 max-w-2xl mx-auto">
        <h1 className="text-2xl font-bold mb-4">Find an instant slot</h1>
        <p className="text-gray-700 mb-4">
          Instant search starts from a ritual — pick one first, then find who is free.
        </p>
        <a href="/discover" className="text-indigo-600 hover:underline">Browse pujas</a>
      </main>
    );
  }

  const runSearch = async () => {
    const end = windowEnd(start);
    if (!end) {
      setError('That start time runs past midnight — pick an earlier slot.');
      return;
    }
    setLoading(true);
    setError('');
    try {
      const data = await customerApi.instantSearch({ date, start, end, puja_id: pujaId });
      setResults(data as InstantSearchResult);
    } catch (e) {
      const code = e instanceof Error ? e.message : '';
      setError(
        code === 'VALIDATION_ERROR'
          ? 'That window is not searchable — check the date and times.'
          : 'Search is unavailable right now. Please try again.',
      );
    } finally {
      setLoading(false);
    }
  };

  // Click-time handoff: same versioned intent contract as every other
  // entry into book/start; timestamps stay fresh without hydration drift.
  const select = (panditId?: string) => {
    try {
      window.location.href = instantSearchBookStartUrl({
        pujaId,
        date,
        start,
        ...(panditId ? { panditId } : {}),
        idempotencyKey: generateIdempotencyKey(),
        nowIso: new Date().toISOString(),
      });
    } catch {
      setError('This selection could not start a booking — please try again.');
    }
  };

  const today = localDateString(0);

  return (
    <main className="p-6 max-w-2xl mx-auto">
      <h1 className="text-2xl font-bold mb-6">Find an instant slot</h1>

      <div className="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label htmlFor="date" className="block text-sm font-medium text-gray-700 mb-2">Date</label>
          <input
            id="date"
            type="date"
            value={date}
            min={today}
            onChange={(e) => setDate(e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label htmlFor="start" className="block text-sm font-medium text-gray-700 mb-2">Start time</label>
          <select
            id="start"
            value={start}
            onChange={(e) => setStart(e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            {START_OPTIONS.map((option) => (
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
        </div>
      </div>

      <button
        type="button"
        onClick={runSearch}
        disabled={loading}
        className="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors mb-6"
      >
        {loading ? 'Searching...' : 'Search'}
      </button>

      {error && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm" role="alert">
          {error}
        </div>
      )}

      {results && (
        <>
          <section className="mb-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-3">
              Assignable pandits ({results.pandits.length})
            </h2>
            {results.pandits.length === 0 ? (
              <p className="text-gray-500 text-sm">No pandit is free for that window.</p>
            ) : (
              <ul className="space-y-3">
                {results.pandits.map((pandit) => {
                  const rate = formatRate(pandit.base_rate_minor, pandit.currency);
                  return (
                    <li key={pandit.id} className="border border-gray-200 rounded-xl p-4 flex items-center justify-between gap-4">
                      <div>
                        <p className="font-medium text-gray-900">{pandit.display_name}</p>
                        <p className="text-sm text-gray-500">
                          ★ {pandit.rating_avg.toFixed(1)} ({pandit.rating_count})
                          {pandit.specializations.length > 0 && ` · ${pandit.specializations.join(', ')}`}
                          {rate && ` · ${rate}`}
                        </p>
                      </div>
                      <button
                        type="button"
                        onClick={() => select(pandit.id)}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors"
                      >
                        Select
                      </button>
                    </li>
                  );
                })}
              </ul>
            )}
          </section>

          <section className="mb-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-3">
              Temple slots ({results.temple_slots.length})
            </h2>
            {results.temple_slots.length === 0 ? (
              <p className="text-gray-500 text-sm">No temple slot is open for that window.</p>
            ) : (
              <ul className="space-y-3">
                {results.temple_slots.map((slot, index) => (
                  <li
                    key={`${slot.temple_id}-${slot.start}-${index}`}
                    className="border border-gray-200 rounded-xl p-4 flex items-center justify-between gap-4"
                  >
                    <div>
                      <p className="font-medium text-gray-900">{slot.temple_name}</p>
                      <p className="text-sm text-gray-500">
                        {slot.city} · {slot.start}–{slot.end}
                        {slot.capacity !== null && ` · ${slot.capacity} spots`}
                      </p>
                    </div>
                    <button
                      type="button"
                      onClick={() => select()}
                      className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors"
                    >
                      Select
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </section>
        </>
      )}

      <a href="/discover" className="text-indigo-600 hover:underline">← Back to discover</a>
    </main>
  );
}
