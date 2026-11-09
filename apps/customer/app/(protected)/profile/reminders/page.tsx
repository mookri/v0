'use client';

import React, { useState, useEffect } from 'react';

/**
 * Devotee Ritual Reminders Page (E3 / E7).
 * Displays astronomical Panchang reminder timeline (Shraddha, Janma Tithi, Solar Anniversary).
 * Sacred Ember Palette: Warm Ivory (#FFF7ED), Aubergine Ink (#2C2033), Navdhya Flame (#F16A24),
 * Soft Sand (#EFE3D3), Deep Ember (#B74717), Deep Text (#2A2421). Zero green.
 */

interface Reminder {
  id: string;
  type: string;
  title: string;
  anchor_rule: Record<string, any>;
  advance_days: number[];
  next_occurrence_at: string | null;
  is_active: boolean;
}

export default function RemindersPage() {
  const [reminders, setReminders] = useState<Reminder[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  // New Reminder Form State
  const [title, setTitle] = useState('');
  const [type, setType] = useState('SHRADDHA');
  const [lunarMonth, setLunarMonth] = useState(6); // Bhadrapada / Ashvina
  const [paksha, setPaksha] = useState('KRISHNA');
  const [tithi, setTithi] = useState(15); // Amavasya (Mahalaya)
  const [advanceDays, setAdvanceDays] = useState('7, 3, 1');

  const loadReminders = () => {
    fetch('/api/reminders')
      .then((res) => res.json())
      .then((data) => {
        if (data?.data && Array.isArray(data.data)) {
          setReminders(data.data);
        }
      })
      .catch(() => setMessage('Failed to load reminders'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadReminders();
  }, []);

  const handleAdd = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage(null);

    const parsedAdvance = advanceDays
      .split(',')
      .map((s) => parseInt(s.trim(), 10))
      .filter((n) => !isNaN(n));

    try {
      const res = await fetch('/api/reminders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type,
          title: title.trim(),
          anchor_rule: {
            type,
            lunar_month: lunarMonth,
            paksha,
            tithi,
            timezone: 'Asia/Kolkata',
          },
          advance_days: parsedAdvance.length > 0 ? parsedAdvance : [7, 3, 1],
          is_active: true,
        }),
      });

      if (!res.ok) throw new Error('Failed to create reminder');

      setTitle('');
      setShowForm(false);
      setMessage('Ritual reminder created with astronomical Panchang calculation.');
      loadReminders();
    } catch {
      setMessage('Error creating reminder.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Cancel this ritual reminder?')) return;
    try {
      const res = await fetch(`/api/reminders/${id}`, { method: 'DELETE' });
      if (res.ok) {
        setReminders((prev) => prev.filter((r) => r.id !== id));
        setMessage('Reminder removed.');
      }
    } catch {
      setMessage('Failed to remove reminder.');
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
      <div className="max-w-3xl mx-auto">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between border-b border-[#EFE3D3] pb-5 mb-8 gap-4">
          <div>
            <div className="inline-block px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-[#EFE3D3] text-[#B74717] mb-2">
              Panchang Engine
            </div>
            <h1 className="text-3xl font-serif font-bold text-[#2C2033]">Ritual Reminders</h1>
            <p className="mt-1 text-sm text-[#2A2421]/80">
              Astronomically computed alerts for ancestral Shraddha, Janma Tithi, and sacred festivals.
            </p>
          </div>
          <button
            onClick={() => setShowForm(!showForm)}
            className="self-start sm:self-auto px-4 py-2 rounded-lg bg-[#F16A24] text-white text-sm font-medium hover:bg-[#B74717] transition-all cursor-pointer shadow-xs"
          >
            {showForm ? 'Cancel' : '+ New Panchang Reminder'}
          </button>
        </div>

        {message && (
          <div className="mb-6 p-4 rounded-lg bg-[#EFE3D3]/40 border border-[#EFE3D3] text-[#2C2033] text-sm">
            {message}
          </div>
        )}

        {/* Create Form */}
        {showForm && (
          <form onSubmit={handleAdd} className="bg-white rounded-xl p-6 border border-[#EFE3D3] shadow-xs mb-8 space-y-4">
            <h2 className="text-lg font-serif font-semibold text-[#2C2033] border-b border-[#EFE3D3] pb-2">
              Configure Ritual Reminder
            </h2>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label htmlFor="reminder_title" className="block text-sm font-medium text-[#2C2033]">Reminder Title *</label>
                <input
                  id="reminder_title"
                  type="text"
                  required
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  placeholder="e.g. Pitru Paksha Tithi Shraddha"
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm focus:ring-1 focus:ring-[#F16A24]"
                />
              </div>

              <div>
                <label htmlFor="reminder_type" className="block text-sm font-medium text-[#2C2033]">Ritual Type *</label>
                <select
                  id="reminder_type"
                  value={type}
                  onChange={(e) => setType(e.target.value)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm bg-white"
                >
                  <option value="SHRADDHA">Shraddha / Ancestral Memorial</option>
                  <option value="LUNAR_BIRTHDAY">Janma Tithi (Lunar Birthday)</option>
                  <option value="SOLAR_BIRTHDAY">Solar Birthday / Sankranti</option>
                  <option value="WEDDING_ANNIVERSARY">Wedding Anniversary</option>
                  <option value="CUSTOM_TITHI">Custom Observance Tithi</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label htmlFor="paksha" className="block text-sm font-medium text-[#2C2033]">Paksha</label>
                <select
                  id="paksha"
                  value={paksha}
                  onChange={(e) => setPaksha(e.target.value)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm bg-white"
                >
                  <option value="SHUKLA">Shukla Paksha (Waxing)</option>
                  <option value="KRISHNA">Krishna Paksha (Waning)</option>
                </select>
              </div>

              <div>
                <label htmlFor="tithi" className="block text-sm font-medium text-[#2C2033]">Tithi (1 to 15)</label>
                <input
                  id="tithi"
                  type="number"
                  min="1"
                  max="15"
                  value={tithi}
                  onChange={(e) => setTithi(parseInt(e.target.value, 10))}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm"
                />
              </div>

              <div>
                <label htmlFor="advance_days" className="block text-sm font-medium text-[#2C2033]">Advance Alert Days</label>
                <input
                  id="advance_days"
                  type="text"
                  value={advanceDays}
                  onChange={(e) => setAdvanceDays(e.target.value)}
                  placeholder="7, 3, 1"
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm"
                />
              </div>
            </div>

            <div className="flex justify-end pt-2">
              <button
                type="submit"
                disabled={saving}
                className="px-5 py-2 rounded-lg bg-[#F16A24] text-white text-sm font-medium hover:bg-[#B74717] transition-all disabled:opacity-50"
              >
                {saving ? 'Calculating...' : 'Schedule Reminder'}
              </button>
            </div>
          </form>
        )}

        {/* Reminders Timeline */}
        <div className="space-y-4">
          {reminders.length === 0 ? (
            <div className="bg-white rounded-xl p-10 text-center border border-[#EFE3D3]">
              <p className="text-[#2A2421]/60 text-sm">No ritual reminders configured.</p>
              <button
                onClick={() => setShowForm(true)}
                className="mt-4 px-4 py-2 rounded-lg bg-[#FFF7ED] border border-[#EFE3D3] text-[#F16A24] font-medium text-sm hover:bg-[#EFE3D3]/50 transition-all"
              >
                Schedule your first ritual reminder
              </button>
            </div>
          ) : (
            reminders.map((reminder) => (
              <div
                key={reminder.id}
                className="bg-white rounded-xl p-5 border border-[#EFE3D3] shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
              >
                <div>
                  <div className="flex items-center gap-3">
                    <h3 className="font-semibold text-lg text-[#2C2033]">{reminder.title}</h3>
                    <span className="text-xs px-2.5 py-0.5 rounded-full font-mono bg-[#EFE3D3] text-[#2C2033]">
                      {reminder.type}
                    </span>
                  </div>

                  <div className="mt-2 flex flex-wrap items-center gap-4 text-xs text-[#2A2421]/75">
                    {reminder.next_occurrence_at ? (
                      <span className="font-medium text-[#B74717] bg-[#FFF7ED] px-2.5 py-1 rounded-md border border-[#EFE3D3]">
                        Next: {new Date(reminder.next_occurrence_at).toLocaleDateString('en-IN', {
                          weekday: 'short',
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric',
                        })}
                      </span>
                    ) : (
                      <span className="text-[#2A2421]/50">Calculating next astronomical date...</span>
                    )}

                    {reminder.advance_days && (
                      <span>Advance alerts: {reminder.advance_days.join(', ')} days prior</span>
                    )}
                  </div>
                </div>

                <button
                  onClick={() => handleDelete(reminder.id)}
                  className="self-end sm:self-auto text-xs text-[#B74717] hover:text-[#B74717]/70 font-medium cursor-pointer"
                >
                  Cancel
                </button>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}

