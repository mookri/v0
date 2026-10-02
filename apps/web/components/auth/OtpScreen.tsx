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

    if (otp.length !== 6) {
      setErrors({
        otp: 'Enter complete code'
      })
      return
    }

    setIsLoading(true)
    setGeneralError('')

    try {
      const challengeId = sessionStorage.getItem('auth_challenge_id')
      if (!challengeId) {
        setGeneralError('Session expired. Please request a new code.')
        setIsLoading(false)
        return
      }

      const endpoint = otpSentReason === 'LOGIN' ? '/api/auth/login' : '/api/auth/verify'
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ challengeId, code: otp }),
      })

      if (!res.ok) {
        const data = await res.json()
        setGeneralError(data.error?.message || 'Invalid code')
        return
      }

      sessionStorage.removeItem('auth_challenge_id')
      transitionTo('SUCCESS')
    } finally {
      setIsLoading(false)
    }
  }

  const handleResend = async () => {
    setResendTimer(60)
    setGeneralError('')

    try {
      const purpose = otpSentReason === 'LOGIN' ? 'login' : 'signup'
      const endpoint = purpose === 'login' ? '/api/auth/otp' : '/api/auth/signup'
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: purpose === 'login'
          ? JSON.stringify({ identity: phone, purpose: 'login' })
          : JSON.stringify({ phone, name: phone }),
      })

      if (!res.ok) {
        const data = await res.json()
        setGeneralError(data.error?.message || 'Failed to resend code')
        return
      }

      const data = await res.json()
      const challengeId = data.data?.challengeId
      if (challengeId) {
        sessionStorage.setItem('auth_challenge_id', challengeId)
      }
    } catch {
      setGeneralError('Failed to resend code')
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