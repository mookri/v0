'use client';

import React from 'react';

export interface PanchangLimb {
  number: number;
  name: string;
  ends_at_formatted?: string;
  pada?: number;
  paksha?: string;
}

export interface DayPeriod {
  name: string;
  starts_at: string;
  ends_at: string;
}

export interface DailyPanchangData {
  date: string;
  place_name: string;
  sunrise: string;
  sunset: string;
  solar_noon: string;
  tithi: PanchangLimb;
  nakshatra: PanchangLimb;
  yoga: PanchangLimb;
  karana: PanchangLimb;
  vara: {
    civil_name: string;
    vedic_name: string;
    is_pre_sunrise?: boolean;
  };
  masa_amanta: string;
  masa_purnimanta: string;
  is_adhika: boolean;
  moon_phase: {
    phase_name: string;
    illumination_percentage: number;
  };
  abhijit_muhurat?: DayPeriod;
  rahu_kaal?: DayPeriod;
  yamaganda?: DayPeriod;
  gulika?: DayPeriod;
}

export function DailyPanchangCard({ data }: { data: DailyPanchangData }) {
  return (
    <div className="w-full max-w-4xl mx-auto rounded-2xl bg-[#FFF7ED] border border-[#EFE3D3] p-6 shadow-sm text-[#2A2421]">
      {/* Header Banner */}
      <div className="border-b border-[#EFE3D3] pb-4 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
        <div>
          <div className="flex items-center gap-2">
            <span className="inline-block w-2.5 h-2.5 rounded-full bg-[#F16A24]" />
            <h2 className="text-xl sm:text-2xl font-serif font-bold text-[#2C2033]">
              Daily Panchang · {data.place_name}
            </h2>
          </div>
          <p className="text-sm text-[#2A2421]/80 mt-1">
            {data.date} · {data.vara.vedic_name} (Vedic) / {data.vara.civil_name} (Civil)
          </p>
        </div>
        <div className="text-left sm:text-right">
          <span className="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-[#EFE3D3] text-[#2C2033]">
            {data.is_adhika ? 'ADHIKA ' : ''}{data.masa_purnimanta} (Purnimanta) / {data.masa_amanta} (Amanta)
          </span>
        </div>
      </div>

      {/* Sun & Moon Horology */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div className="p-3 rounded-xl bg-white/70 border border-[#EFE3D3]">
          <p className="text-xs uppercase tracking-wider text-[#B74717] font-semibold">Sunrise</p>
          <p className="text-base sm:text-lg font-bold text-[#2C2033] mt-0.5">{data.sunrise}</p>
        </div>
        <div className="p-3 rounded-xl bg-white/70 border border-[#EFE3D3]">
          <p className="text-xs uppercase tracking-wider text-[#B74717] font-semibold">Sunset</p>
          <p className="text-base sm:text-lg font-bold text-[#2C2033] mt-0.5">{data.sunset}</p>
        </div>
        <div className="p-3 rounded-xl bg-white/70 border border-[#EFE3D3]">
          <p className="text-xs uppercase tracking-wider text-[#B74717] font-semibold">Solar Noon</p>
          <p className="text-base sm:text-lg font-bold text-[#2C2033] mt-0.5">{data.solar_noon}</p>
        </div>
        <div className="p-3 rounded-xl bg-white/70 border border-[#EFE3D3]">
          <p className="text-xs uppercase tracking-wider text-[#B74717] font-semibold">Moon Phase</p>
          <p className="text-base sm:text-lg font-bold text-[#2C2033] mt-0.5">
            {data.moon_phase.illumination_percentage}% ({data.moon_phase.phase_name})
          </p>
        </div>
      </div>

      {/* The 5 Limbs (Pancha-Anga) */}
      <div className="mb-6">
        <h3 className="text-sm font-semibold tracking-wider text-[#2C2033] uppercase mb-3">
          The Five Limbs (Pancha-Anga)
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          {/* 1. Tithi */}
          <div className="p-4 rounded-xl bg-white border border-[#EFE3D3]">
            <p className="text-xs text-[#B74717] font-semibold">1. TITHI</p>
            <p className="text-lg font-bold text-[#2C2033] mt-1">{data.tithi.name}</p>
            <p className="text-xs text-[#2A2421]/70 mt-1">
              {data.tithi.paksha} Paksha · #{data.tithi.number}
              {data.tithi.ends_at_formatted ? ` · Until ${data.tithi.ends_at_formatted}` : ''}
            </p>
          </div>

          {/* 2. Nakshatra */}
          <div className="p-4 rounded-xl bg-white border border-[#EFE3D3]">
            <p className="text-xs text-[#B74717] font-semibold">2. NAKSHATRA</p>
            <p className="text-lg font-bold text-[#2C2033] mt-1">{data.nakshatra.name}</p>
            <p className="text-xs text-[#2A2421]/70 mt-1">
              Pada {data.nakshatra.pada ?? 1} · #{data.nakshatra.number}
              {data.nakshatra.ends_at_formatted ? ` · Until ${data.nakshatra.ends_at_formatted}` : ''}
            </p>
          </div>

          {/* 3. Yoga */}
          <div className="p-4 rounded-xl bg-white border border-[#EFE3D3]">
            <p className="text-xs text-[#B74717] font-semibold">3. YOGA</p>
            <p className="text-lg font-bold text-[#2C2033] mt-1">{data.yoga.name}</p>
            <p className="text-xs text-[#2A2421]/70 mt-1">Nitya Yoga #{data.yoga.number}</p>
          </div>

          {/* 4. Karana */}
          <div className="p-4 rounded-xl bg-white border border-[#EFE3D3]">
            <p className="text-xs text-[#B74717] font-semibold">4. KARANA</p>
            <p className="text-lg font-bold text-[#2C2033] mt-1">{data.karana.name}</p>
            <p className="text-xs text-[#2A2421]/70 mt-1">Half-Tithi #{data.karana.number}</p>
          </div>

          {/* 5. Vara */}
          <div className="p-4 rounded-xl bg-white border border-[#EFE3D3]">
            <p className="text-xs text-[#B74717] font-semibold">5. VARA</p>
            <p className="text-lg font-bold text-[#2C2033] mt-1">{data.vara.vedic_name}</p>
            <p className="text-xs text-[#2A2421]/70 mt-1">Solar Day Lord</p>
          </div>
        </div>
      </div>

      {/* Auspicious & Inauspicious Timings */}
      <div className="border-t border-[#EFE3D3] pt-5">
        <h3 className="text-sm font-semibold tracking-wider text-[#2C2033] uppercase mb-3">
          Key Muhurat Periods
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          {data.abhijit_muhurat && (
            <div className="p-3 rounded-xl bg-[#FFF7ED] border-2 border-[#F16A24]/40">
              <span className="text-xs font-bold text-[#F16A24]">★ ABHIJIT MUHURAT</span>
              <p className="text-sm font-bold text-[#2C2033] mt-1">
                {data.abhijit_muhurat.starts_at} – {data.abhijit_muhurat.ends_at}
              </p>
            </div>
          )}

          {data.rahu_kaal && (
            <div className="p-3 rounded-xl bg-[#2C2033]/5 border border-[#2C2033]/20">
              <span className="text-xs font-semibold text-[#B74717]">✕ RAHU KAAL</span>
              <p className="text-sm font-bold text-[#2C2033] mt-1">
                {data.rahu_kaal.starts_at} – {data.rahu_kaal.ends_at}
              </p>
            </div>
          )}

          {data.yamaganda && (
            <div className="p-3 rounded-xl bg-[#2C2033]/5 border border-[#2C2033]/20">
              <span className="text-xs font-semibold text-[#2C2033]/80">YAMAGANDA</span>
              <p className="text-sm font-bold text-[#2C2033] mt-1">
                {data.yamaganda.starts_at} – {data.yamaganda.ends_at}
              </p>
            </div>
          )}

          {data.gulika && (
            <div className="p-3 rounded-xl bg-[#2C2033]/5 border border-[#2C2033]/20">
              <span className="text-xs font-semibold text-[#2C2033]/80">GULIKA KAAL</span>
              <p className="text-sm font-bold text-[#2C2033] mt-1">
                {data.gulika.starts_at} – {data.gulika.ends_at}
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

