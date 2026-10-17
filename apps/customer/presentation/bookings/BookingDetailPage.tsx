'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import {
  CustomerBookingDetails,
  bookingAction,
  otherBookingActions,
} from '@/application/bookings/get-customer-booking-details';
import { rescheduleIsoFromInput } from '@/application/bookings/reschedule-from-input';
import { generateIdempotencyKey } from '@/application/idempotency';
import { customerApi } from '@/adapters/backend/customer-api-client';

interface BookingDetailPageProps {
  details: CustomerBookingDetails | null;
  history: Array<{ status: string; timestamp: string; note?: string }>;
  error?: string;
}

export function BookingDetailPage({ details, history, error }: BookingDetailPageProps) {
  const [activeTab, setActiveTab] = useState<'details' | 'history'>('details');
  const [busy, setBusy] = useState<'cancel' | 'reschedule' | null>(null);
  const [flash, setFlash] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [rescheduleValue, setRescheduleValue] = useState('');
  const router = useRouter();

  // Capability reads live in the application layer (customer-features
  // boundary: presentation derives no business rules of its own).
  const cancellable = bookingAction(details, 'cancel');
  const reschedulable = bookingAction(details, 'reschedule');
  const infoActions = otherBookingActions(details);

  async function handleCancel() {
    if (!details || busy) return;
    if (!window.confirm('Cancel this booking? This cannot be undone.')) return;
    setBusy('cancel');
    setActionError(null);
    setFlash(null);
    try {
      await customerApi.cancelBooking(details.id, generateIdempotencyKey());
      setFlash('Booking cancelled.');
      router.refresh();
    } catch (e: any) {
      setActionError(e?.message ?? 'Could not cancel this booking.');
    } finally {
      setBusy(null);
    }
  }

  async function handleReschedule() {
    if (!details || busy) return;
    const iso = rescheduleIsoFromInput(rescheduleValue);
    if (!iso) {
      setActionError('Pick a future date and time.');
      return;
    }
    setBusy('reschedule');
    setActionError(null);
    setFlash(null);
    try {
      await customerApi.rescheduleBooking(details.id, iso, generateIdempotencyKey());
      setFlash('Booking rescheduled.');
      setRescheduleValue('');
      router.refresh();
    } catch (e: any) {
      setActionError(e?.message ?? 'Could not reschedule this booking.');
    } finally {
      setBusy(null);
    }
  }

  if (error) {
    return (
      <main className="p-6">
        <h1 className="text-2xl font-bold mb-4">Booking unavailable</h1>
        <p className="text-red-600" role="alert">{error}</p>
        <a href="/bookings" className="text-indigo-600 hover:underline">Back to bookings</a>
      </main>
    );
  }

  if (!details) {
    return (
      <main className="p-6">
        <h1 className="text-2xl font-bold mb-4">Booking not found</h1>
        <a href="/bookings" className="text-indigo-600 hover:underline">Back to bookings</a>
      </main>
    );
  }

  return (
    <main className="p-6 max-w-3xl mx-auto">
      <div className="mb-6">
        <h1 className="text-2xl font-bold">Booking Details</h1>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 mb-6">
        <nav className="flex gap-4" aria-label="Booking tabs">
          <button
            onClick={() => setActiveTab('details')}
            className={`py-3 px-4 border-b-2 font-medium text-sm transition-colors ${
              activeTab === 'details'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            }`}
          >
            Details
          </button>
          <button
            onClick={() => setActiveTab('history')}
            className={`py-3 px-4 border-b-2 font-medium text-sm transition-colors ${
              activeTab === 'history'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            }`}
          >
            History
          </button>
        </nav>
      </div>

      {activeTab === 'details' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-200 rounded-xl p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Booking Information</h2>
            <dl className="space-y-3">
              <div className="flex justify-between">
                <dt className="text-gray-500">Status</dt>
                <dd className="font-medium">
                  <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                    details.status === 'COMPLETED' ? 'bg-green-100 text-green-800' :
                    details.status === 'CANCELLED' ? 'bg-red-100 text-red-800' :
                    details.status === 'ASSIGNED' ? 'bg-blue-100 text-blue-800' :
                    details.status === 'IN_PROGRESS' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-gray-100 text-gray-800'
                  }`}>
                    {details.status}
                  </span>
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-gray-500">Date & Time</dt>
                <dd className="font-medium text-gray-900">
                  {details.schedule.date} at {details.schedule.time}
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-gray-500">Puja</dt>
                <dd className="font-medium text-gray-900">{details.puja.name}</dd>
              </div>
              {details.pandit && (
                <div className="flex justify-between">
                  <dt className="text-gray-500">Pandit</dt>
                  <dd className="font-medium text-gray-900">{details.pandit.name}</dd>
                </div>
              )}
              
            </dl>
          </div>

          {(cancellable || reschedulable || infoActions.length > 0) && (
            <div className="bg-white border border-gray-200 rounded-xl p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">Actions</h2>

              {flash && (
                <p className="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3" role="status">
                  {flash}
                </p>
              )}
              {actionError && (
                <p className="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3" role="alert">
                  {actionError}
                </p>
              )}

              {reschedulable && (
                <div className="mb-4">
                  <label htmlFor="reschedule-datetime" className="block text-sm font-medium text-gray-700 mb-1">
                    Reschedule to
                  </label>
                  <div className="flex flex-col sm:flex-row gap-2">
                    <input
                      id="reschedule-datetime"
                      type="datetime-local"
                      value={rescheduleValue}
                      onChange={(e) => setRescheduleValue(e.target.value)}
                      className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                    />
                    <button
                      onClick={handleReschedule}
                      disabled={busy !== null}
                      className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                      {busy === 'reschedule' ? 'Rescheduling…' : 'Reschedule'}
                    </button>
                  </div>
                </div>
              )}

              {cancellable && (
                <button
                  onClick={handleCancel}
                  disabled={busy !== null}
                  className="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50"
                >
                  {busy === 'cancel' ? 'Cancelling…' : 'Cancel booking'}
                </button>
              )}

              {infoActions.length > 0 && (
                <ul className="space-y-2 mt-4">
                  {infoActions.map((a) => (
                    <li key={a} className="text-gray-700 flex items-center gap-2">
                      <span className="w-2 h-2 bg-indigo-500 rounded-full"></span>
                      {a}
                    </li>
                  ))}
                </ul>
              )}
            </div>
          )}
        </div>
      )}

      {activeTab === 'history' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-200 rounded-xl p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Status History</h2>
            {history.length === 0 ? (
              <p className="text-gray-500 text-center py-8">No history available</p>
            ) : (
              <dl className="space-y-4">
                {history.map((transition, index) => (
                  <div
                    key={`${transition.timestamp}-${index}`}
                    className="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-gray-50 rounded-lg"
                  >
                    <div className="flex items-center gap-3">
                      <div className="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span className="text-indigo-600 font-bold text-sm">{index + 1}</span>
                      </div>
                      <div>
                        <dt className="text-sm font-medium text-gray-900">{transition.status}</dt>
                        {transition.note && (
                          <dd className="text-sm text-gray-500">{transition.note}</dd>
                        )}
                      </div>
                    </div>
                    <dd className="mt-2 sm:mt-0 text-sm text-gray-500 whitespace-nowrap">
                      {new Date(transition.timestamp).toLocaleString()}
                    </dd>
                  </div>
                ))}
              </dl>
            )}
          </div>
        </div>
      )}
    </main>
  );
}