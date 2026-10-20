'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';

interface AvailabilitySlot {
  day: string;
  start: string;
  end: string;
}

export function PanditAvailabilityView({ slots }: { slots: AvailabilitySlot[] }) {
  const router = useRouter();
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [localSlots, setLocalSlots] = useState<AvailabilitySlot[]>(slots ?? []);

  function addSlot() {
    setLocalSlots((s) => [...s, { day: 'mon', start: '09:00', end: '17:00' }]);
  }

  function removeSlot(index: number) {
    setLocalSlots((s) => s.filter((_, i) => i !== index));
  }

  function updateSlot(index: number, field: keyof AvailabilitySlot, value: string) {
    setLocalSlots((s) => s.map((slot, i) => (i === index ? { ...slot, [field]: value } : slot)));
  }

  async function save() {
    setSaving(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await fetch('/api/pandit/availability', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slots: localSlots }),
      });
      const body = (await res.json()) as { data?: unknown; error?: { code?: string; message?: string } };
      if (!res.ok) {
        setError(body.error?.code ?? 'UPDATE_FAILED');
      } else {
        setSuccess('Availability updated.');
        setEditing(false);
        router.refresh();
      }
    } catch {
      setError('NETWORK_ERROR');
    } finally {
      setSaving(false);
    }
  }

  if (!editing) {
    return (
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold">Availability</h2>
          <button
            type="button"
            onClick={() => setEditing(true)}
            data-testid="edit-availability-button"
            className="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium hover:bg-accent/10"
          >
            Edit
          </button>
        </div>
        {localSlots.length === 0 ? (
          <p className="text-gray-500">No availability slots set.</p>
        ) : (
          <div className="space-y-2">
            {localSlots.map((slot, i) => (
              <div key={i} className="rounded-md border border-gray-200 px-3 py-2 text-sm">
                <span className="font-medium">{slot.day}</span>: {slot.start} – {slot.end}
              </div>
            ))}
          </div>
        )}
        {error ? <p className="text-sm text-red-600">{error}</p> : null}
        {success ? <p className="text-sm text-green-600">{success}</p> : null}
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h2 className="text-lg font-semibold">Edit Availability</h2>
      <div className="space-y-3">
        {localSlots.map((slot, i) => (
          <div key={i} className="flex gap-2 items-end">
            <label className="block text-sm flex-1">
              <span className="font-medium">Day</span>
              <select
                value={slot.day}
                onChange={(e) => updateSlot(i, 'day', e.target.value)}
                className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
              >
                {['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'].map((d) => (
                  <option key={d} value={d}>{d}</option>
                ))}
              </select>
            </label>
            <label className="block text-sm w-24">
              <span className="font-medium">Start</span>
              <input
                type="time"
                value={slot.start}
                onChange={(e) => updateSlot(i, 'start', e.target.value)}
                className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
              />
            </label>
            <label className="block text-sm w-24">
              <span className="font-medium">End</span>
              <input
                type="time"
                value={slot.end}
                onChange={(e) => updateSlot(i, 'end', e.target.value)}
                className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
              />
            </label>
            <button
              type="button"
              onClick={() => removeSlot(i)}
              className="rounded-md border border-red-500/40 bg-red-500/10 px-2 py-2 text-xs text-red-700"
            >
              Remove
            </button>
          </div>
        ))}
        <button
          type="button"
          onClick={addSlot}
          className="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium hover:bg-accent/10"
        >
          + Add slot
        </button>
      </div>
      {error ? <p className="text-sm text-red-600">{error}</p> : null}
      <div className="flex gap-2 mt-4">
        <button
          type="button"
          onClick={save}
          disabled={saving}
          className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
        >
          {saving ? 'Saving…' : 'Save'}
        </button>
        <button
          type="button"
          onClick={() => setEditing(false)}
          className="rounded-md border border-border px-4 py-2 text-sm"
        >
          Cancel
        </button>
      </div>
    </div>
  );
}
