
'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { AuthSplitLayout } from '@/components/auth/AuthSplitLayout'

export default function LockScreenClient() {
  const router = useRouter()
  const [isLoading, setIsLoading] = useState(false)
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!password) { setError('Password is required'); return }
    setError('')
    setIsLoading(true)
    try {
      await new Promise(resolve => setTimeout(resolve, 1500))
      router.push('/user/overview')
    } catch {
      setError('Invalid password')
    } finally {
      setIsLoading(false)
    }
  }

  const inputClass = `w-full px-4.5 py-3 rounded-full border bg-transparent text-zinc-150 text-[13px] transition-all duration-200 placeholder:text-zinc-650 focus:outline-none focus:bg-white/[0.01] ${
    error
      ? 'border-red-500/50 focus:border-red-500 focus:ring-2 focus:ring-red-500/10'
      : 'border-white/[0.08] focus:border-amber-500/30 focus:ring-2 focus:ring-amber-500/10'
  }`

  return (
    <AuthSplitLayout>
      <div className="text-center mb-6">
        <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-white/[0.02] border border-white/[0.06] flex items-center justify-center shadow-inner">
          <svg className="w-8 h-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
          </svg>
        </div>
        <h2 className="text-lg font-bold text-zinc-200 tracking-tight leading-tight">Welcome back</h2>
        <p className="text-[11px] text-zinc-500 uppercase font-semibold mt-1 tracking-wider">Provide password to unlock console</p>
      </div>

      {error && (
        <div className="mb-4 p-3 rounded-xl bg-red-500/5 border border-red-500/20">
          <p className="text-xs text-red-400 font-medium text-center">{error}</p>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="relative">
          <input type={showPassword ? 'text' : 'password'} value={password} onChange={(e) => { setPassword(e.target.value); setError('') }}
            className={`${inputClass} pr-10`}
            placeholder="Enter unlock password" required />
          
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300 transition-colors cursor-pointer"
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
        </div>

        <button type="submit" disabled={isLoading}
          className="w-full h-11 rounded-full bg-white text-black text-[13px] font-bold hover:bg-zinc-200 active:scale-[0.98] transition-all cursor-pointer shadow-md disabled:opacity-40">
          {isLoading ? 'Unlocking...' : 'Unlock'}
        </button>
      </form>

      <p className="mt-6 text-center text-[12px] text-zinc-500 font-semibold">
        Not you? <Link href="/login" className="text-white font-extrabold hover:underline">Sign in</Link>
      </p>
    </AuthSplitLayout>
  )
}
