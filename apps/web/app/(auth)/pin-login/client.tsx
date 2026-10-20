'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { AuthSplitLayout } from '@/components/auth/AuthSplitLayout'
import { AuthHeader } from '@/components/auth/AuthHeader'
import { OtpInput } from '@/components/auth/OtpInput'

export default function PinLoginClient() {
  const router = useRouter()
  const [isLoading, setIsLoading] = useState(false)
  const [pin, setPin] = useState('')
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (pin.length < 6) { setError('Enter your 6-digit PIN'); return }
    setError('')
    setIsLoading(true)
    try {
      await new Promise(resolve => setTimeout(resolve, 1500))
      router.push('/user/overview')
    } catch {
      setError('Invalid PIN')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <AuthSplitLayout>
      <AuthHeader title="Enter secure PIN" subtitle="Provide your PIN to unlock dashboard" />
      {error && (
        <div className="mb-4 p-3 rounded-xl bg-red-500/5 border border-red-500/20">
          <p className="text-xs text-red-400 font-medium text-center">{error}</p>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-5">
        <OtpInput value={pin} onChange={(v) => { setPin(v); setError('') }} />
        <button type="submit" disabled={isLoading}
          className="w-full h-11 rounded-full bg-white text-black text-[13px] font-bold hover:bg-zinc-200 active:scale-[0.98] transition-all cursor-pointer shadow-md disabled:opacity-40">
          {isLoading ? 'Verifying PIN...' : 'Verify'}
        </button>
      </form>

      <p className="mt-6 text-center text-[12px] text-zinc-500 font-semibold">
        Not you? <Link href="/login" className="text-white font-extrabold hover:underline">Sign in</Link>
      </p>
    </AuthSplitLayout>
  )
}
