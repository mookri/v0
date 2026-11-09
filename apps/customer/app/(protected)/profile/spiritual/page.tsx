'use client';

import React, { useState, useEffect } from 'react';

/**
 * Devotee Spiritual Profile Page (E1 / E7).
 * Sacred Ember Palette: Warm Ivory (#FFF7ED), Aubergine Ink (#2C2033),
 * Navdhya Flame (#F16A24), Soft Sand (#EFE3D3), Deep Ember (#B74717), Deep Text (#2A2421).
 * Zero green. Special-category spiritual data is consent-gated and strictly excluded from warehouse export.
 */

const NAKSHATRAS = [
  'Ashwini', 'Bharani', 'Krittika', 'Rohini', 'Mrigashirsha', 'Ardra',
  'Punarvasu', 'Pushya', 'Ashlesha', 'Magha', 'Purva Phalguni', 'Uttara Phalguni',
  'Hasta', 'Chitra', 'Svati', 'Vishakha', 'Anuradha', 'Jyeshtha',
  'Mula', 'Purva Ashadha', 'Uttara Ashadha', 'Shravana', 'Dhanishta',
  'Shatabhisha', 'Purva Bhadrapada', 'Uttara Bhadrapada', 'Revati'
];

const RASHIS = [
  'Mesha (Aries)', 'Vrishabha (Taurus)', 'Mithuna (Gemini)', 'Karka (Cancer)',
  'Simha (Leo)', 'Kanya (Virgo)', 'Tula (Libra)', 'Vrishchika (Scorpio)',
  'Dhanu (Sagittarius)', 'Makara (Capricorn)', 'Kumbha (Aquarius)', 'Meena (Pisces)'
];

export default function SpiritualProfilePage() {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  const [gotra, setGotra] = useState('');
  const [nakshatra, setNakshatra] = useState('');
  const [rashi, setRashi] = useState('');
  const [kuladevataName, setKuladevataName] = useState('');
  const [birthDate, setBirthDate] = useState('');
  const [birthTime, setBirthTime] = useState('');
  const [birthPlace, setBirthPlace] = useState('');
  const [birthTimezone, setBirthTimezone] = useState('Asia/Kolkata');
  const [tradition, setTradition] = useState<'AMANTA' | 'PURNIMANTA'>('AMANTA');

  useEffect(() => {
    fetch('/api/spiritual')
      .then((res) => res.json())
      .then((data) => {
        if (data?.data) {
          const p = data.data;
          setGotra(p.gotra || '');
          setNakshatra(p.nakshatra || '');
          setRashi(p.rashi || '');
          setKuladevataName(p.kuladevata_name || '');
          setBirthDate(p.birth_date || '');
          setBirthTime(p.birth_time || '');
          setBirthPlace(p.birth_place || '');
          setBirthTimezone(p.birth_timezone || 'Asia/Kolkata');
          setTradition(p.tradition || 'AMANTA');
        }
      })
      .catch(() => setMessage('Failed to load spiritual profile'))
      .finally(() => setLoading(false));
  }, []);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage(null);

    try {
      const res = await fetch('/api/spiritual', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          gotra: gotra.trim() || null,
          nakshatra: nakshatra || null,
          rashi: rashi || null,
          kuladevata_name: kuladevataName.trim() || null,
          birth_date: birthDate || null,
          birth_time: birthTime || null,
          birth_place: birthPlace.trim() || null,
          birth_timezone: birthTimezone,
          tradition,
        }),
      });

      if (!res.ok) throw new Error('Save failed');
      setMessage('Spiritual profile saved successfully.');
    } catch {
      setMessage('Error updating spiritual profile. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#FFF7ED] flex items-center justify-center text-[#2C2033]">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#F16A24]" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#FFF7ED] py-8 px-4 sm:px-6 lg:px-8 text-[#2A2421]">
      <div className="max-w-2xl mx-auto">
        {/* Header */}
        <div className="border-b border-[#EFE3D3] pb-5 mb-8">
          <div className="inline-block px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-[#EFE3D3] text-[#B74717] mb-2">
            Sacred Lineage
          </div>
          <h1 className="text-3xl font-serif font-bold text-[#2C2033]">Spiritual & Panchang Profile</h1>
          <p className="mt-2 text-sm text-[#2A2421]/80">
            Your Gotra, Nakshatra, and ancestral tradition guide sankalpa recitations and astronomical Panchang calculations.
          </p>
        </div>

        {message && (
          <div className="mb-6 p-4 rounded-lg bg-[#EFE3D3]/40 border border-[#EFE3D3] text-[#2C2033] text-sm">
            {message}
          </div>
        )}

        <form onSubmit={handleSave} className="space-y-6">
          {/* Gotra & Kuladevata */}
          <div className="bg-white rounded-xl p-6 border border-[#EFE3D3] shadow-xs space-y-4">
            <h2 className="text-lg font-serif font-semibold text-[#2C2033] border-b border-[#EFE3D3] pb-2">
              Lineage & Kuladevata
            </h2>

            <div>
              <label htmlFor="gotra" className="block text-sm font-medium text-[#2C2033]">Gotra</label>
              <input
                id="gotra"
                type="text"
                value={gotra}
                onChange={(e) => setGotra(e.target.value)}
                placeholder="e.g. Kashyapa, Bharadwaja, Vashistha"
                className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md focus:outline-hidden focus:ring-1 focus:ring-[#F16A24] focus:border-[#F16A24] text-sm"
              />
            </div>

            <div>
              <label htmlFor="kuladevata" className="block text-sm font-medium text-[#2C2033]">Kuladevata / Ishtadevata Name</label>
              <input
                id="kuladevata"
                type="text"
                value={kuladevataName}
                onChange={(e) => setKuladevataName(e.target.value)}
                placeholder="e.g. Sri Venkateshwara, Sri Mookambika"
                className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md focus:outline-hidden focus:ring-1 focus:ring-[#F16A24] focus:border-[#F16A24] text-sm"
              />
            </div>
          </div>

          {/* Astronomical Alignment */}
          <div className="bg-white rounded-xl p-6 border border-[#EFE3D3] shadow-xs space-y-4">
            <h2 className="text-lg font-serif font-semibold text-[#2C2033] border-b border-[#EFE3D3] pb-2">
              Vedic Astrology Coordinates
            </h2>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label htmlFor="nakshatra" className="block text-sm font-medium text-[#2C2033]">Janma Nakshatra</label>
                <select
                  id="nakshatra"
                  value={nakshatra}
                  onChange={(e) => setNakshatra(e.target.value)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md focus:outline-hidden focus:ring-1 focus:ring-[#F16A24] focus:border-[#F16A24] text-sm bg-white"
                >
                  <option value="">Select Nakshatra</option>
                  {NAKSHATRAS.map((n) => (
                    <option key={n} value={n}>{n}</option>
                  ))}
                </select>
              </div>

              <div>
                <label htmlFor="rashi" className="block text-sm font-medium text-[#2C2033]">Janma Rashi (Moon Sign)</label>
                <select
                  id="rashi"
                  value={rashi}
                  onChange={(e) => setRashi(e.target.value)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md focus:outline-hidden focus:ring-1 focus:ring-[#F16A24] focus:border-[#F16A24] text-sm bg-white"
                >
                  <option value="">Select Rashi</option>
                  {RASHIS.map((r) => (
                    <option key={r} value={r.split(' ')[0]}>{r}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label htmlFor="birth_date" className="block text-sm font-medium text-[#2C2033]">Birth Date</label>
                <input
                  id="birth_date"
                  type="date"
                  value={birthDate}
                  onChange={(e) => setBirthDate(e.target.value)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md focus:outline-hidden focus:ring-1 focus:ring-[#F16A24] focus:border-[#F16A24] text-sm"
                />
              </div>

              <div>
                <label htmlFor="birth_time" className="block text-sm font-medium text-[#2C2033]">Birth Time (Local)</label>
                <input
                  id="birth_time"
                  type="time"
                  value={birthTime}
                  onChange={(e) => setBirthTime(e.target.value)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md focus:outline-hidden focus:ring-1 focus:ring-[#F16A24] focus:border-[#F16A24] text-sm"
                />
              </div>
            </div>

            <div>
              <label htmlFor="birth_place" className="block text-sm font-medium text-[#2C2033]">Birth Place (City, State/Country)</label>
              <input
                id="birth_place"
                type="text"
                value={birthPlace}
                onChange={(e) => setBirthPlace(e.target.value)}
                placeholder="e.g. Bengaluru, Karnataka, India"
                className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md focus:outline-hidden focus:ring-1 focus:ring-[#F16A24] focus:border-[#F16A24] text-sm"
              />
            </div>
          </div>

          {/* Calendar Tradition */}
          <div className="bg-white rounded-xl p-6 border border-[#EFE3D3] shadow-xs space-y-4">
            <h2 className="text-lg font-serif font-semibold text-[#2C2033] border-b border-[#EFE3D3] pb-2">
              Lunar Calendar Tradition
            </h2>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <label
                className={`p-4 rounded-lg border cursor-pointer transition-all ${
                  tradition === 'AMANTA'
                    ? 'border-[#F16A24] bg-[#FFF7ED] ring-1 ring-[#F16A24]'
                    : 'border-[#EFE3D3] bg-white hover:bg-[#FFF7ED]/30'
                }`}
              >
                <input
                  type="radio"
                  name="tradition"
                  value="AMANTA"
                  checked={tradition === 'AMANTA'}
                  onChange={() => setTradition('AMANTA')}
                  className="sr-only"
                />
                <span className="block font-semibold text-[#2C2033]">Amanta Tradition</span>
                <span className="block text-xs text-[#2A2421]/70 mt-1">
                  Months end on Amavasya (New Moon). Standard in South and Western India.
                </span>
              </label>

              <label
                className={`p-4 rounded-lg border cursor-pointer transition-all ${
                  tradition === 'PURNIMANTA'
                    ? 'border-[#F16A24] bg-[#FFF7ED] ring-1 ring-[#F16A24]'
                    : 'border-[#EFE3D3] bg-white hover:bg-[#FFF7ED]/30'
                }`}
              >
                <input
                  type="radio"
                  name="tradition"
                  value="PURNIMANTA"
                  checked={tradition === 'PURNIMANTA'}
                  onChange={() => setTradition('PURNIMANTA')}
                  className="sr-only"
                />
                <span className="block font-semibold text-[#2C2033]">Purnimanta Tradition</span>
                <span className="block text-xs text-[#2A2421]/70 mt-1">
                  Months end on Purnima (Full Moon). Standard in North and Central India.
                </span>
              </label>
            </div>
          </div>

          <div className="flex justify-end pt-4">
            <button
              type="submit"
              disabled={saving}
              className="px-6 py-2.5 rounded-lg bg-[#F16A24] text-white font-medium hover:bg-[#B74717] focus:outline-hidden focus:ring-2 focus:ring-[#F16A24] focus:ring-offset-2 transition-all disabled:opacity-50 cursor-pointer shadow-xs"
            >
              {saving ? 'Saving...' : 'Save Spiritual Profile'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

