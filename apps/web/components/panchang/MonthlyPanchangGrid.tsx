'use client';

import React from 'react';

export interface CalendarDaySummary {
  day_number: number;
  date_iso: string;
  tithi_name: string;
  paksha: string;
  nakshatra_name: string;
  festival?: string;
  is_today?: boolean;
}

export function MonthlyPanchangGrid({
  monthName,
  year,
  placeName,
  days,
  startWeekday = 0, // 0 = Sunday
}: {
  monthName: string;
  year: number;
  placeName: string;
  days: CalendarDaySummary[];
  startWeekday?: number;
}) {
  const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  return (
    <div className="w-full max-w-5xl mx-auto rounded-2xl bg-[#FFF7ED] border border-[#EFE3D3] p-6 shadow-sm">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h2 className="text-xl sm:text-2xl font-serif font-bold text-[#2C2033]">
            {monthName} {year} · Panchang
          </h2>
          <p className="text-sm text-[#2A2421]/70">{placeName}</p>
        </div>
      </div>

      {/* Weekday Headers */}
      <div className="grid grid-cols-7 gap-2 mb-2 text-center">
        {weekdays.map((w, idx) => (
          <div
            key={w}
            className={`text-xs font-semibold uppercase tracking-wider py-1 ${
              idx === 0 ? 'text-[#B74717]' : 'text-[#2C2033]'
            }`}
          >
            {w}
          </div>
        ))}
      </div>

      {/* Grid Days */}
      <div className="grid grid-cols-7 gap-2">
        {/* Leading blanks for month start weekday offset */}
        {Array.from({ length: startWeekday }).map((_, i) => (
          <div key={`blank-${i}`} className="min-h-[90px] rounded-xl bg-white/30 border border-transparent" />
        ))}

        {days.map((d) => (
          <div
            key={d.date_iso}
            className={`min-h-[90px] p-2.5 rounded-xl border flex flex-col justify-between transition-colors ${
              d.is_today
                ? 'bg-white border-[#F16A24] ring-1 ring-[#F16A24]'
                : 'bg-white border-[#EFE3D3] hover:border-[#F16A24]/50'
            }`}
          >
            <div className="flex justify-between items-start">
              <span
                className={`text-sm font-bold ${
                  d.is_today ? 'text-[#F16A24]' : 'text-[#2C2033]'
                }`}
              >
                {d.day_number}
              </span>
              <span className="text-[10px] uppercase font-semibold text-[#B74717]">
                {d.paksha[0]}
              </span>
            </div>

            <div className="my-1">
              <p className="text-[11px] font-semibold text-[#2C2033] truncate">
                {d.tithi_name}
              </p>
              <p className="text-[10px] text-[#2A2421]/70 truncate">
                {d.nakshatra_name}
              </p>
            </div>

            {d.festival ? (
              <span className="block truncate text-[9px] font-bold px-1.5 py-0.5 rounded bg-[#F16A24] text-white">
                {d.festival}
              </span>
            ) : (
              <div className="h-3" />
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

