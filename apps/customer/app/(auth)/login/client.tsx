'use client';

import { useState, useMemo } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { createOtpClient } from '@navdhya/otp/client';
import { getWidget, sendOtp, verifyOtp, retryOtp, OtpWidgetError } from '@navdhya/otp/widget';
import { useResendCountdown, resendLabel } from '@navdhya/otp/resend';

export default function LoginClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const redirect = searchParams.get('redirect') || '/';

  const [phone, setPhone] = useState('');
  const [otp, setOtp] = useState('');
  const [totpCode, setTotpCode] = useState('');
  const [step, setStep] = useState<'phone' | 'otp' | 'totp'>('phone');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [challengeId, setChallengeId] = useState('');
  const [nextAllowedAt, setNextAllowedAt] = useState<string | null>(null);
  const [resendsLeft, setResendsLeft] = useState(3);

  const otpClient = useMemo(() => createOtpClient({ basePath: '/api/auth' }), []);
  const { secondsLeft, canResend, exhausted: resendExhausted } = useResendCountdown(nextAllowedAt);

  const normalizePhone = (raw: string) => {
    const digits = raw.replace(/\D/g, '');
    return raw.trim().startsWith('+')
      ? `+${digits}`
      : digits.length === 10
        ? `+91${digits}`
        : `+${digits}`;
  };

  const handlePhoneSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!phone || phone.replace(/\D/g, '').length < 10) {
      setError('Enter a valid mobile number');
      return;
    }

    const normalized = normalizePhone(phone);
    setIsLoading(true);

    try {
      const challenge = await otpClient.requestChallenge({
        tenant_id: '',
        purpose: 'login',
        identity: normalized,
      });

      setChallengeId(challenge.challenge_id);

      // Initialize widget and send OTP
      const config = await otpClient.loadConfig();
      await getWidget({ widgetId: config.widget_id, tokenAuth: config.token_auth });
      await sendOtp(normalized);

      const delay = config.resend?.delays_seconds?.[0] ?? 60;
      setNextAllowedAt(new Date(Date.now() + delay * 1000).toISOString());
      setStep('otp');
    } catch (err: any) {
      if (err instanceof OtpWidgetError) {
        setError(err.message || 'Failed to send verification code.');
      } else {
        setError(err?.message || 'Failed to send code');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleOtpSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (otp.length !== 6) {
      setError('Enter complete code');
      return;
    }

    setIsLoading(true);
    try {
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

      const result = await otpClient.redeemLogin({
        tenant_id: '',
        challenge_id: challengeId,
        token,
      });

      if (result.kind === 'mfa_required') {
        setStep('totp');
        setError('');
        return;
      }

      // Success
      router.push(redirect);
      router.refresh();
    } catch (err: any) {
      if (err.status === 401) {
        setStep('totp');
        setError('If your account uses two-factor authentication, enter the code from your authenticator app.');
      } else {
        setError(err?.message || 'Invalid code');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleTotpSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (totpCode.length !== 6) {
      setError('Enter complete code');
      return;
    }

    setIsLoading(true);
    try {
      await otpClient.redeemTotp({
        tenant_id: '',
        challenge_id: challengeId,
        totp_code: totpCode,
      });

      router.push(redirect);
      router.refresh();
    } catch (err: any) {
      setError(err?.message || 'Invalid authenticator code');
    } finally {
      setIsLoading(false);
    }
  };

  const handleResend = async () => {
    if (!canResend || resendsLeft <= 0) return;

    setError('');
    setIsLoading(true);

    try {
      const resendResult = await otpClient.resend({
        tenant_id: '',
        challenge_id: challengeId,
      });

      await retryOtp(resendResult.channel);
      setResendsLeft(resendResult.resends_left);
      setNextAllowedAt(resendResult.next_allowed_at);
      setOtp('');
    } catch (err: any) {
      if (err.retryAfterSeconds) {
        setNextAllowedAt(new Date(Date.now() + err.retryAfterSeconds * 1000).toISOString());
        setError(`Too many requests. Please wait ${err.retryAfterSeconds}s.`);
      } else {
        setError(err?.message || 'Failed to resend code');
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-white flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Welcome back</h1>
          <p className="text-gray-600 mt-2">Sign in to your Navdhya account</p>
        </div>

        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            {error}
          </div>
        )}

        {step === 'phone' && (
          <form onSubmit={handlePhoneSubmit} className="space-y-4">
            <div>
              <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">
                Mobile Number
              </label>
              <input
                id="phone"
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter your phone number"
                disabled={isLoading}
              />
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {isLoading ? 'Sending code...' : 'Continue'}
            </button>
          </form>
        )}

        {step === 'otp' && (
          <form onSubmit={handleOtpSubmit} className="space-y-4">
            <div>
              <label htmlFor="otp" className="block text-sm font-medium text-gray-700 mb-1">
                Verification Code
              </label>
              <input
                id="otp"
                type="text"
                value={otp}
                onChange={(e) => setOtp(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl tracking-widest"
                placeholder="• • • • • •"
                maxLength={6}
                disabled={isLoading}
                autoComplete="one-time-code"
              />
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {isLoading ? 'Verifying...' : 'Verify'}
            </button>

            <div className="text-center pt-2">
              {resendsLeft > 0 && !resendExhausted ? (
                secondsLeft !== null && secondsLeft > 0 ? (
                  <span className="text-sm text-gray-500">
                    Resend in {resendLabel(secondsLeft)}
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={handleResend}
                    disabled={isLoading || !canResend}
                    className="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                  >
                    Resend code
                  </button>
                )
              ) : (
                <span className="text-xs text-gray-500">
                  Maximum resends reached (try again in 10 minutes)
                </span>
              )}
            </div>
          </form>
        )}

        {step === 'totp' && (
          <form onSubmit={handleTotpSubmit} className="space-y-4">
            <p className="text-sm text-gray-600 text-center">
              Enter the 6-digit code from your authenticator app
            </p>
            <div>
              <label htmlFor="totp" className="block text-sm font-medium text-gray-700 mb-1">
                Authenticator Code
              </label>
              <input
                id="totp"
                type="text"
                value={totpCode}
                onChange={(e) => setTotpCode(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl tracking-widest"
                placeholder="• • • • • •"
                maxLength={6}
                disabled={isLoading}
                autoComplete="one-time-code"
              />
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {isLoading ? 'Verifying...' : 'Verify'}
            </button>
          </form>
        )}

        <p className="mt-6 text-center text-sm text-gray-600">
          Don't have an account?{' '}
          <Link href="/signup" className="text-indigo-600 hover:text-indigo-800 font-medium">
            Sign up
          </Link>
        </p>
      </div>
    </div>
  );
}