'use client'

import React, { useState } from 'react'
import { motion } from 'framer-motion'
import { FloatingInput } from './FloatingInput'
import { PrimaryButton } from './AuthButtons'
import { AuthHeader } from './AuthHeader'
import type { AuthState } from './UnifiedAuthConsole'

interface ForgotScreenProps {
  transitionTo: (state: AuthState) => void
  animationVariants: any
}

export function ForgotScreen({
  transitionTo,
  animationVariants
}: ForgotScreenProps) {
  const [isLoading, setIsLoading] = useState(false)
  const [forgotEmail, setForgotEmail] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})

  const clearError = (field: string) => {
    setErrors(prev => {
      const next = { ...prev }
      delete next[field]
      return next
    })
  }

  const handleForgotSubmit = async (
    e: React.FormEvent
  ) => {
    e.preventDefault()

    if (!forgotEmail) {
      setErrors({
        forgotEmail: 'Enter your email'
      })
      return
    }

    if (!/\S+@\S+\.\S+/.test(forgotEmail)) {
      setErrors({
        forgotEmail: 'Enter a valid email'
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
      key="forgot"
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
            transitionTo('LOGIN')
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
          eyebrow="Account Security"
          title="Reset password"
          subtitle="Enter your email address to receive a secure password reset link."
        />

        {/* Form */}
        <form
          onSubmit={handleForgotSubmit}
          className="space-y-3"
        >
          <FloatingInput
            label="Email Address"
            type="email"
            placeholder="e.g. name@company.com"
            value={forgotEmail}
            onChange={(e) => {
              setForgotEmail(
                e.target.value
              )
              clearError(
                'forgotEmail'
              )
            }}
            error={errors.forgotEmail}
            required
          />

          <PrimaryButton
            type="submit"
            loading={isLoading}
            disabled={isLoading}
          >
            Send Link
          </PrimaryButton>
        </form>

        <p className="text-center text-[10px] font-semibold text-[#A8A8A8]">
          SSL verified request
        </p>
      </div>
    </motion.div>
  )
}