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

    try {
      await new Promise(r =>
        setTimeout(r, 1000)
      )

      transitionTo('SUCCESS')
    } finally {
      setIsLoading(false)
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
          <OtpInput
            value={otp}
            onChange={(value) => {
              setOtp(value)
              clearError('otp')
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
                onClick={async () => {
                  setResendTimer(60)

                  await new Promise(r =>
                    setTimeout(r, 500)
                  )
                }}
                className="hover:text-[#111] transition-colors"
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