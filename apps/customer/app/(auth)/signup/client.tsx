'use client';

import { useState, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { createOtpClient } from '@navdhya/otp/client';
import { getWidget, sendOtp, verifyOtp, retryOtp, OtpWidgetError } from '@navdhya/otp/widget';
import { useResendCountdown, resendLabel } from '@navdhya/otp/resend';

export default function SignupClient() {
  const router = useRouter();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [otp, setOtp] = useState('');
  const [step, setStep] = useState<'details' | 'otp'>('details');
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

  const handleDetailsSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!name.trim()) {
      setError('Enter your name');
      return;
    }

    if (!phone || phone.replace(/\D/g, '').length < 10) {
      setError('Enter a valid mobile number');
      return;
    }

    const normalizedPhone = normalizePhone(phone);
    setIsLoading(true);

    try {
      const challenge = await otpClient.signupChallenge({
        tenant_id: '',
        phone: normalizedPhone,
        name,
      });

      setChallengeId(challenge.challenge_id);

      const config = await otpClient.loadConfig();
      await getWidget({ widgetId: config.widget_id, tokenAuth: config.token_auth });
      await sendOtp(normalizedPhone);

      const delay = config.resend?.delays_seconds?.[0] ?? 60;
      setNextAllowedAt(new Date(Date.now() + delay * 1000).toISOString());
      setStep('otp');
    } catch (err: any) {
      if (err instanceof OtpWidgetError) {
        setError(err.message || 'Failed to send verification code.');
      } else {
        setError(err?.message || 'Signup failed');
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

      await otpClient.redeemVerify({
        tenant_id: '',
        challenge_id: challengeId,
        token,
      });

      router.push('/');
      router.refresh();
    } catch (err: any) {
      setError(err?.message || 'Invalid code');
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
          <h1 className="text-3xl font-bold text-gray-900">Create account</h1>
          <p className="text-gray-600 mt-2">Verify your mobile number to get started</p>
        </div>

        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            {error}
          </div>
        )}

        {step === 'details' && (
          <form onSubmit={handleDetailsSubmit} className="space-y-4">
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                Full Name
              </label>
              <input
                id="name"
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter your name"
                disabled={isLoading}
              />
            </div>

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
              {isLoading ? 'Sending code...' : 'Create account'}
            </button>
          </form>
        )}

        {step === 'otp' && (
          <form onSubmit={handleOtpSubmit} className="space-y-4">
            <p className="text-sm text-gray-600 text-center">
              We sent a 6-digit code to {phone}
            </p>
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

        <p className="mt-6 text-center text-sm text-gray-600">
          Already have an account?{' '}
          <Link href="/login" className="text-indigo-600 hover:text-indigo-800 font-medium">
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
}