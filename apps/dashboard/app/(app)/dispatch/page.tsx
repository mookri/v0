'use client';

import React, { useEffect, useState } from 'react';

interface DispatchAssignment {
  id: string;
  booking_id: string;
  pandit_id: string;
  status: string;
  assigned_at?: string | null;
  service_started_at?: string | null;
}

interface UnassignedBooking {
  id: string;
  status: string;
  slot_starts_at?: string | null;
  total_minor: number;
  currency: string;
}

export default function DispatchPage() {
  const [assignments, setAssignments] = useState<DispatchAssignment[]>([]);
  const [unassigned, setUnassigned] = useState<UnassignedBooking[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    fetch('/api/ops/dispatch', { cache: 'no-store' })
      .then(async (res) => {
        const body = (await res.json().catch(() => ({}))) as {
          data?: { active_assignments: DispatchAssignment[]; unassigned_bookings: UnassignedBooking[] };
          message?: string;
        };
        if (!active) return;
        if (!res.ok) {
          setError(body.message || `Dispatch feed unavailable (${res.status})`);
          setLoading(false);
          return;
        }
        setAssignments(body.data?.active_assignments ?? []);
        setUnassigned(body.data?.unassigned_bookings ?? []);
        setLoading(false);
      })
      .catch(() => {
        if (!active) return;
        setError('Unable to load dispatch feed.');
        setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);

  return (
    <div className="space-y-6 text-[#2A2421]">
      <header className="border-b border-[#EFE3D3] pb-4">
        <p className="text-xs font-semibold uppercase tracking-widest text-[#B74717]">Ceremony Operations</p>
        <h1 className="text-2xl font-bold text-[#2C2033]">Temple Dispatcher Console</h1>
      </header>

      {error ? (
        <div role="alert" className="rounded-xl border border-[#B74717] bg-[#EFE3D3] px-4 py-3 text-sm text-[#B74717]">
          {error}
        </div>
      ) : null}

      {loading ? (
        <p className="text-sm">Loading dispatch queue...</p>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <section className="rounded-xl border border-[#EFE3D3] bg-white p-5 space-y-3">
            <h2 className="text-base font-bold text-[#2C2033]">Active Assignments ({assignments.length})</h2>
            {assignments.length === 0 ? (
              <p className="text-sm">No active assignments.</p>
            ) : (
              <ul className="divide-y divide-[#EFE3D3] text-sm">
                {assignments.map((a) => (
                  <li key={a.id} className="py-2.5 flex items-center justify-between">
                    <div>
                      <p className="font-mono text-xs text-[#2C2033]">Booking {a.booking_id}</p>
                      <p className="text-xs">Pandit {a.pandit_id}</p>
                    </div>
                    <span className="rounded-full bg-[#EFE3D3] px-2.5 py-0.5 text-xs font-semibold text-[#2C2033]">
                      {a.status}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="rounded-xl border border-[#EFE3D3] bg-white p-5 space-y-3">
            <h2 className="text-base font-bold text-[#2C2033]">Unassigned Confirmed Bookings ({unassigned.length})</h2>
            {unassigned.length === 0 ? (
              <p className="text-sm">All confirmed bookings currently have an assigned pandit.</p>
            ) : (
              <ul className="divide-y divide-[#EFE3D3] text-sm">
                {unassigned.map((b) => (
                  <li key={b.id} className="py-2.5 flex items-center justify-between">
                    <span className="font-mono text-xs text-[#2C2033]">{b.id}</span>
                    <span className="text-xs font-semibold text-[#B74717]">Needs Assignment</span>
                  </li>
                ))}
              </ul>
            )}
          </section>
        </div>
      )}
    </div>
  );
}

