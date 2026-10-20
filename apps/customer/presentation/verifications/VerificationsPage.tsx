'use client';

import { useState } from 'react';
import { CustomerVerification, StartVerificationInput, validateStartVerification, ConfirmVerificationInput, validateConfirmVerification } from '@/application/verifications/verifications';

interface VerificationsPageViewProps {
  verifications: CustomerVerification[];
  error?: string;
}

export function VerificationsPageView({ verifications, error }: VerificationsPageViewProps) {
  const [showAddModal, setShowAddModal] = useState(false);
  const [formData, setFormData] = useState<StartVerificationInput>({ type: 'phone', contact: '' });
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [confirmData, setConfirmData] = useState<ConfirmVerificationInput>({ id: '', code: '' });
  const [confirmErrors, setConfirmErrors] = useState<Record<string, string>>({});
  const [isConfirming, setIsConfirming] = useState(false);
  const [confirmError, setConfirmError] = useState('');

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    setFieldErrors(prev => {
      const next = { ...prev };
      delete next[field];
      return next;
    });
  };

  const handleConfirmInputChange = (field: string, value: string) => {
    setConfirmData(prev => ({ ...prev, [field]: value }));
    setConfirmErrors(prev => {
      const next = { ...prev };
      delete next[field];
      return next;
    });
    setConfirmError('');
  };

  const handleAddSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const validation = validateStartVerification(formData);
    if (!validation.ok) {
      setFieldErrors(validation.fieldErrors || {});
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await fetch('/api/verifications', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });

      if (!res.ok) {
        const data = await res.json();
        if (data.error?.code === 'CONFLICT') {
          setFieldErrors({ contact: data.error?.message || 'Already verified' });
        } else {
          setFieldErrors({ general: data.error?.message || 'Failed to start verification' });
        }
        return;
      }

      setShowAddModal(false);
      setFormData({ type: 'phone', contact: '' });
      // The 202 carries the challenge id; open confirmation immediately
      // instead of reloading and losing it (the status endpoint only
      // reports already-confirmed contacts).
      const payload = await res.json().catch(() => null);
      const challengeId = payload?.data?.challenge_id ?? payload?.challenge_id;
      if (!challengeId) {
        setFieldErrors({ general: 'Verification could not be started. Please try again.' });
        return;
      }
      setConfirmData({ id: challengeId, code: '' });
      setConfirmErrors({});
      setConfirmError('');
      setShowConfirmModal(true);
    } catch {
      setFieldErrors({ general: 'Failed to start verification' });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleConfirmSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const validation = validateConfirmVerification(confirmData);
    if (!validation.ok) {
      setConfirmErrors(validation.fieldErrors || {});
      return;
    }

    setIsConfirming(true);
    setConfirmError('');

    try {
      const res = await fetch('/api/verifications/confirm', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(confirmData),
      });

      if (!res.ok) {
        const data = await res.json();
        setConfirmError(data.error?.message || 'Invalid code');
        return;
      }

      setShowConfirmModal(false);
      setConfirmData({ id: '', code: '' });
      window.location.reload();
    } catch {
      setConfirmError('Verification failed');
    } finally {
      setIsConfirming(false);
    }
  };

  const openConfirmModal = (verification: CustomerVerification) => {
    setConfirmData({ id: verification.id, code: '' });
    setConfirmErrors({});
    setConfirmError('');
    setShowConfirmModal(true);
  };

  if (error) {
    return (
      <main className="p-6">
        <h1 className="text-2xl font-bold mb-4">Verifications</h1>
        <p className="text-red-600" role="alert">{error}</p>
        <a href="/" className="text-indigo-600 hover:underline">Go home</a>
      </main>
    );
  }

  return (
    <main className="p-6 max-w-2xl mx-auto">
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold">Verifications</h1>
        <button
          onClick={() => setShowAddModal(true)}
          className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
          Add Verification
        </button>
      </div>

      {verifications.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-600">No verifications yet.</p>
          <button
            onClick={() => setShowAddModal(true)}
            className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
          >
            Add Your First Verification
          </button>
        </div>
      ) : (
        <div className="space-y-3">
          {verifications.map((v) => (
            <div
              key={v.id}
              className="p-4 bg-white border border-gray-200 rounded-xl flex items-center justify-between"
            >
              <div className="flex items-center gap-3">
                <div className="p-2 bg-indigo-100 rounded-lg">
                  {v.type === 'phone' ? (
                    <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  ) : (
                    <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  )}
                </div>
                <div>
                  <p className="font-medium text-gray-900">{v.contact}</p>
                  <p className="text-sm text-gray-500 capitalize">{v.type}</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                  v.status === 'verified' ? 'bg-green-100 text-green-800' :
                  v.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-red-100 text-red-800'
                }`}>
                  {v.status}
                </span>
                {v.status === 'pending' && v.id.length > 0 && (
                  <button
                    onClick={() => openConfirmModal(v)}
                    className="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    Confirm
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Add Verification Modal */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
          <div className="bg-white rounded-xl p-6 max-w-md w-full space-y-4">
            <h2 className="text-xl font-bold">Add Verification</h2>
            <form onSubmit={handleAddSubmit} className="space-y-4">
              {fieldErrors.general && (
                <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                  {fieldErrors.general}
                </div>
              )}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                  value={formData.type}
                  onChange={(e) => handleInputChange('type', e.target.value)}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="phone">Phone</option>
                  <option value="email">Email</option>
                </select>
              </div>
              <div>
                <label htmlFor="contact" className="block text-sm font-medium text-gray-700 mb-1">
                  {formData.type === 'phone' ? 'Phone Number' : 'Email Address'}
                </label>
                <input
                  id="contact"
                  type={formData.type === 'phone' ? 'tel' : 'email'}
                  value={formData.contact}
                  onChange={(e) => handleInputChange('contact', e.target.value)}
                  className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${fieldErrors.contact ? 'border-red-500' : 'border-gray-300'}`}
                  placeholder={formData.type === 'phone' ? 'Enter phone number' : 'Enter email address'}
                  disabled={isSubmitting}
                />
                {fieldErrors.contact && <p className="mt-1 text-sm text-red-600">{fieldErrors.contact}</p>}
              </div>
              <div className="pt-4 border-t border-gray-200 flex gap-3">
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="flex-1 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                >
                  {isSubmitting ? 'Sending...' : 'Send Code'}
                </button>
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="flex-1 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Confirm Verification Modal */}
      {showConfirmModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
          <div className="bg-white rounded-xl p-6 max-w-md w-full space-y-4">
            <h2 className="text-xl font-bold">Confirm Verification</h2>
            <p className="text-gray-600">Enter the 6-digit code sent to your contact.</p>

            {confirmError && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {confirmError}
              </div>
            )}

            <form onSubmit={handleConfirmSubmit} className="space-y-4">
              <input
                type="text"
                value={confirmData.code}
                onChange={(e) => handleConfirmInputChange('code', e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl tracking-widest"
                placeholder="• • • • • •"
                maxLength={6}
                disabled={isConfirming}
                autoComplete="one-time-code"
              />
              {confirmErrors.code && <p className="mt-1 text-sm text-red-600">{confirmErrors.code}</p>}

              <div className="pt-4 border-t border-gray-200 flex gap-3">
                <button
                  type="submit"
                  disabled={isConfirming}
                  className="flex-1 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                >
                  {isConfirming ? 'Verifying...' : 'Confirm'}
                </button>
                <button
                  type="button"
                  onClick={() => { setShowConfirmModal(false); setConfirmData({ id: '', code: '' }); setConfirmErrors({}); setConfirmError(''); }}
                  className="flex-1 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </main>
  );
}