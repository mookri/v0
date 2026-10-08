'use client'

import React, { useState } from 'react'
import { motion } from 'framer-motion'
import Link from 'next/link'

import { PhoneInput } from './PhoneInput'
import { PrimaryButton } from './AuthButtons'
import { AuthSocialButtons } from './AuthSocialButtons'
import { AuthDivider } from './AuthDivider'
import { AuthHeader } from './AuthHeader'
import type { AuthState } from './UnifiedAuthConsole'
import { authPost, setChallenge, toE164, newIdempotencyKey, AuthRequestError } from '@/lib/auth/auth-client'

interface SignupScreenProps {
  phone: string
  setPhone: (val: string) => void
  countryCode: string
  setCountryCode: (val: string) => void
  transitionTo: (state: AuthState) => void
  setOtpSentReason: (
    reason: 'LOGIN' | 'SIGNUP'
  ) => void
  animationVariants: any
}

export function SignupScreen({
  phone,
  setPhone,
  countryCode,
  setCountryCode,
  transitionTo,
  setOtpSentReason,
  animationVariants
}: SignupScreenProps) {
  const [isLoading, setIsLoading] =
    useState(false)

  const [errors, setErrors] = useState<
    Record<string, string>
  >({})

  const clearError = (field: string) => {
    setErrors(prev => {
      const next = { ...prev }
      delete next[field]
      return next
    })
  }

  const validate = () => {
    const e: Record<string, string> = {}

    if (!phone) {
      e.phone = 'Enter your phone number'
    } else if (
      phone.replace(/\D/g, '').length < 10
    ) {
      e.phone = 'Enter a valid mobile number'
    }

    setErrors(e)

    return Object.keys(e).length === 0
  }

  const handleSignup = async (
    e: React.FormEvent
  ) => {
    e.preventDefault()

    if (!validate()) return

    setIsLoading(true)

    try {
      const e164 = toE164(countryCode, phone)
      const res = await authPost<{ data?: { challengeId?: string; expiresIn?: number } }>(
        '/api/auth/signup',
        { phone: e164, name: phone },
        { idempotencyKey: newIdempotencyKey() },
      )

      const challengeId = res.data.data?.challengeId
      if (!challengeId) {
        setErrors({ general: 'Failed to get challenge ID' })
        return
      }

      setChallenge(challengeId, 'signup', res.data.data?.expiresIn ?? 600)
      setOtpSentReason('SIGNUP')
      transitionTo('OTP')
    } catch (err) {
      const e = err as AuthRequestError
      setErrors({ general: e.message || 'Signup failed' })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <motion.div
      key="signup"
      variants={animationVariants}
      initial="initial"
      animate="animate"
      exit="exit"
      className="w-full"
    >
      <div className="space-y-4">

        {/* Header */}
        <AuthHeader
          eyebrow="Create account"
          title="Sign Up"
          subtitle="Verify your mobile number to establish your operations workspace."
        />

        {/* Form */}
        <form
          onSubmit={handleSignup}
          className="space-y-3"
        >
          <div className="flex flex-col">
            <span className="text-[10px] font-semibold tracking-[0.08em] uppercase text-[#757575] mb-1">
              Mobile Number
            </span>
            <PhoneInput
              value={phone}
              onChange={(val) => {
                setPhone(val)
                clearError('phone')
              }}
              countryCode={countryCode}
              onCountryChange={setCountryCode}
              error={errors.phone}
            />
          </div>

          {errors.general && (
            <p className="text-[11.5px] font-semibold text-red-500">
              {errors.general}
            </p>
          )}

          <PrimaryButton
            type="submit"
            loading={isLoading}
            disabled={isLoading}
          >
            Create account
          </PrimaryButton>

          {/* Snug inline Terms and Privacy agreement disclaimer */}
          <p className="text-center text-[10.5px] leading-[1.4] text-[#A0A0A0] px-2 font-semibold mt-2.5 text-pretty">
            By signing up, you agree to our{" "}
            <Link 
              href="/terms-of-service"
              className="text-[#6B6B6B] hover:text-[#111] underline decoration-[rgba(17,17,17,0.15)] transition-colors"
            >
              Terms of Service
            </Link>{" "}
            and{" "}
            <Link 
              href="/privacy-policy"
              className="text-[#6B6B6B] hover:text-[#111] underline decoration-[rgba(17,17,17,0.15)] transition-colors"
            >
              Privacy Policy
            </Link>.
          </p>
        </form>

        {/* Divider */}
        <AuthDivider />

        {/* Alternate */}
        <div className="space-y-2.5">
          <AuthSocialButtons
            onGoogle={() => console.log('Google signup requested')}
            onApple={() => console.log('Apple signup requested')}
          />

          <button
            type="button"
            onClick={() =>
              transitionTo('LOGIN')
            }
            className="w-full text-center text-[11.5px] sm:text-[12px] font-bold text-[#666] hover:text-[#111] transition-colors"
          >
            Already have an account? Sign in
          </button>
        </div>

        {/* Trust */}
        <p className="text-center text-[10px] font-semibold text-[#A8A8A8]">
          Bank-grade secure data transmission
        </p>
      </div>
    </motion.div>
  )
}