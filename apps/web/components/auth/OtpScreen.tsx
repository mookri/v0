'use client';

import React, { useEffect, useState, useMemo } from 'react';
import { motion } from 'framer-motion';
import { OtpInput } from './OtpInput';
import { PrimaryButton } from './AuthButtons';
import { AuthHeader } from './AuthHeader';
import type { AuthState } from './UnifiedAuthConsole';
import { getCountryByCode } from '@/lib/auth/countries';
import {
  authPost,
  getChallenge,
  setChallenge,
  clearChallenge,
  toE164,
  newIdempotencyKey,
  AuthRequestError,
} from '@/lib/auth/auth-client';
import { createOtpClient } from '@navdhya/otp/client';
import {
  getWidget,
  sendOtp,
  verifyOtp,
  retryOtp,
  OtpWidgetError,
} from '@navdhya/otp/widget';
import { useResendCountdown, resendLabel } from '@navdhya/otp/resend';

const MAX_ATTEMPTS = 3;

interface OtpScreenProps {
  phone: string;
  countryCode: string;
  otpSentReason: 'LOGIN' | 'SIGNUP';
  transitionTo: (state: AuthState) => void;
  animationVariants: any;
}

export function OtpScreen({
  phone,
  countryCode,
  otpSentReason,
  transitionTo,
  animationVariants,
}: OtpScreenProps) {
  const [otp, setOtp] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [generalError, setGeneralError] = useState('');
  const [attempts, setAttempts] = useState(0);
  const [lockedUntil, setLockedUntil] = useState<number | null>(null);
  const [exhausted, setExhausted] = useState(false);

  // Two-phase MFA / TOTP step-up state
  const [showTotp, setShowTotp] = useState(false);
  const [totpInput, setTotpInput] = useState('');

  // Resend schedule state (compounding 60s → 180s → 540s, max 3)
  const [resendsLeft, setResendsLeft] = useState(3);
  const [nextAllowedAt, setNextAllowedAt] = useState<string | null>(null);

  const dialCode = getCountryByCode(countryCode).dialCode;
  const targetPhone = useMemo(() => toE164(countryCode, phone), [countryCode, phone]);
  const otpClient = useMemo(() => createOtpClient({ basePath: '/api/auth' }), []);

  const { secondsLeft, canResend, exhausted: resendExhausted } = useResendCountdown(nextAllowedAt);

  const clearError = (field: string) => {
    setErrors((prev) => {
      const next = { ...prev };
      delete next[field];
      return next;
    });
  };

  // Mount: bootstrap MSG91 widget & send initial OTP
  useEffect(() => {
    let mounted = true;
    const challenge = getChallenge();
    if (!challenge) {
      setGeneralError('Session expired. Please request a new code.');
      return;
    }

    async function init() {
      try {
        const config = await otpClient.loadConfig();
        await getWidget({ widgetId: config.widget_id, tokenAuth: config.token_auth });
        await sendOtp(targetPhone);
        if (mounted) {
          // Initial send schedule: first resend unlocked after 60s
          const delay = config.resend?.delays_seconds?.[0] ?? 60;
          setNextAllowedAt(new Date(Date.now() + delay * 1000).toISOString());
        }
      } catch (err: any) {
        if (mounted) {
          if (err instanceof OtpWidgetError && err.kind === 'unavailable') {
            setGeneralError('Verification service is temporarily unavailable. Please try again.');
          } else {
            setGeneralError(err?.message || 'Failed to send verification code.');
          }
        }
      }
    }

    void init();

    return () => {
      mounted = false;
    };
  }, [otpClient, targetPhone]);

  const handleVerifyOtp = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();

    if (lockedUntil && Date.now() < lockedUntil) {
      const waitSec = Math.ceil((lockedUntil - Date.now()) / 1000);
      setGeneralError(`Too many attempts. Try again in ${waitSec}s.`);
      return;
    }

    if (exhausted) {
      setGeneralError('Too many failed attempts. Please request a new code.');
      return;
    }

    const challenge = getChallenge();
    if (!challenge) {
      setGeneralError('Session expired. Please request a new code.');
      clearChallenge();
      return;
    }

    // Phase 2: TOTP second-factor verification
    if (showTotp) {
      if (totpInput.length < 6) {
        setErrors({ totp: 'Enter complete code' });
        return;
      }

      setIsLoading(true);
      setGeneralError('');

      try {
        await authPost('/api/auth/login', {
          challengeId: challenge.id,
          totpCode: totpInput,
        });
        clearChallenge();
        transitionTo('SUCCESS');
      } catch (err: any) {
        if (err.status === 429 || err.code === 'RATE_LIMITED') {
          const retryAfter = err.retryAfter ?? 60;
          setLockedUntil(Date.now() + retryAfter * 1000);
          setGeneralError(err.message || 'Too many attempts.');
        } else {
          setGeneralError(err.message || 'Invalid authenticator code.');
        }
      } finally {
        setIsLoading(false);
      }
      return;
    }

    // Phase 1: MSG91 Widget OTP verification
    if (otp.length !== 6) {
      setErrors({ otp: 'Enter complete code' });
      return;
    }

    setIsLoading(true);
    setGeneralError('');

    try {
      // 1. Verify code against MSG91 widget → returns JWT access token
      let token: string;
      try {
        token = await verifyOtp(otp);
      } catch (widgetErr: any) {
        if (widgetErr instanceof OtpWidgetError) {
          setGeneralError('Invalid or expired code.');
          setIsLoading(false);
          return;
        }
        throw widgetErr;
      }

      // 2. Redeem JWT token on BFF
      const endpoint = otpSentReason === 'LOGIN' ? '/api/auth/login' : '/api/auth/verify';
      const res = await authPost<{ data?: { status?: string; object?: string } }>(
        endpoint,
        {
          challengeId: challenge.id,
          token,
        },
      );

      // 202 MFA required: phase boundary held
      if (res.data?.data?.status === 'mfa_required' || res.data?.data?.object === 'otp_challenge') {
        setShowTotp(true);
        setGeneralError('Please enter the 6-digit code from your authenticator app to complete sign in.');
        return;
      }

      clearChallenge();
      transitionTo('SUCCESS');
    } catch (err: any) {
      if (err.status === 429 || err.code === 'RATE_LIMITED') {
        const retryAfter = err.retryAfter ?? 60;
        setLockedUntil(Date.now() + retryAfter * 1000);
        setGeneralError(err.message || 'Too many requests.');
      } else if (err.status === 401) {
        if (otpSentReason === 'LOGIN' && !showTotp) {
          // If server 401 indicates step-up or token mismatch
          setShowTotp(true);
          setGeneralError('The code was rejected. If your account uses an authenticator app, enter its code below.');
        } else {
          const next = attempts + 1;
          setAttempts(next);
          if (next >= MAX_ATTEMPTS) {
            setExhausted(true);
            clearChallenge();
            setGeneralError('Too many failed attempts. Please request a new code.');
          } else {
            setGeneralError(`${err.message || 'Invalid or expired code.'} (${MAX_ATTEMPTS - next} attempts remaining)`);
          }
        }
      } else {
        setGeneralError(err.message || 'Invalid code');
      }
    } finally {
      setIsLoading(false);
    }
  };

  // Server-gated resend (compounding 60s → 180s → 540s)
  const handleResend = async () => {
    const challenge = getChallenge();
    if (!challenge) {
      setGeneralError('Session expired. Please request a new code.');
      return;
    }

    if (!canResend || resendsLeft <= 0) return;

    setIsLoading(true);
    setGeneralError('');

    try {
      const res = await authPost<{
        data?: {
          channel?: string;
          channels?: string[];
          resends_left?: number;
          next_allowed_at?: string | null;
        };
      }>('/api/auth/otp/resend', { challengeId: challenge.id });

      const resendData = res.data?.data;
      const channel = resendData?.channel ?? '11';

      // Call widget retryOtp with the channel granted by Laravel
      await retryOtp(channel);

      if (resendData?.resends_left !== undefined) {
        setResendsLeft(resendData.resends_left);
      }
      setNextAllowedAt(resendData?.next_allowed_at ?? null);
      setOtp('');
      setAttempts(0);
      setExhausted(false);
    } catch (err: any) {
      if (err.status === 429 || err.code === 'RATE_LIMITED') {
        const retryAfter = err.retryAfter ?? 60;
        setNextAllowedAt(new Date(Date.now() + retryAfter * 1000).toISOString());
        setGeneralError(err.message || `Too many resends. Please wait ${retryAfter}s.`);
      } else {
        setGeneralError(err.message || 'Failed to resend code.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  // Fresh-challenge retry arm after resends are spent
  const handleFreshChallenge = async () => {
    setIsLoading(true);
    setGeneralError('');

    try {
      const purpose = otpSentReason === 'LOGIN' ? 'login' : 'signup';
      const endpoint = purpose === 'login' ? '/api/auth/otp' : '/api/auth/signup';
      const body =
        purpose === 'login'
          ? { identity: targetPhone, purpose: 'login' }
          : { phone: targetPhone, name: phone };

      const res = await authPost<{ data?: { challengeId?: string; expiresIn?: number } }>(
        endpoint,
        body,
        { idempotencyKey: newIdempotencyKey() },
      );

      const challengeId = res.data?.data?.challengeId;
      if (!challengeId) {
        setGeneralError('Failed to create new challenge.');
        return;
      }

      setChallenge(challengeId, purpose, res.data?.data?.expiresIn ?? 1200);
      await sendOtp(targetPhone);

      setResendsLeft(3);
      setAttempts(0);
      setExhausted(false);
      setNextAllowedAt(new Date(Date.now() + 60_000).toISOString());
      setOtp('');
      setShowTotp(false);
      setTotpInput('');
    } catch (err: any) {
      if (err.status === 429 || err.code === 'RATE_LIMITED') {
        const retryAfter = err.retryAfter ?? 600;
        setGeneralError(`Too many code requests. Please try again in ${Math.ceil(retryAfter / 60)} minutes.`);
      } else {
        setGeneralError(err.message || 'Failed to request fresh code.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <motion.div
      key="otp"
      variants={animationVariants}
      initial="initial"
      animate="animate"
      exit="exit"
      className="w-full"
    >
      <div className="space-y-4">
        {/* Back */}
        <button
          type="button"
          onClick={() => {
            transitionTo(otpSentReason === 'LOGIN' ? 'LOGIN' : 'SIGNUP');
          }}
          className="
            group
            flex
            items-center
            gap-1
            text-[10px]
            font-bold
            tracking-wide
            uppercase
            text-[#7A7A7A]
            hover:text-[#111]
            transition-colors
          "
        >
          <svg
            className="w-3 h-3 transition-transform group-hover:-translate-x-0.5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={2.8}
          >
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Back
        </button>

        {/* Header */}
        <AuthHeader
          eyebrow="Verification"
          title="Confirm Code"
          subtitle={`We sent a 6-digit verification code to +${dialCode} ${phone}`}
        />

        {/* Form */}
        <form onSubmit={handleVerifyOtp} className="space-y-3">
          {generalError && (
            <div className="mb-2 p-3 rounded-xl bg-red-500/5 border border-red-500/20">
              <p className="text-xs text-red-400 font-medium text-center">{generalError}</p>
            </div>
          )}

          {!showTotp ? (
            <OtpInput
              value={otp}
              onChange={(value) => {
                setOtp(value);
                clearError('otp');
                setGeneralError('');
              }}
              error={errors.otp}
            />
          ) : (
            <div className="space-y-3">
              <p className="text-xs text-[#757575] text-center">
                Enter the 6-digit code from your authenticator app
              </p>
              <OtpInput
                value={totpInput}
                onChange={(value) => {
                  setTotpInput(value);
                  clearError('totp');
                  setGeneralError('');
                }}
                error={errors.totp}
              />
            </div>
          )}

          <PrimaryButton type="submit" loading={isLoading} disabled={isLoading}>
            Verify
          </PrimaryButton>
        </form>

        {/* Resend */}
        {!showTotp && (
          <div className="flex flex-col items-center gap-2 pt-0.5">
            {resendsLeft > 0 && !resendExhausted ? (
              <div className="inline-flex items-center h-7 px-3.5 rounded-full bg-[#FAFAFA] border border-[rgba(17,17,17,0.06)] text-[11px] font-bold text-[#6B6B6B]">
                {secondsLeft !== null && secondsLeft > 0 ? (
                  <span>
                    Resend in <span className="text-[#111]">{resendLabel(secondsLeft)}</span>
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={handleResend}
                    disabled={isLoading || !canResend}
                    className="hover:text-[#111] transition-colors disabled:opacity-50"
                  >
                    Resend code
                  </button>
                )}
              </div>
            ) : (
              <div className="text-center space-y-1">
                <p className="text-[11px] text-[#888]">
                  Maximum resends reached for this code.
                </p>
                <button
                  type="button"
                  onClick={handleFreshChallenge}
                  disabled={isLoading}
                  className="text-[11px] font-bold text-amber-600 hover:text-amber-700 underline"
                >
                  Request fresh code (10 min gate)
                </button>
              </div>
            )}
          </div>
        )}

        {/* Trust */}
        <p className="text-center text-[10px] font-semibold text-[#A8A8A8]">
          Secure 2-factor authentication
        </p>
      </div>
    </motion.div>
  );
}