'use client';

import { useState, useEffect } from 'react';
import { CANONICAL_GOTRAS, CANONICAL_NAKSHATRAS } from '@navdhya/validation';
import { CustomerProfileViewModel, ProfileUpdateInput, validateProfileUpdate, DeactivateInput } from '@/application/profile/customer-profile';

interface CustomerProfilePageProps {
  profile: CustomerProfileViewModel | null;
  error?: string;
}

export function CustomerProfilePage({ profile, error }: CustomerProfilePageProps) {
  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState<ProfileUpdateInput>({});
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState('');
  const [showDeactivateModal, setShowDeactivateModal] = useState(false);
  const [deactivateStep, setDeactivateStep] = useState<'otp' | 'verify'>('otp');
  const [deactivatePhone, setDeactivatePhone] = useState('');
  const [deactivateOtp, setDeactivateOtp] = useState('');
  const [deactivateChallengeId, setDeactivateChallengeId] = useState('');
  const [isDeactivating, setIsDeactivating] = useState(false);
  const [deactivateError, setDeactivateError] = useState('');

  // Initialize form with profile data
  useEffect(() => {
    if (profile) {
      setFormData({
        name: profile.name,
        email: profile.email,
        phone: profile.phone || '',
        city: profile.city || '',
        country: profile.country || '',
        timezone: profile.timezone || '',
        languages: profile.languages || [],
        gotra: profile.gotra || '',
        nakshatra: profile.nakshatra || '',
      });
    }
  }, [profile]);

  const handleInputChange = (field: string, value: string | string[]) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    setFieldErrors(prev => {
      const next = { ...prev };
      delete next[field];
      return next;
    });
    setSaveError('');
  };

  const handleSave = async () => {
    const validation = validateProfileUpdate(formData);
    if (!validation.ok) {
      setFieldErrors(validation.fieldErrors);
      return;
    }

    setIsSaving(true);
    setSaveError('');

    try {
      const res = await fetch('/api/profile', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(validation.input),
      });

      if (!res.ok) {
        const data = await res.json();
        setSaveError(data.error?.message || 'Failed to save');
        return;
      }

      const data = await res.json();
      if (data.data?.profile) {
        // Refresh page to get updated profile
        window.location.reload();
      }
    } catch {
      setSaveError('Failed to save');
    } finally {
      setIsSaving(false);
    }
  };

  const handleRequestDeactivateOtp = async () => {
    if (!deactivatePhone || deactivatePhone.replace(/\D/g, '').length < 10) {
      setDeactivateError('Enter a valid phone number');
      return;
    }

    setIsDeactivating(true);
    setDeactivateError('');

    try {
      const res = await fetch('/api/auth/otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ identity: deactivatePhone, purpose: 'login' }),
      });

      if (!res.ok) {
        const data = await res.json();
        setDeactivateError(data.error?.message || 'Failed to send code');
        return;
      }

      const data = await res.json();
      if (data.data?.challengeId) {
        setDeactivateChallengeId(data.data.challengeId);
        setDeactivateStep('verify');
      }
    } catch {
      setDeactivateError('Failed to send code');
    } finally {
      setIsDeactivating(false);
    }
  };

  const handleDeactivate = async () => {
    if (deactivateOtp.length !== 6) {
      setDeactivateError('Enter complete code');
      return;
    }

    if (!deactivateChallengeId) {
      setDeactivateError('Session expired. Request a new code.');
      return;
    }

    setIsDeactivating(true);
    setDeactivateError('');

    try {
      const res = await fetch('/api/profile/deactivate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ challengeId: deactivateChallengeId, code: deactivateOtp }),
      });

      if (!res.ok) {
        const data = await res.json();
        // Check if MFA is required
        if (res.status === 403 || data.error?.message?.includes('MFA')) {
          setDeactivateError('Multi-factor authentication required. Please enable MFA first.');
        } else {
          setDeactivateError(data.error?.message || 'Deactivation failed');
        }
        return;
      }

      // Success - redirect to home or login
      window.location.href = '/login';
    } catch {
      setDeactivateError('Deactivation failed');
    } finally {
      setIsDeactivating(false);
    }
  };

  if (error) {
    return (
      <main className="p-6">
        <h1 className="text-2xl font-bold mb-4">Profile</h1>
        <p className="text-red-600" role="alert">{error}</p>
        <a href="/" className="text-indigo-600 hover:underline">Go home</a>
      </main>
    );
  }

  if (!profile) {
    return (
      <main className="p-6">
        <h1 className="text-2xl font-bold mb-4">Profile</h1>
        <p>Profile not found</p>
      </main>
    );
  }

  return (
    <main className="p-6 max-w-2xl mx-auto">
      <div className="mb-6">
        <h1 className="text-2xl font-bold">Profile</h1>
        <p className="text-gray-600 mt-1">Manage your account information</p>
      </div>

      {saveError && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
          {saveError}
        </div>
      )}

      <div className="bg-white border border-gray-200 rounded-xl p-6 space-y-6">
        {!isEditing ? (
          <>
            <dl className="space-y-4">
              <div>
                <dt className="text-sm font-medium text-gray-500">Name</dt>
                <dd className="mt-1 text-gray-900">{profile.name}</dd>
              </div>
              <div>
                <dt className="text-sm font-medium text-gray-500">Email</dt>
                <dd className="mt-1 text-gray-900">{profile.email}</dd>
              </div>
              {profile.phone && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Phone</dt>
                  <dd className="mt-1 text-gray-900">{profile.phone}</dd>
                </div>
              )}
              {profile.city && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">City</dt>
                  <dd className="mt-1 text-gray-900">{profile.city}</dd>
                </div>
              )}
              {profile.country && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Country</dt>
                  <dd className="mt-1 text-gray-900">{profile.country}</dd>
                </div>
              )}
              {profile.timezone && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Timezone</dt>
                  <dd className="mt-1 text-gray-900">{profile.timezone}</dd>
                </div>
              )}
              {profile.languages && profile.languages.length > 0 && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Languages</dt>
                  <dd className="mt-1 text-gray-900">{profile.languages.join(', ')}</dd>
                </div>
              )}
              {profile.gotra && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Gotra</dt>
                  <dd className="mt-1 text-gray-900">{profile.gotra}</dd>
                </div>
              )}
              {profile.nakshatra && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Janma Nakshatra</dt>
                  <dd className="mt-1 text-gray-900">{profile.nakshatra}</dd>
                </div>
              )}
              {profile.mfaEnabled !== undefined && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Two-factor authentication</dt>
                  <dd className="mt-1 text-gray-900">
                    {profile.mfaEnabled ? 'Enabled' : 'Disabled'}
                    {profile.enrolledAt && ` (since ${new Date(profile.enrolledAt).toLocaleDateString()})`}
                  </dd>
                </div>
              )}
            </dl>

            <div className="pt-4 border-t border-gray-200 flex flex-wrap gap-3">
              <button
                onClick={() => setIsEditing(true)}
                className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
              >
                Edit Profile
              </button>
              <button
                onClick={() => setShowDeactivateModal(true)}
                className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
              >
                Deactivate Account
              </button>
            </div>
          </>
        ) : (
          <form onSubmit={(e) => { e.preventDefault(); handleSave(); }} className="space-y-4">
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">Name</label>
              <input
                id="name"
                type="text"
                value={formData.name || ''}
                onChange={(e) => handleInputChange('name', e.target.value)}
                className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${fieldErrors.name ? 'border-red-500' : 'border-gray-300'}`}
                disabled={isSaving}
              />
              {fieldErrors.name && <p className="mt-1 text-sm text-red-600">{fieldErrors.name}</p>}
            </div>

            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input
                id="email"
                type="email"
                value={formData.email || ''}
                onChange={(e) => handleInputChange('email', e.target.value)}
                className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${fieldErrors.email ? 'border-red-500' : 'border-gray-300'}`}
                disabled={isSaving}
              />
              {fieldErrors.email && <p className="mt-1 text-sm text-red-600">{fieldErrors.email}</p>}
            </div>

            <div>
              <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
              <input
                id="phone"
                type="tel"
                value={formData.phone || ''}
                onChange={(e) => handleInputChange('phone', e.target.value)}
                className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${fieldErrors.phone ? 'border-red-500' : 'border-gray-300'}`}
                disabled={isSaving}
              />
              {fieldErrors.phone && <p className="mt-1 text-sm text-red-600">{fieldErrors.phone}</p>}
            </div>

            <div>
              <label htmlFor="city" className="block text-sm font-medium text-gray-700 mb-1">City</label>
              <input
                id="city"
                type="text"
                value={formData.city || ''}
                onChange={(e) => handleInputChange('city', e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                disabled={isSaving}
              />
            </div>

            <div>
              <label htmlFor="country" className="block text-sm font-medium text-gray-700 mb-1">Country</label>
              <input
                id="country"
                type="text"
                value={formData.country || ''}
                onChange={(e) => handleInputChange('country', e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                disabled={isSaving}
              />
            </div>

            <div>
              <label htmlFor="timezone" className="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
              <input
                id="timezone"
                type="text"
                value={formData.timezone || ''}
                onChange={(e) => handleInputChange('timezone', e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                disabled={isSaving}
              />
            </div>

            <div>
              <label htmlFor="languages" className="block text-sm font-medium text-gray-700 mb-1">Languages (comma-separated)</label>
              <input
                id="languages"
                type="text"
                value={Array.isArray(formData.languages) ? formData.languages.join(', ') : ''}
                onChange={(e) => handleInputChange('languages', e.target.value.split(',').map(s => s.trim()).filter(Boolean))}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                disabled={isSaving}
              />
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="gotra" className="block text-sm font-medium text-[#2C2033] mb-1">Gotra (Sankalpa)</label>
                <select
                  id="gotra"
                  value={formData.gotra || ''}
                  onChange={(e) => handleInputChange('gotra', e.target.value)}
                  className="w-full px-4 py-3 border border-[#EFE3D3] bg-[#FFF7ED] text-[#2A2421] rounded-lg focus:ring-2 focus:ring-[#F16A24]"
                  disabled={isSaving}
                >
                  <option value="">Select Gotra (optional)</option>
                  {CANONICAL_GOTRAS.map((g) => (
                    <option key={g} value={g}>{g}</option>
                  ))}
                </select>
                {fieldErrors.gotra && <p className="mt-1 text-sm text-[#B74717]">{fieldErrors.gotra}</p>}
              </div>

              <div>
                <label htmlFor="nakshatra" className="block text-sm font-medium text-[#2C2033] mb-1">Janma Nakshatra</label>
                <select
                  id="nakshatra"
                  value={formData.nakshatra || ''}
                  onChange={(e) => handleInputChange('nakshatra', e.target.value)}
                  className="w-full px-4 py-3 border border-[#EFE3D3] bg-[#FFF7ED] text-[#2A2421] rounded-lg focus:ring-2 focus:ring-[#F16A24]"
                  disabled={isSaving}
                >
                  <option value="">Select Nakshatra (optional)</option>
                  {CANONICAL_NAKSHATRAS.map((n) => (
                    <option key={n} value={n}>{n.replace(/_/g, ' ')}</option>
                  ))}
                </select>
                {fieldErrors.nakshatra && <p className="mt-1 text-sm text-[#B74717]">{fieldErrors.nakshatra}</p>}
              </div>
            </div>

            <div className="pt-4 border-t border-gray-200 flex flex-wrap gap-3">
              <button
                type="submit"
                disabled={isSaving}
                className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
              >
                {isSaving ? 'Saving...' : 'Save Changes'}
              </button>
              <button
                type="button"
                onClick={() => { setIsEditing(false); setFieldErrors({}); setSaveError(''); }}
                className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        )}
      </div>

      {/* Deactivate Modal */}
      {showDeactivateModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
          <div className="bg-white rounded-xl p-6 max-w-md w-full space-y-4">
            <h2 className="text-xl font-bold">Deactivate Account</h2>
            <p className="text-gray-600">
              This will deactivate your account. You can reactivate it later by verifying your phone number.
            </p>

            {deactivateError && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {deactivateError}
              </div>
            )}

            {deactivateStep === 'otp' ? (
              <div className="space-y-4">
                <div>
                  <label htmlFor="deactivatePhone" className="block text-sm font-medium text-gray-700 mb-1">
                    Phone Number
                  </label>
                  <input
                    id="deactivatePhone"
                    type="tel"
                    value={deactivatePhone}
                    onChange={(e) => setDeactivatePhone(e.target.value)}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Enter your phone number"
                    disabled={isDeactivating}
                  />
                </div>
                <button
                  onClick={handleRequestDeactivateOtp}
                  disabled={isDeactivating}
                  className="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                >
                  {isDeactivating ? 'Sending code...' : 'Send Verification Code'}
                </button>
              </div>
            ) : (
              <div className="space-y-4">
                <p className="text-sm text-gray-600">Enter the 6-digit code sent to your phone</p>
                <input
                  type="text"
                  value={deactivateOtp}
                  onChange={(e) => setDeactivateOtp(e.target.value)}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl tracking-widest"
                  placeholder="• • • • • •"
                  maxLength={6}
                  disabled={isDeactivating}
                  autoComplete="one-time-code"
                />
                <button
                  onClick={handleDeactivate}
                  disabled={isDeactivating}
                  className="w-full py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors"
                >
                  {isDeactivating ? 'Deactivating...' : 'Deactivate Account'}
                </button>
              </div>
            )}

            <button
              onClick={() => { setShowDeactivateModal(false); setDeactivateStep('otp'); setDeactivateOtp(''); setDeactivatePhone(''); setDeactivateChallengeId(''); setDeactivateError(''); }}
              className="w-full text-sm text-gray-600 hover:text-gray-800"
            >
              Cancel
            </button>
          </div>
        </div>
      )}
    </main>
  );
}