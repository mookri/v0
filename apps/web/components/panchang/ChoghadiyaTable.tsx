'use client';

import React from 'react';

export interface ChoghadiyaSlot {
  slot: number;
  name: string;
  starts_at: string;
  ends_at: string;
  nature: string;
  ruler?: string;
}

export function ChoghadiyaTable({
  daySlots,
  nightSlots,
  date,
  placeName,
}: {
  daySlots: ChoghadiyaSlot[];
  nightSlots?: ChoghadiyaSlot[];
  date: string;
  placeName: string;
}) {
  const getBadgeStyle = (nature: string) => {
    switch (nature) {
      case 'HIGHLY_AUSPICIOUS':
        return 'bg-[#F16A24] text-white font-bold';
      case 'AUSPICIOUS':
        return 'bg-[#FFF7ED] text-[#F16A24] border border-[#F16A24]/40 font-semibold';
      case 'NEUTRAL':
        return 'bg-[#EFE3D3] text-[#2C2033] font-medium';
      default: // INAUSPICIOUS
        return 'bg-[#2C2033]/10 text-[#2C2033]/80 font-medium';
    }
  };

  return (
    <div className="w-full max-w-4xl mx-auto rounded-2xl bg-[#FFF7ED] border border-[#EFE3D3] p-6 shadow-sm">
      <div className="mb-6">
        <h2 className="text-xl sm:text-2xl font-serif font-bold text-[#2C2033]">
          Choghadiya Timings · {placeName}
        </h2>
        <p className="text-sm text-[#2A2421]/70">{date}</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Daytime Choghadiya */}
        <div className="rounded-xl bg-white border border-[#EFE3D3] p-4">
          <h3 className="text-sm font-semibold tracking-wider text-[#2C2033] uppercase mb-3 flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-[#F16A24]" />
            Day Choghadiya
          </h3>
          <div className="space-y-2">
            {daySlots.map((slot) => (
              <div
                key={slot.slot}
                className="flex justify-between items-center py-2 px-3 rounded-lg bg-[#FFF7ED]/50 border border-[#EFE3D3]/60 text-xs"
              >
                <div>
                  <span className="font-bold text-[#2C2033]">{slot.name}</span>
                  <span className="block text-[#2A2421]/70 mt-0.5">
                    {slot.starts_at} – {slot.ends_at}
                  </span>
                </div>
                <span className={`px-2 py-0.5 rounded text-[10px] ${getBadgeStyle(slot.nature)}`}>
                  {slot.nature.replace('_', ' ')}
                </span>
              </div>
            ))}
          </div>
        </div>

        {/* Nighttime Choghadiya (if provided) */}
        {nightSlots && nightSlots.length > 0 && (
          <div className="rounded-xl bg-white border border-[#EFE3D3] p-4">
            <h3 className="text-sm font-semibold tracking-wider text-[#2C2033] uppercase mb-3 flex items-center gap-2">
              <span className="w-2 h-2 rounded-full bg-[#2C2033]" />
              Night Choghadiya
            </h3>
            <div className="space-y-2">
              {nightSlots.map((slot) => (
                <div
                  key={slot.slot}
                  className="flex justify-between items-center py-2 px-3 rounded-lg bg-[#FFF7ED]/50 border border-[#EFE3D3]/60 text-xs"
                >
                  <div>
                    <span className="font-bold text-[#2C2033]">{slot.name}</span>
                    <span className="block text-[#2A2421]/70 mt-0.5">
                      {slot.starts_at} – {slot.ends_at}
                    </span>
                  </div>
                  <span className={`px-2 py-0.5 rounded text-[10px] ${getBadgeStyle(slot.nature)}`}>
                    {slot.nature.replace('_', ' ')}
                  </span>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

