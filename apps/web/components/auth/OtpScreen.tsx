'use client'

import React, {
  useEffect,
  useState
} from 'react'
import { motion } from 'framer-motion'
import { OtpInput } from './OtpInput'
import { PrimaryButton } from './AuthButtons'
import { AuthHeader } from './AuthHeader'
import type { AuthState } from './UnifiedAuthConsole'
import { getCountryByCode } from '@/lib/auth/countries'
import {
  authPost,
  getChallenge,
  setChallenge,
  clearChallenge,
  redeemBody,
  toE164,
  newIdempotencyKey,
  AuthRequestError,
} from '@/lib/auth/auth-client'

/**
 * Backend truth (`CustomerOtpService::MAX_ATTEMPTS = 3`): three wrong
 * codes exhaust the challenge row — the backend deletes it and every
 * further redeem 401s until a new challenge is issued. There is no
 * time-based lock (`locked_until` does not exist), so this component
 * never fabricates one; the local counter is a UX mirror only (I18).
 */
const MAX_ATTEMPTS = 3

interface OtpScreenProps {
  phone: string
  countryCode: string
  otpSentReason: 'LOGIN' | 'SIGNUP'
  transitionTo: (state: AuthState) => void
  animationVariants: any
}

export function OtpScreen({
  phone,
  countryCode,
  otpSentReason,
  transitionTo,
  animationVariants
}: OtpScreenProps) {
  const [otp, setOtp] = useState('')
  const [isLoading, setIsLoading] =
    useState(false)
  const [resendTimer, setResendTimer] =
    useState(60)
  const [errors, setErrors] = useState<
    Record<string, string>
  >({})
  const [generalError, setGeneralError] = useState('')
  const [attempts, setAttempts] = useState(0)
  const [lockedUntil, setLockedUntil] = useState<number | null>(null)
  // Set once the local mirror reaches MAX_ATTEMPTS: the backend has
  // deleted the challenge row, so redeeming again is pointless.
  const [exhausted, setExhausted] = useState(false)

  const dialCode =
    getCountryByCode(countryCode).dialCode

  const clearError = (field: string) => {
    setErrors(prev => {
      const next = { ...prev }
      delete next[field]
      return next
    })
  }

  useEffect(() => {
    if (resendTimer <= 0) return

    const timer = setTimeout(() => {
      setResendTimer(prev => prev - 1)
    }, 1000)

    return () => clearTimeout(timer)
  }, [resendTimer])

  const handleVerifyOtp = async (
    e?: React.FormEvent
  ) => {
    if (e) e.preventDefault()

    if (lockedUntil && Date.now() < lockedUntil) {
      const waitSec = Math.ceil((lockedUntil - Date.now()) / 1000)
      setGeneralError(`Too many attempts. Try again in ${waitSec}s.`)
      return
    }

    if (exhausted) {
      setGeneralError('Too many failed attempts. Please request a new code.')
      return
    }

    if (otp.length !== 6) {
      setErrors({
        otp: 'Enter complete code'
      })
      return
    }

    setIsLoading(true)
    setGeneralError('')

    try {
      const challenge = getChallenge()
      if (!challenge) {
        setGeneralError('Session expired. Please request a new code.')
        clearChallenge()
        setIsLoading(false)
        return
      }

      // Contract §2: canonical field is `proof`. The old `code` field
      // here was sending an empty credential to /auth/login.
      const endpoint =
        otpSentReason === 'LOGIN' ? '/api/auth/login' : '/api/auth/verify'
      const body =
        otpSentReason === 'LOGIN'
          ? redeemBody(challenge.id, otp)
          : { challengeId: challenge.id, code: otp }

      await authPost(endpoint, body)

      clearChallenge()
      transitionTo('SUCCESS')
    } catch (err) {
      const e = err as AuthRequestError

      if (e.code === 'RATE_LIMITED') {
        const retryAfter = e.retryAfter ?? 60
        setLockedUntil(Date.now() + retryAfter * 1000)
        setGeneralError(e.message)
      } else if (e.status === 401) {
        // Wrong code — count locally; backend lock is authoritative (I8).
        // Backend truth: the 3rd failure exhausts the challenge (the row
        // is deleted), so drop the challenge and require a restart — no
        // fabricated time-based lock.
        const next = attempts + 1
        setAttempts(next)
        if (next >= MAX_ATTEMPTS) {
          setExhausted(true)
          clearChallenge()
          setGeneralError('Too many failed attempts. Please request a new code.')
        } else {
          setGeneralError(`${e.message} (${MAX_ATTEMPTS - next} attempts remaining)`)
        }
      } else {
        setGeneralError(e.message || 'Invalid code')
      }
    } finally {
      setIsLoading(false)
    }
  }

  const handleResend = async () => {
    setGeneralError('')
    setAttempts(0)
    setLockedUntil(null)
    setExhausted(false)

    try {
      const purpose = otpSentReason === 'LOGIN' ? 'login' : 'signup'
      const e164 = toE164(countryCode, phone)

      if (purpose === 'login') {
        const res = await authPost<{ data?: { challengeId?: string; expiresIn?: number } }>(
          '/api/auth/otp',
          { identity: e164, purpose: 'login' },
          { idempotencyKey: newIdempotencyKey() },
        )
        const challengeId = res.data.data?.challengeId
        if (challengeId) setChallenge(challengeId, 'login', res.data.data?.expiresIn ?? 600)
      } else {
        const res = await authPost<{ data?: { challengeId?: string; expiresIn?: number } }>(
          '/api/auth/signup',
          { phone: e164, name: phone },
          { idempotencyKey: newIdempotencyKey() },
        )
        const challengeId = res.data.data?.challengeId
        if (challengeId) setChallenge(challengeId, 'signup', res.data.data?.expiresIn ?? 600)
      }

      setResendTimer(60)
    } catch (err) {
      const e = err as AuthRequestError
      if (e.code === 'RATE_LIMITED') {
        const retryAfter = e.retryAfter ?? 60
        setResendTimer(retryAfter)
      }
      setGeneralError(e.message || 'Failed to resend code')
    }
  }

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
          onClick={() =>
            transitionTo(
              otpSentReason === 'LOGIN'
                ? 'LOGIN'
                : 'SIGNUP'
            )
          }
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
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M15 19l-7-7 7-7"
            />
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
        <form
          onSubmit={handleVerifyOtp}
          className="space-y-3"
        >
          {generalError && (
            <div className="mb-2 p-3 rounded-xl bg-red-500/5 border border-red-500/20">
              <p className="text-xs text-red-400 font-medium text-center">{generalError}</p>
            </div>
          )}
          <OtpInput
            value={otp}
            onChange={(value) => {
              setOtp(value)
              clearError('otp')
              setGeneralError('')
            }}
            error={errors.otp}
          />

          <PrimaryButton
            type="submit"
            loading={isLoading}
            disabled={isLoading}
          >
            Verify
          </PrimaryButton>
        </form>

        {/* Resend */}
        <div className="flex justify-center pt-0.5">
          <div className="inline-flex items-center h-7 px-3.5 rounded-full bg-[#FAFAFA] border border-[rgba(17,17,17,0.06)] text-[11px] font-bold text-[#6B6B6B]">
            {resendTimer > 0 ? (
              <span>
                Resend in <span className="text-[#111]">{resendTimer}s</span>
              </span>
            ) : (
              <button
                type="button"
                onClick={handleResend}
                disabled={isLoading}
                className="hover:text-[#111] transition-colors disabled:opacity-50"
              >
                Resend code
              </button>
            )}
          </div>
        </div>

        {/* Trust */}
        <p className="text-center text-[10px] font-semibold text-[#A8A8A8]">
          Secure 2-factor authentication
        </p>
      </div>
    </motion.div>
  )
}