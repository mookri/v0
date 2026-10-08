'use client'

import React, { useState } from 'react'
import { motion } from 'framer-motion'
import { PhoneInput } from './PhoneInput'
import { PrimaryButton } from './AuthButtons'
import { AuthSocialButtons } from './AuthSocialButtons'
import { AuthDivider } from './AuthDivider'
import { AuthHeader } from './AuthHeader'
import type { AuthState } from './UnifiedAuthConsole'
import { authPost, setChallenge, toE164, newIdempotencyKey, AuthRequestError } from '@/lib/auth/auth-client'

interface LoginScreenProps {
  phone: string
  setPhone: (val: string) => void
  countryCode: string
  setCountryCode: (val: string) => void
  transitionTo: (state: AuthState) => void
  setOtpSentReason: (reason: 'LOGIN' | 'SIGNUP') => void
  animationVariants: any
}

export function LoginScreen({
  phone,
  setPhone,
  countryCode,
  setCountryCode,
  transitionTo,
  setOtpSentReason,
  animationVariants
}: LoginScreenProps) {
  const [isLoading, setIsLoading] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})

  const validatePhone = () => {
    const e: Record<string, string> = {}

    if (!phone) {
      e.phone = 'Enter your phone number'
    } else if (phone.replace(/\D/g, '').length < 10) {
      e.phone = 'Enter a valid mobile number'
    }

    setErrors(e)
    return Object.keys(e).length === 0
  }

  const handleLoginSubmit = async (
    e: React.FormEvent
  ) => {
    e.preventDefault()

    if (!validatePhone()) return

    setIsLoading(true)

    try {
      // E.164 normalization — the backend binds the challenge to a
      // normalized identity (contract §1/I3).
      const identity = toE164(countryCode, phone)
      const res = await authPost<{ data?: { challengeId?: string; expiresIn?: number } }>(
        '/api/auth/otp',
        { identity, purpose: 'login' },
        { idempotencyKey: newIdempotencyKey() },
      )

      const challengeId = res.data.data?.challengeId
      if (!challengeId) {
        setErrors({ phone: 'Failed to get challenge ID' })
        return
      }

      setChallenge(challengeId, 'login', res.data.data?.expiresIn ?? 600)
      setOtpSentReason('LOGIN')
      transitionTo('OTP')
    } catch (err) {
      const e = err as AuthRequestError
      setErrors({ phone: e.message || 'Failed to send code' })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <motion.div
      key="login"
      variants={animationVariants}
      initial="initial"
      animate="animate"
      exit="exit"
      className="w-full"
    >
      <div className="space-y-4">

        {/* Header */}
        <AuthHeader
          eyebrow="Welcome back"
          title="Sign In"
          subtitle="Access your personal ritual schedule and spiritual guidance securely."
        />

        {/* Form */}
        <form
          onSubmit={handleLoginSubmit}
          className="space-y-3"
        >
          <div className="flex flex-col">
            <span className="text-[10px] font-semibold tracking-[0.08em] uppercase text-[#757575] mb-1">
              Mobile Number
            </span>
            <PhoneInput
              value={phone}
              onChange={setPhone}
              countryCode={countryCode}
              onCountryChange={setCountryCode}
              error={errors.phone}
            />
          </div>

          <PrimaryButton
            type="submit"
            loading={isLoading}
            disabled={isLoading}
          >
            Continue
          </PrimaryButton>
        </form>

        {/* Utility */}
        <div className="flex justify-start">
          <button
            type="button"
            onClick={() =>
              transitionTo('FORGOT_PASSWORD')
            }
            className="text-[11.5px] font-bold text-[#757575] hover:text-[#111] transition-colors"
          >
            Forgot password?
          </button>
        </div>

        <AuthDivider />

        {/* Secondary */}
        <div className="space-y-2.5">
          <AuthSocialButtons
            onGoogle={() => console.log('Google login requested')}
            onApple={() => console.log('Apple login requested')}
          />

          <button
            type="button"
            onClick={() =>
              transitionTo('SIGNUP')
            }
            className="w-full text-center text-[11.5px] sm:text-[12px] font-bold text-[#666] hover:text-[#111] transition-colors"
          >
            Don’t have an account? Sign up
          </button>
        </div>

        {/* Trust */}
        <p className="text-center text-[10px] font-semibold text-[#A8A8A8]">
          Protected by secure 256-bit encryption
        </p>
      </div>
    </motion.div>
  )
}