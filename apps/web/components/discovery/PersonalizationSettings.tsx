'use client';

import React, { useState, useEffect } from 'react';

export interface PersonalizationPreferences {
  user_id?: number;
  personalization_enabled: boolean;
  tradition: string | null;
  gotra: string | null;
  preferred_region_id: string | null;
  preferred_language: string;
}

export const PersonalizationSettings: React.FC = () => {
  const [preferences, setPreferences] = useState<PersonalizationPreferences>({
    personalization_enabled: true,
    tradition: '',
    gotra: '',
    preferred_region_id: '',
    preferred_language: 'en',
  });
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [loading, setLoading] = useState<boolean>(false);

  useEffect(() => {
    fetch('/api/v1/me/discovery-preferences')
      .then((res) => {
        if (res.ok) return res.json();
        throw new Error('Failed to load preferences');
      })
      .then((data: PersonalizationPreferences) => {
        setPreferences({
          personalization_enabled: Boolean(data.personalization_enabled),
          tradition: data.tradition || '',
          gotra: data.gotra || '',
          preferred_region_id: data.preferred_region_id || '',
          preferred_language: data.preferred_language || 'en',
        });
      })
      .catch(() => {
        // Fallback default state
      });
  }, []);

  const handleSave = async () => {
    setLoading(true);
    setStatusMessage(null);
    try {
      const res = await fetch('/api/v1/me/discovery-preferences', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(preferences),
      });
      if (res.ok) {
        setStatusMessage('Devotee discovery preferences updated successfully.');
      } else {
        setStatusMessage('Error saving preferences.');
      }
    } catch {
      setStatusMessage('Network error updating preferences.');
    } finally {
      setLoading(false);
    }
  };

  const handleResetRecommendations = async () => {
    if (!window.confirm('Reset all inferred browsing and recommendation history? Explicit preferences will remain safe.')) {
      return;
    }
    setLoading(true);
    setStatusMessage(null);
    try {
      const res = await fetch('/api/v1/me/recommendations/reset', {
        method: 'POST',
      });
      if (res.ok) {
        setStatusMessage('Inferred recommendation history has been reset.');
      }
    } catch {
      setStatusMessage('Error resetting recommendation history.');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteSearchHistory = async () => {
    if (!window.confirm('Delete all past search and discovery query history?')) {
      return;
    }
    setLoading(true);
    setStatusMessage(null);
    try {
      const res = await fetch('/api/v1/me/search-history', {
        method: 'DELETE',
      });
      if (res.ok) {
        setStatusMessage('Search query history successfully cleared.');
      }
    } catch {
      setStatusMessage('Error clearing search history.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      className="max-w-2xl mx-auto p-6 rounded-xl border shadow-sm"
      style={{
        backgroundColor: '#FFF7ED', // Warm Ivory (45%)
        borderColor: '#EFE3D3', // Soft Sand (10%)
        color: '#2A2421', // Deep Text (3%)
      }}
    >
      <div className="border-b pb-4 mb-6" style={{ borderColor: '#EFE3D3' }}>
        <h2
          className="text-2xl font-serif font-bold tracking-tight"
          style={{ color: '#2C2033' }} // Aubergine Ink (22%)
        >
          Sacred Discovery & Privacy Preferences
        </h2>
        <p className="text-sm mt-1" style={{ color: '#2A2421' }}>
          Control personalized ritual recommendations, Vedic traditions, and search history.
        </p>
      </div>

      {statusMessage && (
        <div
          className="p-3 mb-6 rounded-lg text-sm font-medium border"
          style={{
            backgroundColor: '#EFE3D3',
            borderColor: '#B74717',
            color: '#2C2033',
          }}
          role="status"
        >
          {statusMessage}
        </div>
      )}

      <div className="space-y-6">
        {/* Toggle Personalization */}
        <div className="flex items-center justify-between p-4 rounded-lg border" style={{ backgroundColor: '#FFF7ED', borderColor: '#EFE3D3' }}>
          <div>
            <span className="font-semibold block" style={{ color: '#2C2033' }}>
              Enable Ritual Personalization
            </span>
            <span className="text-xs" style={{ color: '#2A2421' }}>
              Recommend pujas, pandits, and auspicious timings aligned with your tradition.
            </span>
          </div>
          <button
            type="button"
            role="switch"
            aria-checked={preferences.personalization_enabled}
            onClick={() => setPreferences((p) => ({ ...p, personalization_enabled: !p.personalization_enabled }))}
            className="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none"
            style={{
              backgroundColor: preferences.personalization_enabled ? '#F16A24' : '#EFE3D3', // Navdhya Flame vs Soft Sand
            }}
          >
            <span
              className={`inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out mt-0.5 ${
                preferences.personalization_enabled ? 'translate-x-5' : 'translate-x-0.5'
              }`}
            />
          </button>
        </div>

        {/* Vedic Tradition */}
        <div>
          <label className="block text-sm font-semibold mb-1" style={{ color: '#2C2033' }}>
            Vedic Sampradaya / Tradition
          </label>
          <select
            value={preferences.tradition || ''}
            onChange={(e) => setPreferences((p) => ({ ...p, tradition: e.target.value }))}
            className="w-full px-3 py-2 rounded-lg border focus:outline-none font-medium text-sm"
            style={{
              backgroundColor: '#FFF7ED',
              borderColor: '#EFE3D3',
              color: '#2C2033',
            }}
          >
            <option value="">Select Tradition (Optional)</option>
            <option value="smartha">Smartha</option>
            <option value="vaishnava">Vaishnava</option>
            <option value="shaiva">Shaiva</option>
            <option value="shakta">Shakta</option>
            <option value="vedic_general">General Vedic</option>
          </select>
        </div>

        {/* Gotra */}
        <div>
          <label className="block text-sm font-semibold mb-1" style={{ color: '#2C2033' }}>
            Gotra
          </label>
          <input
            type="text"
            placeholder="e.g. Kashyapa, Bharadwaja, Vashistha"
            value={preferences.gotra || ''}
            onChange={(e) => setPreferences((p) => ({ ...p, gotra: e.target.value }))}
            className="w-full px-3 py-2 rounded-lg border focus:outline-none font-medium text-sm"
            style={{
              backgroundColor: '#FFF7ED',
              borderColor: '#EFE3D3',
              color: '#2C2033',
            }}
          />
        </div>

        {/* Action Buttons */}
        <div className="pt-4 flex flex-wrap gap-3">
          <button
            type="button"
            disabled={loading}
            onClick={handleSave}
            className="px-5 py-2 rounded-lg font-semibold text-sm shadow transition duration-150 text-white"
            style={{ backgroundColor: '#F16A24' }} // Navdhya Flame (14%)
          >
            {loading ? 'Saving...' : 'Save Preferences'}
          </button>

          <button
            type="button"
            disabled={loading}
            onClick={handleResetRecommendations}
            className="px-4 py-2 rounded-lg font-medium text-sm border transition duration-150"
            style={{
              borderColor: '#B74717', // Deep Ember (6%)
              color: '#B74717',
              backgroundColor: '#FFF7ED',
            }}
          >
            Reset Inferred Recommendations
          </button>

          <button
            type="button"
            disabled={loading}
            onClick={handleDeleteSearchHistory}
            className="px-4 py-2 rounded-lg font-medium text-sm border transition duration-150"
            style={{
              borderColor: '#2C2033', // Aubergine Ink (22%)
              color: '#2C2033',
              backgroundColor: '#FFF7ED',
            }}
          >
            Clear Search History
          </button>
        </div>
      </div>
    </div>
  );
};

