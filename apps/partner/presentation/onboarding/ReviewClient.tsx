'use client';

import React, { useState } from 'react';
import { SubmitOtpDialog } from './SubmitOtpDialog';
import { onboardingString, type OnboardingStringKey } from './strings';

export interface ReviewClientProps {
  applicationId: string;
  contactPhone: string;
  track: string;
  evidence: Record<string, any>;
  locale: string;
}

export function ReviewClient({
  applicationId,
  contactPhone,
  track,
  evidence,
  locale,
}: ReviewClientProps) {
  const [showOtpDialog, setShowOtpDialog] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const t = (key: OnboardingStringKey) => onboardingString(locale, key);

  if (isSubmitted) {
    return (
      <div style={{ padding: '24px', textAlign: 'center', backgroundColor: '#f0fdf4', borderRadius: '8px' }}>
        <h2 style={{ color: '#16a34a', margin: '0 0 12px 0' }}>{t('review.applicationSubmitted')}</h2>
        <p style={{ color: '#374151', margin: '0 0 16px 0' }}>
          {t('review.submissionBody')}
        </p>
        <a href={`/onboarding/${applicationId}?hl=${locale}`} style={{ color: '#2563eb', fontWeight: '600' }}>
          {t('review.backToOverview')}
        </a>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: '640px' }}>
      <div style={{ backgroundColor: '#fff', border: '1px solid #e5e7eb', borderRadius: '8px', padding: '24px', marginBottom: '24px' }}>
        <h2 style={{ fontSize: '18px', fontWeight: '600', marginBottom: '16px' }}>{t('review.summaryTitle')} ({track})</h2>
        <div style={{ fontSize: '14px', color: '#4b5563', display: 'flex', flexDirection: 'column', gap: '8px' }}>
          <p style={{ margin: 0 }}><strong>{t('review.contactPhone')}</strong> {contactPhone}</p>
          {evidence['identity']?.name && (
            <p style={{ margin: 0 }}><strong>{t('review.applicantName')}</strong> {String(evidence['identity'].name)}</p>
          )}
          {evidence['business']?.gstin && (
            <p style={{ margin: 0 }}><strong>{t('review.gstin')}</strong> {String(evidence['business'].gstin)}</p>
          )}
        </div>
      </div>

      <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
        <button
          type="button"
          onClick={() => setShowOtpDialog(true)}
          style={{
            padding: '12px 24px',
            backgroundColor: '#2563eb',
            color: '#fff',
            border: 'none',
            borderRadius: '6px',
            fontWeight: '600',
            cursor: 'pointer',
          }}
        >
          {t('review.submitApplication')}
        </button>
        <a
          href={`/onboarding/${applicationId}?hl=${locale}`}
          style={{ color: '#6b7280', fontSize: '14px', textDecoration: 'none' }}
        >
          {t('review.back')}
        </a>
      </div>

      <SubmitOtpDialog
        applicationId={applicationId}
        contactPhone={contactPhone}
        locale={locale}
        isOpen={showOtpDialog}
        onClose={() => setShowOtpDialog(false)}
        onSuccess={() => {
          setShowOtpDialog(false);
          setIsSubmitted(true);
        }}
      />
    </div>
  );
}
