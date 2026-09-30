'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { AuthSplitLayout } from '@/components/auth/AuthSplitLayout'
import { AuthHeader } from '@/components/auth/AuthHeader'
import { OtpInput } from '@/components/auth/OtpInput'

export default function TwoFactorClient() {
  const router = useRouter()
  const [isLoading, setIsLoading] = useState(false)
  const [code, setCode] = useState('')
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (code.length < 6) { setError('Enter the 6-digit code'); return }
    setError('')
    setIsLoading(true)
    try {
      await new Promise(resolve => setTimeout(resolve, 1500))
      router.push('/user/overview')
    } catch {
      setError('Invalid code')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <AuthSplitLayout>
      <AuthHeader title="Two-factor auth" subtitle="Verification code sent to your registered contact" />
      <div className="text-center mb-6">
        <span className="text-base font-mono font-bold tracking-[0.2em] text-zinc-300 bg-white/[0.02] border border-white/[0.05] px-4 py-1.5 rounded-full shadow-inner">******6789</span>
      </div>

      {error && (
        <div className="mb-4 p-3 rounded-xl bg-red-500/5 border border-red-500/20">
          <p className="text-xs text-red-400 font-medium text-center">{error}</p>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-5">
        <OtpInput value={code} onChange={(v) => { setCode(v); setError('') }} />
        <button type="submit" disabled={isLoading}
          className="w-full h-11 rounded-full bg-white text-black text-[13px] font-bold hover:bg-zinc-200 active:scale-[0.98] transition-all cursor-pointer shadow-md disabled:opacity-40">
          {isLoading ? 'Verifying...' : 'Verify'}
        </button>
      </form>

      <p className="mt-5 text-center text-[12px] text-zinc-550 font-semibold leading-relaxed">
        Didn&apos;t receive a code? <button className="text-white font-extrabold hover:underline cursor-pointer">Resend</button>
      </p>

      <p className="mt-4 text-center text-[12px] text-zinc-550 font-semibold">
        <Link href="/login" className="text-white font-extrabold hover:underline">Back to sign in</Link>
      </p>
    </AuthSplitLayout>
  )
}
