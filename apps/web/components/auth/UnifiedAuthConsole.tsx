'use client'

import { useState, useCallback, useEffect } from 'react'
import { AnimatePresence } from 'framer-motion'

import { AuthSplitLayout } from './AuthSplitLayout'
import { LoginScreen } from './LoginScreen'
import { SignupScreen } from './SignupScreen'
import { ForgotScreen } from './ForgotScreen'
import { OtpScreen } from './OtpScreen'
import { SuccessScreen } from './SuccessScreen'

export type AuthState =
  | 'LOGIN'
  | 'SIGNUP'
  | 'FORGOT_PASSWORD'
  | 'OTP'
  | 'SUCCESS'

interface UnifiedAuthConsoleProps {
  initialState?: AuthState
}

const animationVariants = {
  initial: {
    opacity: 0,
    y: 18
  },
  animate: {
    opacity: 1,
    y: 0,
    transition: {
      duration: 0.42,
      ease: [0.16, 1, 0.3, 1]
    }
  },
  exit: {
    opacity: 0,
    y: -12,
    transition: {
      duration: 0.22
    }
  }
}

export function UnifiedAuthConsole({
  initialState = 'LOGIN'
}: UnifiedAuthConsoleProps) {
  const [state, setState] =
    useState<AuthState>(initialState)

  const [phone, setPhone] = useState('')
  const [countryCode, setCountryCode] =
    useState('IN')

  const [otpSentReason, setOtpSentReason] =
    useState<'LOGIN' | 'SIGNUP'>('LOGIN')

  // HTML5 History back/forward navigation sync
  useEffect(() => {
    const handlePopState = () => {
      if (typeof window !== 'undefined') {
        const path = window.location.pathname
        if (path.includes('/login')) {
          setState('LOGIN')
        } else if (path.includes('/signup')) {
          setState('SIGNUP')
        } else if (path.includes('/forgot-password')) {
          setState('FORGOT_PASSWORD')
        }
      }
    }

    window.addEventListener('popstate', handlePopState)
    return () => window.removeEventListener('popstate', handlePopState)
  }, [])

  const transitionTo = useCallback(
    (next: AuthState) => {
      setState(next)
      
      if (typeof window !== 'undefined') {
        let path = ''
        if (next === 'LOGIN') path = '/login'
        else if (next === 'SIGNUP') path = '/signup'
        else if (next === 'FORGOT_PASSWORD') path = '/forgot-password'
        
        if (path && window.location.pathname !== path) {
          window.history.pushState(null, '', path)
        }
      }
    },
    []
  )

  const renderScreen = () => {
    switch (state) {
      case 'LOGIN':
        return (
          <LoginScreen
            phone={phone}
            setPhone={setPhone}
            countryCode={countryCode}
            setCountryCode={setCountryCode}
            transitionTo={transitionTo}
            setOtpSentReason={setOtpSentReason}
            animationVariants={
              animationVariants
            }
          />
        )

      case 'SIGNUP':
        return (
          <SignupScreen
            phone={phone}
            setPhone={setPhone}
            countryCode={countryCode}
            setCountryCode={setCountryCode}
            transitionTo={transitionTo}
            setOtpSentReason={setOtpSentReason}
            animationVariants={
              animationVariants
            }
          />
        )

      case 'FORGOT_PASSWORD':
        return (
          <ForgotScreen
            transitionTo={transitionTo}
            animationVariants={
              animationVariants
            }
          />
        )

      case 'OTP':
        return (
          <OtpScreen
            phone={phone}
            countryCode={countryCode}
            otpSentReason={otpSentReason}
            transitionTo={transitionTo}
            animationVariants={
              animationVariants
            }
          />
        )

      case 'SUCCESS':
        return (
          <SuccessScreen
            animationVariants={
              animationVariants
            }
          />
        )

      default:
        return null
    }
  }

  return (
    <AuthSplitLayout state={state} transitionTo={transitionTo}>
      <AnimatePresence mode="wait">
        {renderScreen()}
      </AnimatePresence>
    </AuthSplitLayout>
  )
}