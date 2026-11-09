'use client';

import React, { useState, useEffect } from 'react';

/**
 * Devotee Family & Ancestral Lineage Page (E1 / E7).
 * Allows devotees to maintain family lineage for sankalpa and Shraddha observances.
 * Sacred Ember Palette: Warm Ivory (#FFF7ED), Aubergine Ink (#2C2033), Navdhya Flame (#F16A24),
 * Soft Sand (#EFE3D3), Deep Ember (#B74717), Deep Text (#2A2421). Zero green.
 */

interface FamilyMember {
  id: string;
  full_name: string;
  relation: string;
  nakshatra: string | null;
  rashi: string | null;
  birth_date: string | null;
  death_date: string | null;
  data_subject_kind: 'SELF' | 'DECEASED' | 'THIRD_PARTY';
}

const RELATIONS = [
  'FATHER', 'MOTHER', 'SPOUSE', 'SON', 'DAUGHTER', 'BROTHER', 'SISTER',
  'GRANDFATHER', 'GRANDMOTHER', 'UNCLE', 'AUNT', 'OTHER_ANCESTOR'
];

export default function FamilyPage() {
  const [members, setMembers] = useState<FamilyMember[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  // Form State
  const [fullName, setFullName] = useState('');
  const [relation, setRelation] = useState('FATHER');
  const [nakshatra, setNakshatra] = useState('');
  const [rashi, setRashi] = useState('');
  const [birthDate, setBirthDate] = useState('');
  const [deathDate, setDeathDate] = useState('');
  const [dataSubjectKind, setDataSubjectKind] = useState<'SELF' | 'DECEASED' | 'THIRD_PARTY'>('DECEASED');

  const loadMembers = () => {
    fetch('/api/family')
      .then((res) => res.json())
      .then((data) => {
        if (data?.data && Array.isArray(data.data)) {
          setMembers(data.data);
        }
      })
      .catch(() => setMessage('Failed to load family members'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadMembers();
  }, []);

  const handleAdd = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage(null);

    try {
      const res = await fetch('/api/family', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          full_name: fullName.trim(),
          relation,
          nakshatra: nakshatra || null,
          rashi: rashi || null,
          birth_date: birthDate || null,
          death_date: deathDate || null,
          data_subject_kind: dataSubjectKind,
        }),
      });

      if (!res.ok) throw new Error('Add failed');

      setFullName('');
      setBirthDate('');
      setDeathDate('');
      setShowForm(false);
      setMessage('Family member added.');
      loadMembers();
    } catch {
      setMessage('Failed to add family member.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Remove this lineage record?')) return;
    try {
      const res = await fetch(`/api/family/${id}`, { method: 'DELETE' });
      if (res.ok) {
        setMembers((prev) => prev.filter((m) => m.id !== id));
        setMessage('Lineage record removed.');
      }
    } catch {
      setMessage('Failed to remove record.');
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
              Ancestry & Lineage
            </div>
            <h1 className="text-3xl font-serif font-bold text-[#2C2033]">Family Members & Pitrus</h1>
            <p className="mt-1 text-sm text-[#2A2421]/80">
              Preserve sacred lineage details for family sankalpa, tithi memorials, and Shraddha rituals.
            </p>
          </div>
          <button
            onClick={() => setShowForm(!showForm)}
            className="self-start sm:self-auto px-4 py-2 rounded-lg bg-[#F16A24] text-white text-sm font-medium hover:bg-[#B74717] transition-all cursor-pointer shadow-xs"
          >
            {showForm ? 'Cancel' : '+ Add Lineage Member'}
          </button>
        </div>

        {message && (
          <div className="mb-6 p-4 rounded-lg bg-[#EFE3D3]/40 border border-[#EFE3D3] text-[#2C2033] text-sm">
            {message}
          </div>
        )}

        {/* Add Form */}
        {showForm && (
          <form onSubmit={handleAdd} className="bg-white rounded-xl p-6 border border-[#EFE3D3] shadow-xs mb-8 space-y-4">
            <h2 className="text-lg font-serif font-semibold text-[#2C2033] border-b border-[#EFE3D3] pb-2">
              New Lineage Record
            </h2>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label htmlFor="family_name" className="block text-sm font-medium text-[#2C2033]">Full Name *</label>
                <input
                  id="family_name"
                  type="text"
                  required
                  value={fullName}
                  onChange={(e) => setFullName(e.target.value)}
                  placeholder="e.g. Late Sri R. Sharma"
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm focus:ring-1 focus:ring-[#F16A24]"
                />
              </div>

              <div>
                <label htmlFor="relation" className="block text-sm font-medium text-[#2C2033]">Relationship *</label>
                <select
                  id="relation"
                  value={relation}
                  onChange={(e) => setRelation(e.target.value)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm bg-white focus:ring-1 focus:ring-[#F16A24]"
                >
                  {RELATIONS.map((rel) => (
                    <option key={rel} value={rel}>{rel}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label htmlFor="data_subject_kind" className="block text-sm font-medium text-[#2C2033]">Status *</label>
                <select
                  id="data_subject_kind"
                  value={dataSubjectKind}
                  onChange={(e) => setDataSubjectKind(e.target.value as any)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm bg-white"
                >
                  <option value="DECEASED">Deceased (Ancestor)</option>
                  <option value="SELF">Self / Immediate</option>
                  <option value="THIRD_PARTY">Living Relative</option>
                </select>
              </div>

              <div>
                <label htmlFor="birth_date" className="block text-sm font-medium text-[#2C2033]">Birth Date</label>
                <input
                  id="birth_date"
                  type="date"
                  value={birthDate}
                  onChange={(e) => setBirthDate(e.target.value)}
                  className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm"
                />
              </div>

              {dataSubjectKind === 'DECEASED' && (
                <div>
                  <label htmlFor="death_date" className="block text-sm font-medium text-[#2C2033]">Demise Date (Tithi Anchor)</label>
                  <input
                    id="death_date"
                    type="date"
                    value={deathDate}
                    onChange={(e) => setDeathDate(e.target.value)}
                    className="mt-1 block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm"
                  />
                </div>
              )}
            </div>

            <div className="flex justify-end pt-2">
              <button
                type="submit"
                disabled={saving}
                className="px-5 py-2 rounded-lg bg-[#F16A24] text-white text-sm font-medium hover:bg-[#B74717] transition-all disabled:opacity-50"
              >
                {saving ? 'Adding...' : 'Add Member'}
              </button>
            </div>
          </form>
        )}

        {/* Members List */}
        <div className="space-y-4">
          {members.length === 0 ? (
            <div className="bg-white rounded-xl p-10 text-center border border-[#EFE3D3]">
              <p className="text-[#2A2421]/60 text-sm">No lineage members recorded yet.</p>
              <button
                onClick={() => setShowForm(true)}
                className="mt-4 px-4 py-2 rounded-lg bg-[#FFF7ED] border border-[#EFE3D3] text-[#F16A24] font-medium text-sm hover:bg-[#EFE3D3]/50 transition-all"
              >
                Record your first ancestor or family member
              </button>
            </div>
          ) : (
            members.map((member) => (
              <div
                key={member.id}
                className="bg-white rounded-xl p-5 border border-[#EFE3D3] shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
              >
                <div>
                  <div className="flex items-center gap-3">
                    <h3 className="font-semibold text-lg text-[#2C2033]">{member.full_name}</h3>
                    <span className="text-xs px-2.5 py-0.5 rounded-full font-mono bg-[#EFE3D3] text-[#2C2033]">
                      {member.relation}
                    </span>
                    {member.data_subject_kind === 'DECEASED' && (
                      <span className="text-xs px-2 py-0.5 rounded-full bg-[#B74717]/10 text-[#B74717] font-medium">
                        Pitru
                      </span>
                    )}
                  </div>
                  <div className="text-xs text-[#2A2421]/70 mt-1 flex flex-wrap gap-4">
                    {member.birth_date && <span>Born: {member.birth_date}</span>}
                    {member.death_date && <span>Demise: {member.death_date}</span>}
                    {member.nakshatra && <span>Nakshatra: {member.nakshatra}</span>}
                  </div>
                </div>

                <button
                  onClick={() => handleDelete(member.id)}
                  className="self-end sm:self-auto text-xs text-[#B74717] hover:text-[#B74717]/70 font-medium cursor-pointer"
                >
                  Remove
                </button>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}

