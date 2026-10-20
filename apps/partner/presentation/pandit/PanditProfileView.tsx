'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';

interface PanditProfile {
  id: string;
  display_name: string;
  bio: string | null;
  languages: string[];
  specializations: string[];
  experience_years: number | null;
  certification_refs: string[];
  base_rate_minor: number | null;
  currency: string;
  market: string | null;
  status: string;
  verification_status: string;
}

const LANGUAGES = ['hi', 'en', 'sa', 'ta', 'te', 'bn', 'mr', 'gu', 'kn', 'ml', 'pa', 'or'];

export function PanditProfileView({ profile }: { profile: PanditProfile | null }) {
  const router = useRouter();
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const [displayName, setDisplayName] = useState(profile?.display_name ?? '');
  const [bio, setBio] = useState(profile?.bio ?? '');
  const [languages, setLanguages] = useState<string[]>(profile?.languages ?? []);
  const [specializations, setSpecializations] = useState(profile?.specializations?.join(', ') ?? '');
  const [experienceYears, setExperienceYears] = useState(profile?.experience_years?.toString() ?? '');
  const [certRefs, setCertRefs] = useState(profile?.certification_refs?.join(', ') ?? '');
  const [baseRate, setBaseRate] = useState(profile?.base_rate_minor ? (profile.base_rate_minor / 100).toString() : '');
  const [currency, setCurrency] = useState(profile?.currency ?? 'INR');
  const [market, setMarket] = useState(profile?.market ?? '');

  if (!profile) {
    return (
      <div className="rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500">
        No pandit profile found. Apply to become a pandit first.
      </div>
    );
  }

  async function save() {
    setSaving(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await fetch('/api/pandit/profile', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          display_name: displayName,
          bio: bio || null,
          languages,
          specializations: specializations.split(',').map((s) => s.trim()).filter(Boolean),
          experience_years: experienceYears ? Number(experienceYears) : null,
          certification_refs: certRefs.split(',').map((s) => s.trim()).filter(Boolean),
          base_rate_minor: baseRate ? Math.round(Number(baseRate) * 100) : null,
          currency,
          market: market || null,
        }),
      });
      const body = (await res.json()) as { data?: unknown; error?: { code?: string; message?: string } };
      if (!res.ok) {
        setError(body.error?.code ?? 'UPDATE_FAILED');
      } else {
        setSuccess('Profile updated.');
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
          <h2 className="text-lg font-semibold">Profile</h2>
          <button
            type="button"
            onClick={() => setEditing(true)}
            data-testid="edit-profile-button"
            className="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium hover:bg-accent/10"
          >
            Edit
          </button>
        </div>
        <div className="space-y-2 text-sm">
          <p><span className="font-medium">Name:</span> {profile.display_name}</p>
          <p><span className="font-medium">Status:</span> {profile.status}</p>
          <p><span className="font-medium">Verification:</span> {profile.verification_status}</p>
          {profile.bio ? <p><span className="font-medium">Bio:</span> {profile.bio}</p> : null}
          {profile.languages?.length ? <p><span className="font-medium">Languages:</span> {profile.languages.join(', ')}</p> : null}
          {profile.specializations?.length ? <p><span className="font-medium">Specializations:</span> {profile.specializations.join(', ')}</p> : null}
          {profile.experience_years !== null ? <p><span className="font-medium">Experience:</span> {profile.experience_years} years</p> : null}
          {profile.base_rate_minor ? <p><span className="font-medium">Base rate:</span> {profile.currency} {(profile.base_rate_minor / 100).toFixed(2)}</p> : null}
          {profile.market ? <p><span className="font-medium">Market:</span> {profile.market}</p> : null}
        </div>
        {error ? <p className="text-sm text-red-600">{error}</p> : null}
        {success ? <p className="text-sm text-green-600">{success}</p> : null}
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h2 className="text-lg font-semibold">Edit Profile</h2>
      <div className="space-y-3">
        <label className="block text-sm">
          <span className="font-medium">Display name</span>
          <input type="text" value={displayName} onChange={(e) => setDisplayName(e.target.value)} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
        </label>
        <label className="block text-sm">
          <span className="font-medium">Bio</span>
          <textarea value={bio} onChange={(e) => setBio(e.target.value)} rows={3} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
        </label>
        <label className="block text-sm">
          <span className="font-medium">Languages (comma-separated)</span>
          <input type="text" value={languages.join(', ')} onChange={(e) => setLanguages(e.target.value.split(',').map((s) => s.trim()).filter(Boolean))} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
        </label>
        <label className="block text-sm">
          <span className="font-medium">Specializations (comma-separated)</span>
          <input type="text" value={specializations} onChange={(e) => setSpecializations(e.target.value)} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
        </label>
        <label className="block text-sm">
          <span className="font-medium">Experience (years)</span>
          <input type="number" value={experienceYears} onChange={(e) => setExperienceYears(e.target.value)} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
        </label>
        <label className="block text-sm">
          <span className="font-medium">Certifications (comma-separated)</span>
          <input type="text" value={certRefs} onChange={(e) => setCertRefs(e.target.value)} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
        </label>
        <div className="flex gap-2">
          <label className="block text-sm flex-1">
            <span className="font-medium">Base rate</span>
            <input type="number" value={baseRate} onChange={(e) => setBaseRate(e.target.value)} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
          </label>
          <label className="block text-sm w-24">
            <span className="font-medium">Currency</span>
            <input type="text" value={currency} onChange={(e) => setCurrency(e.target.value)} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
          </label>
        </div>
        <label className="block text-sm">
          <span className="font-medium">Market</span>
          <input type="text" value={market} onChange={(e) => setMarket(e.target.value)} className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
        </label>
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
