'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Calendar, Clock, MapPin, Sun, Moon, ArrowRight, ShieldCheck, Compass } from 'lucide-react';

interface PanchangData {
  date: string;
  location: {
    latitude: number;
    longitude: number;
    timezone: string;
  };
  limbs: {
    tithi: { number: number; name: string; paksha: string; transits_at?: string };
    vara: { number: number; name: string };
    nakshatra: { number: number; name: string; pada: number; transits_at?: string };
    yoga: { number: number; name: string; transits_at?: string };
    karana: { number: number; name: string; transits_at?: string };
  };
  solar_horizon: {
    sunrise: string;
    sunset: string;
    solar_noon: string;
    day_length_seconds: number;
  };
  inauspicious_windows: {
    rahu_kaal: { start: string; end: string };
    yamaghanta: { start: string; end: string };
    gulika_kaal: { start: string; end: string };
  };
  day_choghadiya: Array<{
    period: number;
    name: string;
    nature: string;
    is_auspicious: boolean;
    start: string;
    end: string;
  }>;
  observances: Array<{
    code: string;
    name: string;
    priority: number;
  }>;
}

export default function PanchangPage() {
  const [data, setData] = useState<PanchangData | null>(null);
  const [selectedDate, setSelectedDate] = useState<string>(
    new Date().toISOString().split('T')[0]
  );
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    setLoading(true);
    fetch(`/api/panchang?date=${selectedDate}&latitude=28.6139&longitude=77.2090&timezone=Asia/Kolkata`)
      .then((res) => res.json())
      .then((res) => {
        if (res.data) setData(res.data);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, [selectedDate]);

  return (
    <div className="min-h-screen bg-[#FFF7ED] text-[#2A2421] py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto space-y-8">
        {/* Header Breadcrumbs & Title */}
        <div className="space-y-2">
          <div className="flex items-center space-x-2 text-sm text-[#B74717]">
            <Link href="/" className="hover:underline">Home</Link>
            <span>/</span>
            <Link href="/resources/tools" className="hover:underline">Tools</Link>
            <span>/</span>
            <span className="text-[#2C2033] font-medium">Daily Panchang</span>
          </div>
          <h1 className="text-3xl sm:text-4xl font-serif font-bold text-[#2C2033]">
            Vedic Panchang & Daily Ephemeris
          </h1>
          <p className="text-base text-[#2A2421]/80">
            High-precision five-limb calculations rooted in classical Parashari principles.
          </p>
        </div>

        {/* Controls Bar */}
        <div className="p-4 rounded-xl bg-[#EFE3D3]/40 border border-[#2C2033]/15 flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center space-x-3">
            <Calendar className="w-5 h-5 text-[#F16A24]" />
            <input
              type="date"
              value={selectedDate}
              onChange={(e) => setSelectedDate(e.target.value)}
              className="bg-[#FFF7ED] border border-[#2C2033]/20 rounded-md px-3 py-1.5 text-sm text-[#2A2421] focus:outline-none focus:ring-2 focus:ring-[#F16A24]"
            />
          </div>
          <div className="flex items-center space-x-2 text-sm text-[#2C2033]">
            <MapPin className="w-4 h-4 text-[#F16A24]" />
            <span>New Delhi, India (28.61°N, 77.21°E)</span>
          </div>
        </div>

        {loading ? (
          <div className="p-12 text-center text-[#2C2033]/60 font-medium animate-pulse">
            Calculating Panchang Limbs...
          </div>
        ) : data ? (
          <div className="space-y-6">
            {/* Solar Horizon Banner */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div className="p-4 rounded-xl bg-white border border-[#2C2033]/10 shadow-sm flex items-center space-x-3">
                <Sun className="w-8 h-8 text-[#F16A24]" />
                <div>
                  <div className="text-xs uppercase tracking-wide text-[#B74717]">Sunrise</div>
                  <div className="text-lg font-bold text-[#2C2033]">{data.solar_horizon.sunrise.slice(11, 16)} IST</div>
                </div>
              </div>
              <div className="p-4 rounded-xl bg-white border border-[#2C2033]/10 shadow-sm flex items-center space-x-3">
                <Moon className="w-8 h-8 text-[#2C2033]" />
                <div>
                  <div className="text-xs uppercase tracking-wide text-[#B74717]">Sunset</div>
                  <div className="text-lg font-bold text-[#2C2033]">{data.solar_horizon.sunset.slice(11, 16)} IST</div>
                </div>
              </div>
              <div className="p-4 rounded-xl bg-white border border-[#2C2033]/10 shadow-sm flex items-center space-x-3">
                <Compass className="w-8 h-8 text-[#B74717]" />
                <div>
                  <div className="text-xs uppercase tracking-wide text-[#B74717]">Solar Noon</div>
                  <div className="text-lg font-bold text-[#2C2033]">{data.solar_horizon.solar_noon.slice(11, 16)} IST</div>
                </div>
              </div>
            </div>

            {/* Five Limbs Grid */}
            <div className="p-6 rounded-2xl bg-white border border-[#2C2033]/10 shadow-sm space-y-4">
              <h2 className="text-xl font-serif font-bold text-[#2C2033] border-b border-[#2C2033]/10 pb-3">
                The Five Limbs (Pancha-Anga)
              </h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div className="p-3 rounded-lg bg-[#FFF7ED] border border-[#2C2033]/10">
                  <div className="text-xs text-[#B74717] font-semibold">TITHI (LUNAR DAY)</div>
                  <div className="text-lg font-bold text-[#2C2033]">{data.limbs.tithi.name}</div>
                  <div className="text-xs text-[#2A2421]/70">{data.limbs.tithi.paksha} Paksha</div>
                </div>
                <div className="p-3 rounded-lg bg-[#FFF7ED] border border-[#2C2033]/10">
                  <div className="text-xs text-[#B74717] font-semibold">VARA (SOLAR DAY)</div>
                  <div className="text-lg font-bold text-[#2C2033]">{data.limbs.vara.name}</div>
                  <div className="text-xs text-[#2A2421]/70">Day #{data.limbs.vara.number}</div>
                </div>
                <div className="p-3 rounded-lg bg-[#FFF7ED] border border-[#2C2033]/10">
                  <div className="text-xs text-[#B74717] font-semibold">NAKSHATRA (LUNAR MANSION)</div>
                  <div className="text-lg font-bold text-[#2C2033]">{data.limbs.nakshatra.name}</div>
                  <div className="text-xs text-[#2A2421]/70">Pada {data.limbs.nakshatra.pada}</div>
                </div>
                <div className="p-3 rounded-lg bg-[#FFF7ED] border border-[#2C2033]/10">
                  <div className="text-xs text-[#B74717] font-semibold">YOGA (SOLI-LUNAR ANGLE)</div>
                  <div className="text-lg font-bold text-[#2C2033]">{data.limbs.yoga.name}</div>
                  <div className="text-xs text-[#2A2421]/70">Yoga #{data.limbs.yoga.number}</div>
                </div>
                <div className="p-3 rounded-lg bg-[#FFF7ED] border border-[#2C2033]/10">
                  <div className="text-xs text-[#B74717] font-semibold">KARANA (HALF-TITHI)</div>
                  <div className="text-lg font-bold text-[#2C2033]">{data.limbs.karana.name}</div>
                  <div className="text-xs text-[#2A2421]/70">Karana #{data.limbs.karana.number}</div>
                </div>
              </div>
            </div>

            {/* Inauspicious Windows */}
            <div className="p-6 rounded-2xl bg-white border border-[#2C2033]/10 shadow-sm space-y-4">
              <h2 className="text-xl font-serif font-bold text-[#2C2033] border-b border-[#2C2033]/10 pb-3">
                Restricted Timings (Ashubh Kaal)
              </h2>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="p-4 rounded-xl bg-[#2C2033]/5 border border-[#2C2033]/15">
                  <div className="text-xs font-semibold text-[#B74717]">RAHU KAAL</div>
                  <div className="text-base font-bold text-[#2C2033]">
                    {data.inauspicious_windows.rahu_kaal.start.slice(11, 16)} – {data.inauspicious_windows.rahu_kaal.end.slice(11, 16)}
                  </div>
                  <div className="text-xs text-[#2A2421]/70 mt-1">Inauspicious for new beginnings</div>
                </div>
                <div className="p-4 rounded-xl bg-[#2C2033]/5 border border-[#2C2033]/15">
                  <div className="text-xs font-semibold text-[#B74717]">YAMAGHANTA</div>
                  <div className="text-base font-bold text-[#2C2033]">
                    {data.inauspicious_windows.yamaghanta.start.slice(11, 16)} – {data.inauspicious_windows.yamaghanta.end.slice(11, 16)}
                  </div>
                  <div className="text-xs text-[#2A2421]/70 mt-1">Avoid journeys & travel</div>
                </div>
                <div className="p-4 rounded-xl bg-[#2C2033]/5 border border-[#2C2033]/15">
                  <div className="text-xs font-semibold text-[#B74717]">GULIKA KAAL</div>
                  <div className="text-base font-bold text-[#2C2033]">
                    {data.inauspicious_windows.gulika_kaal.start.slice(11, 16)} – {data.inauspicious_windows.gulika_kaal.end.slice(11, 16)}
                  </div>
                  <div className="text-xs text-[#2A2421]/70 mt-1">Repeat-action window</div>
                </div>
              </div>
            </div>

            {/* Choghadiya Table */}
            <div className="p-6 rounded-2xl bg-white border border-[#2C2033]/10 shadow-sm space-y-4">
              <h2 className="text-xl font-serif font-bold text-[#2C2033] border-b border-[#2C2033]/10 pb-3">
                Day Choghadiya
              </h2>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {data.day_choghadiya.map((ch) => (
                  <div
                    key={ch.period}
                    className={`p-3 rounded-lg border text-center ${
                      ch.is_auspicious
                        ? 'bg-[#EFE3D3]/30 border-[#F16A24]/40'
                        : 'bg-[#2C2033]/5 border-[#2C2033]/15'
                    }`}
                  >
                    <div className="text-xs text-[#B74717]">{ch.start} - {ch.end}</div>
                    <div className="text-base font-bold text-[#2C2033]">{ch.name}</div>
                    <div className="text-xs mt-1 font-medium" style={{ color: ch.is_auspicious ? '#B74717' : '#2A2421' }}>
                      {ch.is_auspicious ? 'Auspicious' : 'Inauspicious'}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}

