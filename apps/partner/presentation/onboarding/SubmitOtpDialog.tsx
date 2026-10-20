'use client';

import React, { useState, useEffect, useMemo } from 'react';
import { OtpInput } from './OtpInput';
import { createOtpClient } from '@navdhya/otp/client';
import {
  getWidget,
  sendOtp,
  verifyOtp,
  retryOtp,
  OtpWidgetError,
} from '@navdhya/otp/widget';
import { useResendCountdown, resendLabel } from '@navdhya/otp/resend';
import { onboardingString, type OnboardingStringKey } from './strings';

export interface SubmitOtpDialogProps {
  applicationId: string;
  contactPhone: string;
  locale: string;
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export function SubmitOtpDialog({
  applicationId,
  contactPhone,
  locale,
  isOpen,
  onClose,
  onSuccess,
}: SubmitOtpDialogProps) {
  const [otp, setOtp] = useState('');
  const [challengeId, setChallengeId] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isInitializing, setIsInitializing] = useState(false);
  const [error, setError] = useState('');
  const [nextAllowedAt, setNextAllowedAt] = useState<string | null>(null);
  const [resendsLeft, setResendsLeft] = useState(3);

  const otpClient = useMemo(() => createOtpClient({ basePath: '/api/auth' }), []);
  const { secondsLeft, canResend, exhausted: resendExhausted } = useResendCountdown(nextAllowedAt);
  const t = (key: OnboardingStringKey) => onboardingString(locale, key);

  // When dialog opens, initiate submission and send OTP via widget
  useEffect(() => {
    if (!isOpen) {
      setOtp('');
      setError('');
      setChallengeId('');
      setNextAllowedAt(null);
      return;
    }

    let mounted = true;
    setIsInitializing(true);
    setError('');

    async function initSubmit() {
      try {
        // 1. Begin onboarding submission (generates OTP challenge row bound to partner_submit)
        const submitRes = await fetch(`/api/onboarding/${encodeURIComponent(applicationId)}/submit`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
        });

        if (!submitRes.ok) {
          const errData = await submitRes.json().catch(() => null);
          throw new Error(errData?.error?.message || 'Failed to initiate submission');
        }

        const submitData = await submitRes.json();
        const cid = submitData?.data?.challenge_id || submitData?.data?.challengeId;
        if (!cid) throw new Error('No challenge received');

        if (mounted) setChallengeId(cid);

        // 2. Load widget config & initialize SDK
        const config = await otpClient.loadConfig();
        await getWidget({ widgetId: config.widget_id, tokenAuth: config.token_auth });

        // 3. Send OTP to contact phone via widget
        await sendOtp(contactPhone);

        if (mounted) {
          const delay = config.resend?.delays_seconds?.[0] ?? 60;
          setNextAllowedAt(new Date(Date.now() + delay * 1000).toISOString());
        }
      } catch (err: any) {
        if (mounted) {
          if (err instanceof OtpWidgetError) {
            setError(err.message || 'Verification service error.');
          } else {
            setError(err.message || 'Failed to send verification code.');
          }
        }
      } finally {
        if (mounted) setIsInitializing(false);
      }
    }

    void initSubmit();

    return () => {
      mounted = false;
    };
  }, [isOpen, applicationId, contactPhone, otpClient]);

  const handleConfirm = async (e: React.FormEvent) => {
    e.preventDefault();
    if (otp.length !== 6) {
      setError('Please enter the complete 6-digit code');
      return;
    }

    setIsLoading(true);
    setError('');

    try {
      // 1. Judge code in widget SDK -> returns JWT access token
      let token: string;
      try {
        token = await verifyOtp(otp);
      } catch (widgetErr: any) {
        if (widgetErr instanceof OtpWidgetError) {
          setError('Invalid or expired code.');
          setIsLoading(false);
          return;
        }
        throw widgetErr;
      }

      // 2. Confirm submit via BFF proxy
      const confirmRes = await fetch(`/api/onboarding/${encodeURIComponent(applicationId)}/submit/confirm`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          challenge_id: challengeId,
          token,
        }),
      });

      if (!confirmRes.ok) {
        const errData = await confirmRes.json().catch(() => null);
        throw new Error(errData?.error?.message || 'Submission confirmation failed');
      }

      onSuccess();
    } catch (err: any) {
      setError(err?.message || 'Verification failed');
    } finally {
      setIsLoading(false);
    }
  };

  const handleResend = async () => {
    if (!canResend || resendsLeft <= 0 || !challengeId) return;

    setIsLoading(true);
    setError('');

    try {
      const resendRes = await fetch(`/api/onboarding/${encodeURIComponent(applicationId)}/submit/resend`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ challenge_id: challengeId }),
      });

      if (!resendRes.ok) {
        const errData = await resendRes.json().catch(() => null);
        throw new Error(errData?.error?.message || 'Failed to resend code');
      }

      const resendData = await resendRes.json();
      const channel = resendData?.data?.channel ?? '11';

      await retryOtp(channel);

      if (resendData?.data?.resends_left !== undefined) {
        setResendsLeft(resendData.data.resends_left);
      }
      setNextAllowedAt(resendData?.data?.next_allowed_at ?? null);
      setOtp('');
    } catch (err: any) {
      setError(err?.message || 'Failed to resend code');
    } finally {
      setIsLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        backgroundColor: 'rgba(0, 0, 0, 0.5)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 50,
      }}
    >
      <div
        style={{
          backgroundColor: '#fff',
          borderRadius: '12px',
          padding: '28px',
          maxWidth: '440px',
          width: '90%',
          boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1)',
        }}
      >
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
          <h2 style={{ fontSize: '18px', fontWeight: '700', margin: 0 }}>{t('submitOtp.confirmTitle')}</h2>
          <button
            type="button"
            onClick={onClose}
            disabled={isLoading || isInitializing}
            style={{ background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer', color: '#666' }}
          >
            ×
          </button>
        </div>

        <p style={{ fontSize: '14px', color: '#666', marginBottom: '20px' }}>
          {t('submitOtp.codeSentTo')} <strong>{contactPhone}</strong>. {t('submitOtp.enterCodeBelow')}
        </p>

        {error && (
          <div
            style={{
              padding: '10px 14px',
              backgroundColor: '#fef2f2',
              border: '1px solid #fecaca',
              borderRadius: '8px',
              color: '#dc2626',
              fontSize: '13px',
              marginBottom: '16px',
            }}
          >
            {error}
          </div>
        )}

        {isInitializing ? (
          <div style={{ textAlign: 'center', padding: '24px 0', color: '#666' }}>
            <p style={{ margin: 0 }}>{t('submitOtp.sendingCode')}</p>
          </div>
        ) : (
          <form onSubmit={handleConfirm}>
            <div style={{ marginBottom: '20px' }}>
              <OtpInput value={otp} onChange={setOtp} disabled={isLoading} />
            </div>

            <button
              type="submit"
              disabled={isLoading || otp.length !== 6}
              style={{
                width: '100%',
                padding: '12px',
                backgroundColor: '#2563eb',
                color: '#fff',
                fontWeight: '600',
                border: 'none',
                borderRadius: '8px',
                cursor: isLoading || otp.length !== 6 ? 'not-allowed' : 'pointer',
                opacity: isLoading || otp.length !== 6 ? 0.6 : 1,
                marginBottom: '12px',
              }}
            >
              {isLoading ? t('submitOtp.verifying') : t('submitOtp.confirmAndSubmit')}
            </button>

            <div style={{ textAlign: 'center' }}>
              {resendsLeft > 0 && !resendExhausted ? (
                secondsLeft !== null && secondsLeft > 0 ? (
                  <span style={{ fontSize: '13px', color: '#888' }}>
                    {t('submitOtp.resendIn')} {resendLabel(secondsLeft)}
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={handleResend}
                    disabled={isLoading || !canResend}
                    style={{
                      background: 'none',
                      border: 'none',
                      color: '#2563eb',
                      fontWeight: '600',
                      fontSize: '13px',
                      cursor: 'pointer',
                    }}
                  >
                    {t('submitOtp.resendCode')}
                  </button>
                )
              ) : (
                <span style={{ fontSize: '12px', color: '#888' }}>
                  {t('submitOtp.maxResends')}
                </span>
              )}
            </div>
          </form>
        )}
      </div>
    </div>
  );
}
