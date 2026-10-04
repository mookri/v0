'use client';

import { useState } from 'react';
import { customerSettingsSections, KNOWN_SETTINGS_KEYS, validateSettingsUpdate } from '@/application/settings/settings';
import type { SettingsSection } from '@/application/settings/settings';

interface SettingsPageViewProps {
  sections: SettingsSection[];
  values: Record<string, unknown>;
}

export function SettingsPageView({ sections, values }: SettingsPageViewProps) {
  const [formData, setFormData] = useState<Record<string, unknown>>(values);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState('');
  const [saveSuccess, setSaveSuccess] = useState(false);

  const handleInputChange = (key: string, value: string | boolean) => {
    setFormData(prev => ({ ...prev, [key]: value }));
    setFieldErrors(prev => {
      const next = { ...prev };
      delete next[key];
      return next;
    });
    setSaveError('');
    setSaveSuccess(false);
  };

  const handleSave = async () => {
    const validation = validateSettingsUpdate(formData);
    if (!validation.ok) {
      setFieldErrors(validation.fieldErrors);
      return;
    }

    setIsSaving(true);
    setSaveError('');
    setSaveSuccess(false);

    try {
      const res = await fetch('/api/settings', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(validation.values),
      });

      if (!res.ok) {
        const data = await res.json();
        setSaveError(data.error?.message || 'Failed to save settings');
        return;
      }

      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 3000);
    } catch {
      setSaveError('Failed to save settings');
    } finally {
      setIsSaving(false);
    }
  };

  const getInputComponent = (key: string) => {
    const value = formData[key];

    if (typeof value === 'boolean') {
      return (
        <label className="flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            checked={value}
            onChange={(e) => handleInputChange(key, e.target.checked)}
            className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
          />
          <span className="text-gray-700 font-medium">Enabled</span>
        </label>
      );
    }

    return (
      <input
        type="text"
        value={String(value ?? '')}
        onChange={(e) => handleInputChange(key, e.target.value)}
        className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${fieldErrors[key] ? 'border-red-500' : 'border-gray-300'}`}
        placeholder={`Enter ${key.replace(/_/g, ' ')}`}
      />
    );
  };

  return (
    <main className="p-6 max-w-2xl mx-auto">
      <h1 className="text-2xl font-bold mb-6">Settings</h1>

      {saveError && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm" role="alert">
          {saveError}
        </div>
      )}

      {saveSuccess && (
        <div className="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm" role="status">
          Settings saved successfully
        </div>
      )}

      <div className="bg-white border border-gray-200 rounded-xl p-6 space-y-8">
        {sections.map((section) => (
          <section key={section.id} className="space-y-4">
            <h2 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
              {section.title}
            </h2>
            <div className="space-y-4">
              {KNOWN_SETTINGS_KEYS
                .filter(k => {
                  // Group keys by section (simplified mapping)
                  const notificationKeys = ['email_notifications', 'push_notifications', 'sms_notifications', 'marketing_emails', 'security_alerts'];
                  const i18nKeys = ['locale', 'timezone', 'currency'];
                  const identityKeys = ['two_factor_enabled'];
                  const userKeys = ['theme'];

                  switch (section.id) {
                    case 'notifications':
                      return notificationKeys.includes(k);
                    case 'language':
                      return i18nKeys.includes(k);
                    case 'security':
                      return identityKeys.includes(k);
                    case 'profile':
                      return userKeys.includes(k);
                    default:
                      return false;
                  }
                })
                .map((key) => (
                  <div key={key} className="space-y-1">
                    <label htmlFor={key} className="block text-sm font-medium text-gray-700">
                      {key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </label>
                    {getInputComponent(key)}
                    {fieldErrors[key] && (
                      <p className="text-sm text-red-600">{fieldErrors[key]}</p>
                    )}
                  </div>
                ))}
            </div>
          </section>
        ))}

        <div className="pt-6 border-t border-gray-200 flex gap-3">
          <button
            onClick={handleSave}
            disabled={isSaving}
            className="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {isSaving ? 'Saving...' : 'Save Settings'}
          </button>
        </div>
      </div>
    </main>
  );
}