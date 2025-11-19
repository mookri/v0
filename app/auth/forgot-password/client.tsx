'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Mail } from 'lucide-react'

export default function ForgotPasswordClient() {
  const [isLoading, setIsLoading] = useState(false)
  const [isSubmitted, setIsSubmitted] = useState(false)
  const [email, setEmail] = useState('')
  const [error, setError] = useState('')

  const validateForm = () => {
    if (!email) {
      setError('Email is required')
      return false
    } else if (!/\S+@\S+\.\S+/.test(email)) {
      setError('Email address is invalid')
      return false
    }
    
    setError('')
    return true
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    if (!validateForm()) return
    
    setIsLoading(true)
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500))
      
      // Show success message
      setIsSubmitted(true)
    } catch (error) {
      console.error('Forgot password error:', error)
      setError('An error occurred. Please try again.')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-background flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          <div className="mx-auto h-16 w-16 rounded-full bg-[#0E3F2E] flex items-center justify-center">
            <Mail className="h-8 w-8 text-white" />
          </div>
          <h2 className="mt-6 text-3xl font-serif font-bold text-foreground">
            {isSubmitted ? 'Check your email' : 'Forgot your password?'}
          </h2>
          <p className="mt-2 text-sm text-muted-foreground">
            {isSubmitted 
              ? 'We have sent password reset instructions to your email address.' 
              : 'Enter your email address and we will send you a link to reset your password.'}
          </p>
        </div>

        {error && (
          <div className="rounded-md bg-destructive/10 p-4">
            <div className="text-sm text-destructive">
              {error}
            </div>
          </div>
        )}

        {isSubmitted ? (
          <div className="mt-8 text-center">
            <div className="rounded-md bg-[#0E3F2E]/10 p-4">
              <div className="text-sm text-[#0E3F2E]">
                <p>If an account exists for {email}, you will receive a password reset link shortly.</p>
                <p className="mt-2">Please check your inbox and spam folder.</p>
              </div>
            </div>
            
            <div className="mt-6">
              <Link 
                href="/auth/login" 
                className="font-medium text-[#0E3F2E] hover:text-[#0E3F2E]/90 text-sm"
              >
                ← Back to login
              </Link>
            </div>
          </div>
        ) : (
          <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-foreground mb-1">
                Email address
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Mail className="h-5 w-5 text-muted-foreground" />
                </div>
                <input
                  id="email"
                  name="email"
                  type="email"
                  autoComplete="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className={`pl-10 w-full px-3 py-2 border rounded-lg shadow-sm placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-[#0E3F2E] focus:border-transparent bg-background ${
                    error ? 'border-destructive' : 'border-border'
                  }`}
                  placeholder="you@example.com"
                />
              </div>
            </div>

            <div>
              <button
                type="submit"
                disabled={isLoading}
                className="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0E3F2E] disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
              >
                {isLoading ? (
                  <span className="flex items-center">
                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sending...
                  </span>
                ) : (
                  'Send reset link'
                )}
              </button>
            </div>
          </form>
        )}

        {!isSubmitted && (
          <div className="text-center text-sm text-muted-foreground">
            <Link href="/auth/login" className="font-medium text-[#0E3F2E] hover:text-[#0E3F2E]/90">
              ← Back to login
            </Link>
          </div>
        )}
      </div>
    </div>
  )
}