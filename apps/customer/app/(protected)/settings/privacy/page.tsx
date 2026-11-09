'use client';

import React, { useState, useEffect } from 'react';

/**
 * Devotee Privacy, Consent & Data Rights Portal (E6 / E7).
 * Implements DPDP / GDPR devotee rights:
 * - Granular notification matrix (Channel x Category).
 * - Explicit purpose consent ledger (RITUAL_REMINDERS, ANALYTICS, MARKETING).
 * - Right to Data Portability / Export (JSON archive).
 * - Right to Erasure / Account Closure with statutory financial retention notice.
 * Sacred Ember Palette: Warm Ivory (#FFF7ED), Aubergine Ink (#2C2033), Navdhya Flame (#F16A24),
 * Soft Sand (#EFE3D3), Deep Ember (#B74717), Deep Text (#2A2421). Zero green.
 */

interface ConsentStatus {
  state: 'GRANTED' | 'WITHDRAWN';
  updated_at: string | null;
  source: string;
}

interface PreferenceItem {
  category: string;
  channel: string;
  is_enabled: boolean;
}

export default function PrivacyPortalPage() {
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);
  const [erasing, setErasing] = useState(false);
  const [showErasureConfirm, setShowErasureConfirm] = useState(false);
  const [erasureReason, setErasureReason] = useState('Personal decision to close account');

  // Consent
  const [ritualConsent, setRitualConsent] = useState(true);
  const [analyticsConsent, setAnalyticsConsent] = useState(false);
  const [marketingConsent, setMarketingConsent] = useState(false);

  // Preferences
  const [remindersWhatsapp, setRemindersWhatsapp] = useState(true);
  const [remindersEmail, setRemindersEmail] = useState(true);
  const [remindersSms, setRemindersSms] = useState(false);

  useEffect(() => {
    Promise.all([
      fetch('/api/consent').then((r) => r.json()),
      fetch('/api/preferences').then((r) => r.json()),
    ])
      .then(([consentRes, prefRes]) => {
        if (consentRes?.data) {
          const c = consentRes.data as Record<string, ConsentStatus>;
          setRitualConsent(c.RITUAL_REMINDERS?.state === 'GRANTED');
          setAnalyticsConsent(c.ANALYTICS?.state === 'GRANTED');
          setMarketingConsent(c.MARKETING?.state === 'GRANTED');
        }
        if (prefRes?.data && Array.isArray(prefRes.data)) {
          const prefs = prefRes.data as PreferenceItem[];
          for (const p of prefs) {
            if (p.category === 'REMINDER') {
              if (p.channel === 'WHATSAPP') setRemindersWhatsapp(p.is_enabled);
              if (p.channel === 'EMAIL') setRemindersEmail(p.is_enabled);
              if (p.channel === 'SMS') setRemindersSms(p.is_enabled);
            }
          }
        }
      })
      .catch(() => setMessage('Failed to load privacy settings'))
      .finally(() => setLoading(false));
  }, []);

  const handleConsentToggle = async (purpose: string, currentState: boolean, setter: (v: boolean) => void) => {
    const nextState = !currentState;
    setter(nextState);
    try {
      await fetch('/api/consent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          purpose,
          state: nextState ? 'GRANTED' : 'WITHDRAWN',
        }),
      });
      setMessage(`Consent for ${purpose.replace('_', ' ').toLowerCase()} updated.`);
    } catch {
      setter(currentState);
      setMessage('Failed to update consent.');
    }
  };

  const handlePreferenceToggle = async (channel: string, currentState: boolean, setter: (v: boolean) => void) => {
    const nextState = !currentState;
    setter(nextState);
    try {
      await fetch('/api/preferences', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          category: 'REMINDER',
          channel,
          is_enabled: nextState,
        }),
      });
    } catch {
      setter(currentState);
      setMessage('Failed to update channel preference.');
    }
  };

  const handleExportData = async () => {
    setExporting(true);
    setMessage(null);
    try {
      const res = await fetch('/api/privacy/export');
      if (!res.ok) throw new Error('Export failed');
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `devotee-data-archive-${new Date().toISOString().slice(0, 10)}.json`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      setMessage('Data archive downloaded successfully.');
    } catch {
      setMessage('Failed to export data archive.');
    } finally {
      setExporting(false);
    }
  };

  const handleErasure = async () => {
    setErasing(true);
    setMessage(null);
    try {
      const res = await fetch('/api/privacy/erasure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason: erasureReason }),
      });
      if (!res.ok) throw new Error('Erasure failed');
      setShowErasureConfirm(false);
      alert('Your devotee profile has been erased and your account has been closed. You will now be signed out.');
      window.location.href = '/';
    } catch {
      setMessage('Failed to complete erasure request.');
      setErasing(false);
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
      <div className="max-w-3xl mx-auto space-y-8">
        {/* Header */}
        <div className="border-b border-[#EFE3D3] pb-5">
          <div className="inline-block px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-[#EFE3D3] text-[#B74717] mb-2">
            Governance & DPDP
          </div>
          <h1 className="text-3xl font-serif font-bold text-[#2C2033]">Privacy & Consent Center</h1>
          <p className="mt-1 text-sm text-[#2A2421]/80">
            Control your sacred data, consent boundaries, communication channels, and statutory data rights.
          </p>
        </div>

        {message && (
          <div className="p-4 rounded-lg bg-[#EFE3D3]/40 border border-[#EFE3D3] text-[#2C2033] text-sm">
            {message}
          </div>
        )}

        {/* 1. Explicit Purpose Consent */}
        <div className="bg-white rounded-xl p-6 border border-[#EFE3D3] shadow-xs space-y-4">
          <h2 className="text-lg font-serif font-semibold text-[#2C2033] border-b border-[#EFE3D3] pb-2">
            Purpose-Based Consent
          </h2>
          <p className="text-xs text-[#2A2421]/70">
            Spiritual and astronomical data are special-category. Every processing activity requires explicit consent.
          </p>

          <div className="space-y-4 pt-2">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h3 className="text-sm font-semibold text-[#2C2033]">Ritual Reminders & Panchang Math</h3>
                <p className="text-xs text-[#2A2421]/70">
                  Allow computing lunar tithis, shraddha anniversaries, and transmitting devotional alerts.
                </p>
              </div>
              <input
                type="checkbox"
                checked={ritualConsent}
                onChange={() => handleConsentToggle('RITUAL_REMINDERS', ritualConsent, setRitualConsent)}
                className="mt-1 h-5 w-5 text-[#F16A24] rounded border-[#EFE3D3] focus:ring-[#F16A24]"
              />
            </div>

            <div className="flex items-start justify-between gap-4 pt-3 border-t border-[#EFE3D3]/60">
              <div>
                <h3 className="text-sm font-semibold text-[#2C2033]">Anonymous Service Analytics</h3>
                <p className="text-xs text-[#2A2421]/70">
                  Help improve ritual platform performance. Special-category spiritual data is 100% excluded.
                </p>
              </div>
              <input
                type="checkbox"
                checked={analyticsConsent}
                onChange={() => handleConsentToggle('ANALYTICS', analyticsConsent, setAnalyticsConsent)}
                className="mt-1 h-5 w-5 text-[#F16A24] rounded border-[#EFE3D3] focus:ring-[#F16A24]"
              />
            </div>

            <div className="flex items-start justify-between gap-4 pt-3 border-t border-[#EFE3D3]/60">
              <div>
                <h3 className="text-sm font-semibold text-[#2C2033]">Temple & Festival Communications</h3>
                <p className="text-xs text-[#2A2421]/70">
                  Receive notifications regarding upcoming major temple utsavams and holy occasions.
                </p>
              </div>
              <input
                type="checkbox"
                checked={marketingConsent}
                onChange={() => handleConsentToggle('MARKETING', marketingConsent, setMarketingConsent)}
                className="mt-1 h-5 w-5 text-[#F16A24] rounded border-[#EFE3D3] focus:ring-[#F16A24]"
              />
            </div>
          </div>
        </div>

        {/* 2. Channel Matrix for Ritual Reminders */}
        <div className="bg-white rounded-xl p-6 border border-[#EFE3D3] shadow-xs space-y-4">
          <h2 className="text-lg font-serif font-semibold text-[#2C2033] border-b border-[#EFE3D3] pb-2">
            Notification Delivery Channels
          </h2>
          <p className="text-xs text-[#2A2421]/70">
            Choose which verified channels may receive your scheduled ritual reminders:
          </p>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <label className="flex items-center gap-3 p-3 rounded-lg border border-[#EFE3D3] bg-[#FFF7ED]/30 cursor-pointer">
              <input
                type="checkbox"
                checked={remindersWhatsapp}
                onChange={() => handlePreferenceToggle('WHATSAPP', remindersWhatsapp, setRemindersWhatsapp)}
                className="h-4 w-4 text-[#F16A24] rounded border-[#EFE3D3]"
              />
              <span className="text-sm font-medium text-[#2C2033]">WhatsApp</span>
            </label>

            <label className="flex items-center gap-3 p-3 rounded-lg border border-[#EFE3D3] bg-[#FFF7ED]/30 cursor-pointer">
              <input
                type="checkbox"
                checked={remindersEmail}
                onChange={() => handlePreferenceToggle('EMAIL', remindersEmail, setRemindersEmail)}
                className="h-4 w-4 text-[#F16A24] rounded border-[#EFE3D3]"
              />
              <span className="text-sm font-medium text-[#2C2033]">Email</span>
            </label>

            <label className="flex items-center gap-3 p-3 rounded-lg border border-[#EFE3D3] bg-[#FFF7ED]/30 cursor-pointer">
              <input
                type="checkbox"
                checked={remindersSms}
                onChange={() => handlePreferenceToggle('SMS', remindersSms, setRemindersSms)}
                className="h-4 w-4 text-[#F16A24] rounded border-[#EFE3D3]"
              />
              <span className="text-sm font-medium text-[#2C2033]">SMS (DLT)</span>
            </label>
          </div>
        </div>

        {/* 3. Data Portability / Export */}
        <div className="bg-white rounded-xl p-6 border border-[#EFE3D3] shadow-xs space-y-4">
          <h2 className="text-lg font-serif font-semibold text-[#2C2033] border-b border-[#EFE3D3] pb-2">
            Devotee Data Portability (Article 15)
          </h2>
          <p className="text-xs text-[#2A2421]/70">
            Download an exhaustive, machine-readable JSON archive of all your sacred profile data, ancestral lineage,
            ritual reminders, and consent transaction ledger.
          </p>

          <button
            onClick={handleExportData}
            disabled={exporting}
            className="px-5 py-2.5 rounded-lg border border-[#F16A24] text-[#F16A24] hover:bg-[#FFF7ED] font-medium text-sm transition-all disabled:opacity-50 cursor-pointer"
          >
            {exporting ? 'Generating Archive...' : 'Download My Data Archive'}
          </button>
        </div>

        {/* 4. Right to Erasure */}
        <div className="bg-white rounded-xl p-6 border border-[#B74717]/30 shadow-xs space-y-4">
          <h2 className="text-lg font-serif font-semibold text-[#B74717] border-b border-[#EFE3D3] pb-2">
            Right to Erasure & Account Deletion
          </h2>
          <p className="text-xs text-[#2A2421]/70 leading-relaxed">
            Upon requesting erasure, your spiritual profile, family records, reminders, and preferences will be permanently wiped.
            Telemetry is immediately anonymized. Past financial documents, tax invoices, and completed booking references will be
            retained strictly under the statutory <strong>financial_exception</strong> legal retention basis.
          </p>

          {!showErasureConfirm ? (
            <button
              onClick={() => setShowErasureConfirm(true)}
              className="px-5 py-2.5 rounded-lg bg-[#B74717] text-white hover:bg-[#B74717]/90 font-medium text-sm transition-all cursor-pointer"
            >
              Request Account Erasure
            </button>
          ) : (
            <div className="p-4 rounded-lg bg-[#FFF7ED] border border-[#B74717]/40 space-y-3">
              <h3 className="text-sm font-bold text-[#B74717]">Confirm Permanent Account Erasure</h3>
              <p className="text-xs text-[#2A2421]">
                This action is irreversible. All future ritual reminders will cease immediately.
              </p>
              <input
                type="text"
                value={erasureReason}
                onChange={(e) => setErasureReason(e.target.value)}
                placeholder="Reason for erasure (optional)"
                className="block w-full px-3 py-2 border border-[#EFE3D3] rounded-md text-sm bg-white"
              />
              <div className="flex gap-3">
                <button
                  onClick={handleErasure}
                  disabled={erasing}
                  className="px-4 py-2 rounded-lg bg-[#B74717] text-white text-xs font-bold hover:bg-[#B74717]/90"
                >
                  {erasing ? 'Erasing Data...' : 'Confirm & Erase My Account'}
                </button>
                <button
                  onClick={() => setShowErasureConfirm(false)}
                  className="px-4 py-2 rounded-lg border border-[#EFE3D3] bg-white text-xs font-medium text-[#2C2033]"
                >
                  Cancel
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

