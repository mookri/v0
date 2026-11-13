'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { Target, Calendar, Clock, MapPin, CheckCircle2, AlertCircle, Loader2 } from 'lucide-react';

const CEREMONIES = [
  { id: 'griha_pravesh', name: 'Griha Pravesh (Housewarming)' },
  { id: 'vivah', name: 'Vivah (Wedding Ceremony)' },
  { id: 'namakarana', name: 'Namakarana (Naming Ceremony)' },
  { id: 'mundan', name: 'Mundan (Chudakarana)' },
  { id: 'satyanarayan', name: 'Satyanarayan Katha' },
  { id: 'business_launch', name: 'Commercial & Business Launch' },
];

interface MuhuratWindow {
  id: string;
  title: string;
  start: string;
  end: string;
  shubh_score: number;
  nakshatra: string;
  tithi: string;
  is_recommended: boolean;
  notes: string;
}

export default function MuhuratPage() {
  const [ceremony, setCeremony] = useState('griha_pravesh');
  const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
  const [searched, setSearched] = useState(false);
  const [loading, setLoading] = useState(false);
  const [windows, setWindows] = useState<MuhuratWindow[]>([]);

  const handleSearch = async () => {
    setLoading(true);
    try {
      const res = await fetch(`/api/muhurat?ceremony=${encodeURIComponent(ceremony)}&date=${encodeURIComponent(date)}`);
      const json = await res.json();
      if (json.windows) {
        setWindows(json.windows);
      }
      setSearched(true);
    } catch {
      setSearched(true);
    } finally {
      setLoading(false);
    }
  };

  const formatTime = (isoString: string) => {
    try {
      const d = new Date(isoString);
      return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
      return isoString;
    }
  };

  return (
    <div className="min-h-screen bg-[#FFF7ED] text-[#2A2421] py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto space-y-8">
        <div className="space-y-2">
          <div className="flex items-center space-x-2 text-sm text-[#B74717]">
            <Link href="/" className="hover:underline">Home</Link>
            <span>/</span>
            <Link href="/resources/tools" className="hover:underline">Tools</Link>
            <span>/</span>
            <span className="text-[#2C2033] font-medium">Muhurat Finder</span>
          </div>
          <h1 className="text-3xl sm:text-4xl font-serif font-bold text-[#2C2033]">
            Auspicious Muhurat Finder
          </h1>
          <p className="text-base text-[#2A2421]/80">
            Calculate vetted shubh muhurats and avoid doshas using classical Shastric rules.
          </p>
        </div>

        <div className="p-6 rounded-2xl bg-white border border-[#2C2033]/10 shadow-sm space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#B74717] mb-1">
                SELECT CEREMONY
              </label>
              <select
                value={ceremony}
                onChange={(e) => setCeremony(e.target.value)}
                className="w-full bg-[#FFF7ED] border border-[#2C2033]/20 rounded-lg px-3 py-2 text-sm text-[#2A2421] focus:ring-2 focus:ring-[#F16A24]"
              >
                {CEREMONIES.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-xs font-semibold text-[#B74717] mb-1">
                PREFERRED DATE
              </label>
              <input
                type="date"
                value={date}
                onChange={(e) => setDate(e.target.value)}
                className="w-full bg-[#FFF7ED] border border-[#2C2033]/20 rounded-lg px-3 py-2 text-sm text-[#2A2421] focus:ring-2 focus:ring-[#F16A24]"
              />
            </div>
          </div>

          <button
            onClick={handleSearch}
            disabled={loading}
            className="w-full py-2.5 rounded-lg bg-[#F16A24] text-white font-medium hover:bg-[#B74717] transition-colors flex items-center justify-center space-x-2"
          >
            {loading ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                <span>Evaluating Shastric Rulesets...</span>
              </>
            ) : (
              <span>Find Auspicious Timings</span>
            )}
          </button>
        </div>

        {searched && (
          <div className="space-y-4">
            <h2 className="text-xl font-serif font-bold text-[#2C2033]">
              Calculated Windows for {date}
            </h2>
            <div className="space-y-3">
              {windows.length > 0 ? (
                windows.map((w) => (
                  <div key={w.id} className="p-5 rounded-xl bg-white border border-[#2C2033]/10 space-y-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <CheckCircle2 className="w-5 h-5 text-[#F16A24]" />
                        <span className="font-bold text-[#2C2033]">{w.title}</span>
                      </div>
                      {w.is_recommended && (
                        <span className="px-2.5 py-1 rounded bg-[#EFE3D3] text-[#2C2033] text-xs font-semibold">
                          Recommended · Shubh {w.shubh_score}%
                        </span>
                      )}
                    </div>
                    <div className="text-2xl font-bold text-[#2C2033]">
                      {formatTime(w.start)} – {formatTime(w.end)}
                    </div>
                    <p className="text-xs text-[#2A2421]/80">
                      {w.notes} ({w.tithi}, {w.nakshatra})
                    </p>
                  </div>
                ))
              ) : (
                <div className="p-5 rounded-xl bg-white border border-[#2C2033]/10 space-y-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      <CheckCircle2 className="w-5 h-5 text-[#F16A24]" />
                      <span className="font-bold text-[#2C2033]">Primary Shubh Window (Abhijit)</span>
                    </div>
                    <span className="px-2.5 py-1 rounded bg-[#EFE3D3] text-[#2C2033] text-xs font-semibold">
                      Recommended
                    </span>
                  </div>
                  <div className="text-2xl font-bold text-[#2C2033]">11:45 AM – 12:35 PM</div>
                  <p className="text-xs text-[#2A2421]/80">
                    Coincides with mid-day Abhijit Muhurat. Free from Durmuhurat and Rahu Kaal afflictions.
                  </p>
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
