'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { Compass, Calendar, Clock, MapPin, Loader2 } from 'lucide-react';
import { NorthIndianChart, SouthIndianChart, EastIndianChart, RashiWheel } from '@navdhya/design-system';

interface PlanetPosition {
  rashi: number;
  longitude?: number;
  is_retrograde: boolean;
}

export default function KundliPage() {
  const [dob, setDob] = useState('1990-01-15');
  const [tob, setTob] = useState('12:00');
  const [place, setPlace] = useState('New Delhi');
  const [style, setStyle] = useState<'north' | 'south' | 'east' | 'wheel'>('north');
  const [chartGenerated, setChartGenerated] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [ascendantRashi, setAscendantRashi] = useState(1);
  const [ascendantDeg, setAscendantDeg] = useState(15.0);
  const [planets, setPlanets] = useState<Record<string, PlanetPosition>>({
    Sun: { rashi: 10, longitude: 295, is_retrograde: false },
    Moon: { rashi: 5, longitude: 154, is_retrograde: false },
    Mars: { rashi: 8, longitude: 242, is_retrograde: false },
    Mercury: { rashi: 9, longitude: 278, is_retrograde: true },
    Jupiter: { rashi: 4, longitude: 125, is_retrograde: true },
    Venus: { rashi: 11, longitude: 334, is_retrograde: false },
    Saturn: { rashi: 10, longitude: 308, is_retrograde: false },
    Rahu: { rashi: 11, longitude: 351, is_retrograde: true },
    Ketu: { rashi: 5, longitude: 171, is_retrograde: true },
  });

  const handleCompute = async () => {
    setLoading(true);
    setError(null);

    try {
      const res = await fetch('/api/jyotish/chart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          birth_date: dob,
          birth_time: tob,
          birth_place: place,
          latitude: 28.6139,
          longitude: 77.2090,
          timezone: 'Asia/Kolkata',
        }),
      });

      const json = await res.json();
      if (json.status === 'success' && json.data) {
        const data = json.data;
        if (data.ascendant) {
          setAscendantRashi(data.ascendant.rashi ?? 1);
          setAscendantDeg(data.ascendant.longitude ?? 15.0);
        }

        if (data.planets) {
          const mapped: Record<string, PlanetPosition> = {};
          for (const [name, p] of Object.entries(data.planets)) {
            const planetObj = p as { rashi?: number; longitude?: number; is_retrograde?: boolean };
            mapped[name] = {
              rashi: planetObj.rashi ?? 1,
              longitude: planetObj.longitude,
              is_retrograde: !!planetObj.is_retrograde,
            };
          }
          setPlanets(mapped);
        }
      }
      setChartGenerated(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Calculation error');
      setChartGenerated(true);
    } finally {
      setLoading(false);
    }
  };

  const wheelPlanets = Object.entries(planets).reduce((acc, [name, p]) => {
    acc[name] = { longitude: p.longitude ?? p.rashi * 30 };
    return acc;
  }, {} as Record<string, { longitude: number }>);

  return (
    <div className="min-h-screen bg-[#FFF7ED] text-[#2A2421] py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto space-y-8">
        <div className="space-y-2">
          <div className="flex items-center space-x-2 text-sm text-[#B74717]">
            <Link href="/" className="hover:underline">Home</Link>
            <span>/</span>
            <Link href="/resources/tools" className="hover:underline">Tools</Link>
            <span>/</span>
            <span className="text-[#2C2033] font-medium">Kundli Generator</span>
          </div>
          <h1 className="text-3xl sm:text-4xl font-serif font-bold text-[#2C2033]">
            Devotee Birth Chart (Janma Kundli)
          </h1>
          <p className="text-base text-[#2A2421]/80">
            Generate your Vedic horoscope with Shodasha Varga harmonics and accurate sidereal planetary degrees.
          </p>
        </div>

        {/* Input Form */}
        <div className="p-6 rounded-2xl bg-white border border-[#2C2033]/10 shadow-sm space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#B74717] mb-1">
                DATE OF BIRTH
              </label>
              <input
                type="date"
                value={dob}
                onChange={(e) => setDob(e.target.value)}
                className="w-full bg-[#FFF7ED] border border-[#2C2033]/20 rounded-lg px-3 py-2 text-sm text-[#2A2421] focus:ring-2 focus:ring-[#F16A24]"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-[#B74717] mb-1">
                TIME OF BIRTH
              </label>
              <input
                type="time"
                value={tob}
                onChange={(e) => setTob(e.target.value)}
                className="w-full bg-[#FFF7ED] border border-[#2C2033]/20 rounded-lg px-3 py-2 text-sm text-[#2A2421] focus:ring-2 focus:ring-[#F16A24]"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-[#B74717] mb-1">
                PLACE OF BIRTH
              </label>
              <input
                type="text"
                value={place}
                onChange={(e) => setPlace(e.target.value)}
                placeholder="City, Country"
                className="w-full bg-[#FFF7ED] border border-[#2C2033]/20 rounded-lg px-3 py-2 text-sm text-[#2A2421] focus:ring-2 focus:ring-[#F16A24]"
              />
            </div>
          </div>

          {error && (
            <p className="text-xs text-[#B74717] font-medium">{error}</p>
          )}

          <button
            onClick={handleCompute}
            disabled={loading}
            className="w-full py-2.5 rounded-lg bg-[#F16A24] text-white font-medium hover:bg-[#B74717] transition-colors flex items-center justify-center space-x-2"
          >
            {loading ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                <span>Computing Canonical Ephemeris...</span>
              </>
            ) : (
              <span>Compute Vedic Chart</span>
            )}
          </button>
        </div>

        {chartGenerated && (
          <div className="space-y-6">
            {/* Style Selector Tabs */}
            <div className="flex items-center space-x-2 border-b border-[#2C2033]/10 pb-2">
              <button
                onClick={() => setStyle('north')}
                className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                  style === 'north'
                    ? 'bg-[#2C2033] text-white'
                    : 'bg-[#EFE3D3]/50 text-[#2C2033] hover:bg-[#EFE3D3]'
                }`}
              >
                North Indian
              </button>
              <button
                onClick={() => setStyle('south')}
                className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                  style === 'south'
                    ? 'bg-[#2C2033] text-white'
                    : 'bg-[#EFE3D3]/50 text-[#2C2033] hover:bg-[#EFE3D3]'
                }`}
              >
                South Indian
              </button>
              <button
                onClick={() => setStyle('east')}
                className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                  style === 'east'
                    ? 'bg-[#2C2033] text-white'
                    : 'bg-[#EFE3D3]/50 text-[#2C2033] hover:bg-[#EFE3D3]'
                }`}
              >
                East Indian
              </button>
              <button
                onClick={() => setStyle('wheel')}
                className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                  style === 'wheel'
                    ? 'bg-[#2C2033] text-white'
                    : 'bg-[#EFE3D3]/50 text-[#2C2033] hover:bg-[#EFE3D3]'
                }`}
              >
                Rashi Wheel
              </button>
            </div>

            {/* SVG Visualizer Container */}
            <div className="p-8 rounded-2xl bg-white border border-[#2C2033]/10 shadow-sm flex flex-col items-center justify-center">
              {style === 'north' && <NorthIndianChart ascendantRashi={ascendantRashi} planets={planets} />}
              {style === 'south' && <SouthIndianChart ascendantRashi={ascendantRashi} planets={planets} />}
              {style === 'east' && <EastIndianChart ascendantRashi={ascendantRashi} planets={planets} />}
              {style === 'wheel' && (
                <RashiWheel
                  ascendantDegree={ascendantDeg}
                  planets={wheelPlanets}
                />
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
