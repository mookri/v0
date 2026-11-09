'use client';

import { useState, useEffect } from 'react';
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

function getStatusBadgeStyle(status: string): string {
  switch (status) {
    case 'COMPLETED':
      return 'bg-[#EFE3D3] text-[#2C2033] border border-[#2C2033]/20';
    case 'PENDING_COUNCIL_APPROVAL':
      return 'bg-[#2C2033] text-[#FFF7ED] border border-[#2C2033]';
    case 'CONFIRMED':
      return 'bg-[#2C2033] text-[#FFF7ED]';
    case 'ASSIGNED':
      return 'bg-[#EFE3D3] text-[#2A2421] border border-[#EFE3D3]';
    case 'IN_PROGRESS':
      return 'bg-[#F16A24] text-white';
    case 'PENDING_PAYMENT':
      return 'bg-[#FFF7ED] text-[#B74717] border border-[#F16A24]';
    case 'CANCELLED':
      return 'bg-[#B74717]/10 text-[#B74717] border border-[#B74717]/30';
    case 'DRAFT':
    default:
      return 'bg-[#EFE3D3]/60 text-[#2A2421] border border-[#EFE3D3]';
  }
}

export function BookingDetailPage({ details, history, error }: BookingDetailPageProps) {
  const [activeTab, setActiveTab] = useState<'details' | 'history'>('details');
  const [busy, setBusy] = useState<'cancel' | 'reschedule' | null>(null);
  const [flash, setFlash] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [rescheduleValue, setRescheduleValue] = useState('');
  const router = useRouter();

  // Hold countdown timer for PENDING_PAYMENT
  const [remainingSeconds, setRemainingSeconds] = useState<number | null>(() => {
    if (details?.status === 'PENDING_PAYMENT' && details?.checkoutExpiresAt) {
      return Math.max(0, Math.floor((new Date(details.checkoutExpiresAt).getTime() - Date.now()) / 1000));
    }
    return null;
  });

  useEffect(() => {
    if (details?.status !== 'PENDING_PAYMENT' || !details?.checkoutExpiresAt) {
      return;
    }
    const interval = setInterval(() => {
      const remaining = Math.max(0, Math.floor((new Date(details.checkoutExpiresAt!).getTime() - Date.now()) / 1000));
      setRemainingSeconds(remaining);
      if (remaining <= 0) {
        clearInterval(interval);
      }
    }, 1000);
    return () => clearInterval(interval);
  }, [details?.status, details?.checkoutExpiresAt]);

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
      await customerApi.rescheduleBooking(details.id, iso, details.version, generateIdempotencyKey());
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
      <main className="p-6 bg-[#FFF7ED] min-h-screen text-[#2A2421]">
        <h1 className="text-2xl font-bold mb-4 text-[#2C2033]">Booking unavailable</h1>
        <p className="text-[#B74717]" role="alert">{error}</p>
        <a href="/bookings" className="text-[#F16A24] hover:underline mt-4 inline-block">Back to bookings</a>
      </main>
    );
  }

  if (!details) {
    return (
      <main className="p-6 bg-[#FFF7ED] min-h-screen text-[#2A2421]">
        <h1 className="text-2xl font-bold mb-4 text-[#2C2033]">Booking not found</h1>
        <a href="/bookings" className="text-[#F16A24] hover:underline">Back to bookings</a>
      </main>
    );
  }

  const formatCountdown = (secs: number) => {
    const mins = Math.floor(secs / 60);
    const s = secs % 60;
    return `${mins}:${s < 10 ? '0' : ''}${s}`;
  };

  return (
    <main className="p-6 max-w-3xl mx-auto bg-[#FFF7ED] min-h-screen text-[#2A2421]">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-[#2C2033]">Booking Details</h1>
      </div>

      {/* Hold countdown alert */}
      {details.status === 'PENDING_PAYMENT' && remainingSeconds !== null && (
        <div className="mb-6 p-4 rounded-xl bg-[#FFF7ED] border-2 border-[#F16A24] shadow-sm flex items-center justify-between">
          <div>
            <p className="font-semibold text-[#2C2033]">Ritual slot temporarily held</p>
            <p className="text-sm text-[#2A2421]">Complete payment to secure your ritual slot.</p>
          </div>
          <div className="text-right">
            <span className="text-xs uppercase font-medium text-[#B74717]">Expires in</span>
            <p className="text-2xl font-bold text-[#F16A24] font-mono">
              {remainingSeconds > 0 ? formatCountdown(remainingSeconds) : 'EXPIRED'}
            </p>
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="border-b border-[#EFE3D3] mb-6">
        <nav className="flex gap-4" aria-label="Booking tabs">
          <button
            onClick={() => setActiveTab('details')}
            className={`py-3 px-4 border-b-2 font-medium text-sm transition-colors ${
              activeTab === 'details'
                ? 'border-[#F16A24] text-[#2C2033] font-semibold'
                : 'border-transparent text-[#2A2421]/60 hover:text-[#2C2033]'
            }`}
          >
            Details
          </button>
          <button
            onClick={() => setActiveTab('history')}
            className={`py-3 px-4 border-b-2 font-medium text-sm transition-colors ${
              activeTab === 'history'
                ? 'border-[#F16A24] text-[#2C2033] font-semibold'
                : 'border-transparent text-[#2A2421]/60 hover:text-[#2C2033]'
            }`}
          >
            History
          </button>
        </nav>
      </div>

      {activeTab === 'details' && (
        <div className="space-y-4">
          <div className="bg-white border border-[#EFE3D3] rounded-xl p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-[#2C2033] mb-4">Booking Information</h2>
            <dl className="space-y-3">
              <div className="flex justify-between items-center">
                <dt className="text-[#2A2421]/70">Status</dt>
                <dd className="font-medium">
                  <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusBadgeStyle(details.status)}`}>
                    {details.status}
                  </span>
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-[#2A2421]/70">Date & Time</dt>
                <dd className="font-medium text-[#2C2033]">
                  {details.schedule.date} at {details.schedule.time}
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-[#2A2421]/70">Puja</dt>
                <dd className="font-medium text-[#2C2033]">{details.puja.name}</dd>
              </div>
              {details.pandit && (
                <div className="flex justify-between">
                  <dt className="text-[#2A2421]/70">Pandit</dt>
                  <dd className="font-medium text-[#2C2033]">{details.pandit.name}</dd>
                </div>
              )}

              {/* Commercials & Pricing */}
              {details.amountMinor !== undefined && details.amountMinor > 0 && (
                <div className="flex justify-between items-center pt-2 border-t border-[#EFE3D3]">
                  <dt className="text-[#2A2421]/70">Commercial Total</dt>
                  <dd className="font-semibold text-[#2C2033]">
                    {details.currency ?? 'INR'} {(details.amountMinor / 100).toFixed(2)}
                    {details.acceptedQuoteHash && (
                      <span className="ml-2 text-xs font-normal px-2 py-0.5 rounded bg-[#EFE3D3] text-[#2C2033]">
                        Locked
                      </span>
                    )}
                  </dd>
                </div>
              )}

              {/* Samagri Kit Shipment Tracking */}
              {details.shipment && (
                <div className="mt-4 pt-4 border-t border-[#EFE3D3]">
                  <h3 className="text-sm font-semibold text-[#2C2033] mb-2">Samagri Kit Dispatch</h3>
                  <div className="bg-[#FFF7ED] border border-[#EFE3D3] rounded-lg p-3 flex justify-between items-center">
                    <div>
                      <span className="text-xs font-medium text-[#B74717] uppercase tracking-wider block">
                        {details.shipment.status}
                      </span>
                      <p className="text-sm text-[#2A2421]">
                        {details.shipment.carrierName ?? 'Partner Courier'}
                        {details.shipment.trackingCode ? ` · #${details.shipment.trackingCode}` : ''}
                      </p>
                    </div>
                    <span className="text-xs font-medium px-2 py-1 bg-[#EFE3D3] text-[#2C2033] rounded">
                      Tracked
                    </span>
                  </div>
                </div>
              )}
            </dl>
          </div>

          {(cancellable || reschedulable || infoActions.length > 0) && (
            <div className="bg-white border border-[#EFE3D3] rounded-xl p-6 shadow-sm">
              <h2 className="text-lg font-semibold text-[#2C2033] mb-4">Actions</h2>

              {flash && (
                <p className="mb-4 text-sm text-[#2C2033] bg-[#EFE3D3] border border-[#2C2033]/20 rounded-lg px-4 py-3" role="status">
                  {flash}
                </p>
              )}
              {actionError && (
                <p className="mb-4 text-sm text-[#B74717] bg-[#B74717]/10 border border-[#B74717]/30 rounded-lg px-4 py-3" role="alert">
                  {actionError}
                </p>
              )}

              {reschedulable && (
                <div className="mb-4">
                  <label htmlFor="reschedule-datetime" className="block text-sm font-medium text-[#2A2421] mb-1">
                    Reschedule to
                  </label>
                  <div className="flex flex-col sm:flex-row gap-2">
                    <input
                      id="reschedule-datetime"
                      type="datetime-local"
                      value={rescheduleValue}
                      onChange={(e) => setRescheduleValue(e.target.value)}
                      className="flex-1 rounded-lg border border-[#EFE3D3] px-3 py-2 text-sm text-[#2A2421] focus:border-[#F16A24] focus:outline-none"
                    />
                    <button
                      onClick={handleReschedule}
                      disabled={busy !== null}
                      className="rounded-lg bg-[#F16A24] px-4 py-2 text-sm font-medium text-white hover:bg-[#B74717] disabled:opacity-50 transition-colors"
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
                  className="rounded-lg border border-[#B74717] px-4 py-2 text-sm font-medium text-[#B74717] hover:bg-[#B74717]/10 disabled:opacity-50 transition-colors"
                >
                  {busy === 'cancel' ? 'Cancelling…' : 'Cancel booking'}
                </button>
              )}

              {infoActions.length > 0 && (
                <ul className="space-y-2 mt-4">
                  {infoActions.map((a) => (
                    <li key={a} className="text-[#2A2421] flex items-center gap-2">
                      <span className="w-2 h-2 bg-[#F16A24] rounded-full"></span>
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
          <div className="bg-white border border-[#EFE3D3] rounded-xl p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-[#2C2033] mb-4">Status History</h2>
            {history.length === 0 ? (
              <p className="text-[#2A2421]/60 text-center py-8">No history available</p>
            ) : (
              <dl className="space-y-4">
                {history.map((transition, index) => (
                  <div
                    key={`${transition.timestamp}-${index}`}
                    className="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-[#FFF7ED] rounded-lg border border-[#EFE3D3]"
                  >
                    <div className="flex items-center gap-3">
                      <div className="flex-shrink-0 w-10 h-10 rounded-full bg-[#EFE3D3] flex items-center justify-center">
                        <span className="text-[#2C2033] font-bold text-sm">{index + 1}</span>
                      </div>
                      <div>
                        <dt className="text-sm font-medium text-[#2C2033]">{transition.status}</dt>
                        {transition.note && (
                          <dd className="text-sm text-[#2A2421]/70">{transition.note}</dd>
                        )}
                      </div>
                    </div>
                    <dd className="mt-2 sm:mt-0 text-sm text-[#2A2421]/60 whitespace-nowrap">
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
