'use client';

import React, { useState } from 'react';

export interface MuhuratWindow {
  starts_at: string;
  ends_at: string;
  duration_minutes: number;
  reason?: string;
}

export interface CeremonyOption {
  key: string;
  label: string;
  description: string;
}

export const CEREMONY_OPTIONS: CeremonyOption[] = [
  { key: 'VIVAH', label: 'Vivah (Marriage)', description: 'Auspicious marriage lagna windows excluding Rahu Kaal & Bhadra' },
  { key: 'GRIHA_PRAVESH', label: 'Griha Pravesh', description: 'Housewarming ceremonies based on fixed Nakshatras & Tithis' },
  { key: 'NAMAKARANA', label: 'Namakarana', description: 'Vedic child naming ceremony muhurats' },
  { key: 'VEHICLE_PURCHASE', label: 'Vehicle Purchase', description: 'Auspicious purchase timings aligned with Chara & Labha' },
];

export function MuhuratFinder({
  initialWindows = [],
  onSearch,
}: {
  initialWindows?: MuhuratWindow[];
  onSearch?: (ceremonyKey: string, place: string) => void;
}) {
  const [selectedCeremony, setSelectedCeremony] = useState('VIVAH');
  const [selectedPlace, setSelectedPlace] = useState('delhi');
  const [windows, setWindows] = useState<MuhuratWindow[]>(initialWindows);

  const handleSearch = () => {
    if (onSearch) {
      onSearch(selectedCeremony, selectedPlace);
    }
  };

  return (
    <div className="w-full max-w-4xl mx-auto rounded-2xl bg-[#FFF7ED] border border-[#EFE3D3] p-6 shadow-sm">
      <div className="mb-6">
        <h2 className="text-xl sm:text-2xl font-serif font-bold text-[#2C2033]">
          Vedic Muhurat Finder
        </h2>
        <p className="text-sm text-[#2A2421]/70 mt-1">
          Astronomically evaluated auspicious windows with interval algebra constraint resolution.
        </p>
      </div>

      {/* Select Controls */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div>
          <label className="block text-xs font-semibold uppercase text-[#B74717] mb-1">
            Ceremony Type
          </label>
          <select
            value={selectedCeremony}
            onChange={(e) => setSelectedCeremony(e.target.value)}
            className="w-full rounded-xl border border-[#EFE3D3] bg-white p-2.5 text-sm font-semibold text-[#2C2033] focus:outline-none focus:ring-1 focus:ring-[#F16A24]"
          >
            {CEREMONY_OPTIONS.map((opt) => (
              <option key={opt.key} value={opt.key}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs font-semibold uppercase text-[#B74717] mb-1">
            Location
          </label>
          <select
            value={selectedPlace}
            onChange={(e) => setSelectedPlace(e.target.value)}
            className="w-full rounded-xl border border-[#EFE3D3] bg-white p-2.5 text-sm font-semibold text-[#2C2033] focus:outline-none focus:ring-1 focus:ring-[#F16A24]"
          >
            <option value="delhi">New Delhi</option>
            <option value="varanasi">Varanasi</option>
            <option value="ujjain">Ujjain</option>
            <option value="mumbai">Mumbai</option>
            <option value="bengaluru">Bengaluru</option>
          </select>
        </div>

        <div className="flex items-end">
          <button
            type="button"
            onClick={handleSearch}
            className="w-full rounded-xl bg-[#F16A24] text-white py-2.5 px-4 font-semibold text-sm hover:bg-[#B74717] transition-colors shadow-sm"
          >
            Find Auspicious Timings
          </button>
        </div>
      </div>

      {/* Results Section */}
      <div className="border-t border-[#EFE3D3] pt-6">
        <h3 className="text-sm font-semibold uppercase text-[#2C2033] tracking-wider mb-4">
          Auspicious Windows
        </h3>

        {windows.length === 0 ? (
          <div className="p-8 text-center rounded-xl bg-white border border-[#EFE3D3]">
            <p className="text-sm text-[#2A2421]/70">
              Select your ritual requirements and location to evaluate auspicious intervals.
            </p>
          </div>
        ) : (
          <div className="space-y-3">
            {windows.map((w, i) => (
              <div
                key={i}
                className="p-4 rounded-xl bg-white border border-[#EFE3D3] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2"
              >
                <div>
                  <span className="inline-block px-2 py-0.5 text-xs font-bold rounded bg-[#FFF7ED] text-[#F16A24] border border-[#F16A24]/30 mb-1">
                    {w.duration_minutes} Minutes
                  </span>
                  <p className="text-base font-bold text-[#2C2033]">
                    {w.starts_at} – {w.ends_at}
                  </p>
                  {w.reason && (
                    <p className="text-xs text-[#2A2421]/70 mt-0.5">{w.reason}</p>
                  )}
                </div>
                <span className="text-xs font-semibold text-[#B74717]">
                  Rahu Kaal & Bhadra Cleared ✓
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

