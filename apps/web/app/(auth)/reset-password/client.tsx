'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { AuthSplitLayout } from '@/components/auth/AuthSplitLayout'
import { AuthHeader } from '@/components/auth/AuthHeader'
import { OtpInput } from '@/components/auth/OtpInput'
import { PasswordStrengthBar } from '@/components/ui/PasswordStrengthBar'

export default function ResetPasswordClient() {
  const router = useRouter()
  const [isLoading, setIsLoading] = useState(false)
  const [isSuccess, setIsSuccess] = useState(false)
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = useState(false)
  const [formData, setFormData] = useState({ email: '', code: '', password: '', confirmPassword: '' })
  const [errors, setErrors] = useState<Record<string, string>>({})

  const validateForm = () => {
    const e: Record<string, string> = {}
    if (!formData.email) e.email = 'Email is required'
    else if (!/\S+@\S+\.\S+/.test(formData.email)) e.email = 'Invalid email'
    if (!formData.code || formData.code.length < 6) e.code = 'Enter the 6-digit code'
    if (!formData.password) e.password = 'Password is required'
    else if (formData.password.length < 8) e.password = 'Min 8 characters'
    if (!formData.confirmPassword) e.confirmPassword = 'Confirm your password'
    else if (formData.password !== formData.confirmPassword) e.confirmPassword = 'Passwords don\'t match'
    setErrors(e)
    return Object.keys(e).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!validateForm()) return
    setIsLoading(true)
    try {
      await new Promise(resolve => setTimeout(resolve, 1500))
      setIsSuccess(true)
    } catch {
      setErrors({ general: 'Something went wrong' })
    } finally {
      setIsLoading(false)
    }
  }

  const inputClass = (field: string) =>
    `w-full px-4.5 py-3 rounded-full border bg-transparent text-zinc-150 text-[13px] transition-all duration-200 placeholder:text-zinc-650 focus:outline-none focus:bg-white/[0.01] ${
      errors[field]
        ? 'border-red-500/50 focus:border-red-500 focus:ring-2 focus:ring-red-500/10'
        : 'border-white/[0.08] focus:border-amber-500/30 focus:ring-2 focus:ring-amber-500/10'
    }`

  return (
    <AuthSplitLayout>
      <AuthHeader
        title={isSuccess ? 'Password updated' : 'Reset password'}
        subtitle={isSuccess ? 'Your password has been successfully reset' : 'Verify identity & create your new credentials'}
      />
      {errors.general && (
        <div className="mb-4 p-3 rounded-xl bg-red-500/5 border border-red-500/20">
          <p className="text-xs text-red-400 font-medium text-center">{errors.general}</p>
        </div>
      )}

      {isSuccess ? (
        <div className="text-center">
          <div className="w-14 h-14 mx-auto mb-4 rounded-full bg-emerald-500/[0.04] border border-emerald-500/20 flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.1)]">
            <svg className="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
          </div>
          <button onClick={() => router.push('/login')}
            className="w-full h-11 rounded-full bg-white text-black text-[13px] font-bold hover:bg-zinc-200 active:scale-[0.98] transition-all cursor-pointer shadow-md">
            Sign in with new password
          </button>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-[11px] font-semibold text-zinc-500 mb-2 uppercase tracking-wider pl-3">Email address</label>
            <input type="email" value={formData.email} onChange={(e) => setFormData(p => ({ ...p, email: e.target.value }))}
              className={inputClass('email')}
              placeholder="you@example.com" required />
            {errors.email && <p className="mt-1.5 pl-3 text-xs text-red-400 font-medium">{errors.email}</p>}
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-zinc-500 mb-2 uppercase tracking-wider pl-3">Verification code</label>
            <OtpInput value={formData.code} onChange={(v) => setFormData(p => ({ ...p, code: v }))} />
            {errors.code && <p className="mt-1.5 pl-3 text-xs text-red-400 font-medium">{errors.code}</p>}
          </div>

          <div className="relative">
            <label className="block text-[11px] font-semibold text-zinc-500 mb-2 uppercase tracking-wider pl-3">New password</label>
            <input type={showPassword ? 'text' : 'password'} value={formData.password} onChange={(e) => setFormData(p => ({ ...p, password: e.target.value }))}
              className={`${inputClass('password')} pr-10`}
              placeholder="Enter new password" required />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-4 top-[46px] text-zinc-500 hover:text-zinc-300 transition-colors cursor-pointer"
            >
              {showPassword ? (
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              ) : (
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              )}
            </button>
            <div className="mt-1 px-1">
              <PasswordStrengthBar password={formData.password} />
            </div>
            {errors.password && <p className="mt-1.5 pl-3 text-xs text-red-400 font-medium">{errors.password}</p>}
          </div>

          <div className="relative">
            <label className="block text-[11px] font-semibold text-zinc-500 mb-2 uppercase tracking-wider pl-3">Confirm password</label>
            <input type={showConfirmPassword ? 'text' : 'password'} value={formData.confirmPassword} onChange={(e) => setFormData(p => ({ ...p, confirmPassword: e.target.value }))}
              className={`${inputClass('confirmPassword')} pr-10`}
              placeholder="Confirm new password" required />
            <button
              type="button"
              onClick={() => setShowConfirmPassword(!showConfirmPassword)}
              className="absolute right-4 top-[46px] text-zinc-500 hover:text-zinc-300 transition-colors cursor-pointer"
            >
              {showConfirmPassword ? (
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              ) : (
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              )}
            </button>
            {errors.confirmPassword && <p className="mt-1.5 pl-3 text-xs text-red-400 font-medium">{errors.confirmPassword}</p>}
          </div>

          <button type="submit" disabled={isLoading}
            className="w-full h-11 rounded-full bg-white text-black text-[13px] font-bold hover:bg-zinc-200 active:scale-[0.98] transition-all cursor-pointer shadow-md disabled:opacity-40 disabled:scale-100 disabled:cursor-not-allowed">
            {isLoading ? 'Updating password...' : 'Update password'}
          </button>
        </form>
      )}

      <p className="mt-6 text-center text-[12px] text-zinc-500 font-semibold">
        <Link href="/login" className="text-white font-extrabold hover:underline">Back to sign in</Link>
      </p>
    </AuthSplitLayout>
  )
}
