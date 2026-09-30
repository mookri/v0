'use client'

import { useMemo } from 'react'

interface PasswordStrengthBarProps {
  password: string
}

type StrengthLevel = 'weak' | 'fair' | 'good' | 'strong'

function getStrengthLevel(password: string): { level: StrengthLevel; score: number; label: string } {
  let score = 0

  if (password.length >= 8) score++
  if (password.length >= 12) score++
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++
  if (/\d/.test(password)) score++
  if (/[^a-zA-Z0-9]/.test(password)) score++

  if (score <= 1) return { level: 'weak', score: 1, label: 'Weak security' }
  if (score <= 2) return { level: 'fair', score: 2, label: 'Fair strength' }
  if (score <= 3) return { level: 'good', score: 3, label: 'Good security' }
  return { level: 'strong', score: 4, label: 'Excellent security' }
}

const barColors: Record<StrengthLevel, string> = {
  weak: 'bg-red-500/70 shadow-[0_0_8px_rgba(239,68,68,0.3)]',
  fair: 'bg-amber-500/70 shadow-[0_0_8px_rgba(245,158,11,0.3)]',
  good: 'bg-emerald-500/70 shadow-[0_0_8px_rgba(16,185,129,0.3)]',
  strong: 'bg-[#f39c12] shadow-[0_0_10px_rgba(243,156,18,0.4)]',
}

const textColors: Record<StrengthLevel, string> = {
  weak: 'text-red-400',
  fair: 'text-amber-400',
  good: 'text-emerald-400',
  strong: 'text-[#f39c12]',
}

export function PasswordStrengthBar({ password }: PasswordStrengthBarProps) {
  const strength = useMemo(() => getStrengthLevel(password), [password])

  if (!password) return null

  return (
    <div className="mt-2.5 space-y-1.5">
      <div className="flex gap-1.5">
        {Array.from({ length: 4 }, (_, i) => (
          <div
            key={i}
            className={`h-1 flex-1 rounded-full transition-all duration-500 ${
              i < strength.score ? barColors[strength.level] : 'bg-white/[0.04]'
            }`}
          />
        ))}
      </div>
      <p className={`text-[10px] font-bold uppercase tracking-widest font-mono ${textColors[strength.level]}`}>
        {strength.label}
      </p>
    </div>
  )
}

