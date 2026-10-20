'use client'

import { useRouter } from 'next/navigation'
import { useState } from 'react'
import { motion } from 'framer-motion'
import { AuthSplitLayout } from '@/components/auth/AuthSplitLayout'
import { AuthHeader } from '@/components/auth/AuthHeader'
import {
  CheckCircle2,
  Mail,
  UserCheck2
} from 'lucide-react'

type Variant =
  | 'verify'
  | 'select-role'
  | 'complete-profile'
  | 'pandit-application'
  | 'vendor-application'
  | 'invite-accept'

interface AuthRoutePanelProps {
  variant: Variant
}

const roles = [
  {
    value: 'User',
    label: 'Book rituals for family and personal guidance',
    href: '/user/overview'
  },
  {
    value: 'Pandit',
    label: 'Manage ceremonies and bookings',
    href: '/pandit/overview'
  },
  {
    value: 'Vendor',
    label: 'Handle samagri supply chain and inventory logistics',
    href: '/vendor/overview'
  },
  {
    value: 'Manager',
    label: 'Coordinate operational quality and regional staff',
    href: '/manager/overview'
  },
  {
    value: 'Admin',
    label: 'Control regional platform systems and settings',
    href: '/admin/overview'
  }
]

export default function AuthRoutePanel({
  variant
}: AuthRoutePanelProps) {
  const router = useRouter()
  const [selectedRole, setSelectedRole] = useState('User')
  const [submitted, setSubmitted] = useState(false)

  const primaryButton =
    'group relative w-full h-[50px] sm:h-[54px] rounded-[20px] bg-[#111111] text-white text-[14px] sm:text-[15px] font-bold tracking-[-0.01em] flex items-center justify-center overflow-hidden transition-all duration-200 shadow-[0_4px_12px_rgba(17,17,17,0.04)] hover:shadow-[0_8px_20px_rgba(17,17,17,0.06)] active:scale-[0.985]'

  const secondaryInput =
    'w-full h-[50px] sm:h-[54px] rounded-[20px] px-5 bg-[#FAFAFA] border border-[rgba(17,17,17,0.06)] text-[#111111] text-[14px] sm:text-[15px] font-bold outline-none placeholder:text-[#A0A0A0] transition-all focus:border-[#91713B] focus:bg-white focus:ring-[4px] focus:ring-[#91713B]/[0.03]'

  if (variant === 'verify') {
    return (
      <AuthSplitLayout>
        <div className="space-y-6">
          <AuthHeader
            title="Verify access"
            subtitle="A secure verification link has been sent to your email."
          />

          <div className="flex flex-col items-center text-center space-y-5 pt-2">
            <div className="w-16 h-16 rounded-full border border-[#91713B]/20 bg-[#91713B]/[0.04] flex items-center justify-center shadow-[0_4px_16px_rgba(145,113,59,0.04)]">
              <Mail className="w-6 h-6 text-[#91713B]" />
            </div>

            <p className="text-[#555555] text-[13.5px] leading-relaxed max-w-[32ch] font-semibold">
              Please check your inbox and tap the link to confirm your identity.
            </p>

            <button
              onClick={() => router.push('/login')}
              className={primaryButton}
            >
              Continue to Sign In
            </button>
          </div>
        </div>
      </AuthSplitLayout>
    )
  }

  if (variant === 'select-role') {
    return (
      <AuthSplitLayout>
        <div className="space-y-6">
          <AuthHeader
            title="Choose workspace"
            subtitle="Select your operational environment and dashboard"
          />

          <div className="space-y-2.5">
            {roles.map((role) => (
              <motion.button
                key={role.value}
                whileTap={{ scale: 0.995 }}
                onClick={() => setSelectedRole(role.value)}
                className={`w-full rounded-[18px] p-4 border transition-all text-left outline-none ${selectedRole === role.value
                  ? 'border-[#91713B] bg-[#91713B]/[0.02] shadow-[0_4px_12px_rgba(145,113,59,0.03)]'
                  : 'border-[rgba(17,17,17,0.06)] bg-[#FAFAFA] hover:bg-[#F3F3F3]'
                  }`}
              >
                <div className="flex items-center justify-between">
                  <span className="text-[#111111] font-bold text-[14px] sm:text-[15px]">
                    {role.value}
                  </span>

                  {selectedRole === role.value && (
                    <UserCheck2 className="w-4 h-4 text-[#91713B]" />
                  )}
                </div>

                <p className="text-[#555555] text-[12px] sm:text-[12.5px] font-semibold mt-0.5">
                  {role.label}
                </p>
              </motion.button>
            ))}
          </div>

          <button
            className={primaryButton}
            onClick={() => {
              const next =
                roles.find(r => r.value === selectedRole)?.href ||
                '/user/overview'

              router.push(next)
            }}
          >
            Enter Workspace
          </button>
        </div>
      </AuthSplitLayout>
    )
  }

  if (variant === 'invite-accept') {
    return (
      <AuthSplitLayout>
        <div className="space-y-6">
          <AuthHeader
            title="Accept invitation"
            subtitle="Join the operations network"
          />

          <div className="rounded-[18px] border border-[rgba(17,17,17,0.06)] bg-[#FAFAFA] p-6">
            <p className="text-[#111] font-bold text-[13px] uppercase tracking-wide">
              Invited Role
            </p>

            <p className="text-[#91713B] font-bold text-[16px] mt-0.5">
              Operations Manager
            </p>

            <p className="text-[#555555] text-[13.5px] leading-relaxed font-semibold mt-2.5">
              Coordinate regional ritual logistics, Samagri supply chain, and service excellence.
            </p>
          </div>

          <div className="space-y-2.5 pt-2">
            <button
              className={primaryButton}
              onClick={() => router.push('/select-role')}
            >
              Accept Invitation
            </button>

            <button
              onClick={() => router.push('/login')}
              className="w-full h-[50px] sm:h-[54px] rounded-[20px] bg-[#FAFAFA] border border-[rgba(17,17,17,0.06)] hover:bg-[#F3F3F3] text-[#111111] font-bold text-[14px] sm:text-[15px] transition-colors outline-none"
            >
              Decline
            </button>
          </div>
        </div>
      </AuthSplitLayout>
    )
  }

  return (
    <AuthSplitLayout>
      <div className="space-y-6">
        <AuthHeader
          title={
            submitted
              ? 'Submission complete'
              : 'Complete profile'
          }
          subtitle={
            submitted
              ? 'Your credentials are under active review.'
              : 'Provide your operational details below'
          }
        />

        {submitted ? (
          <div className="flex flex-col items-center text-center space-y-5 pt-4">
            <div className="w-16 h-16 rounded-full border border-[#91713B]/20 bg-[#91713B]/[0.04] flex items-center justify-center shadow-[0_4px_16px_rgba(145,113,59,0.04)]">
              <CheckCircle2 className="w-7 h-7 text-[#91713B]" />
            </div>

            <p className="text-[#555555] font-bold text-[14px]">
              Application submitted successfully.
            </p>
          </div>
        ) : (
          <form
            onSubmit={(e) => {
              e.preventDefault()
              setSubmitted(true)
            }}
            className="space-y-3.5"
          >
            <div className="space-y-1.5 flex flex-col">
              <span className="text-[11px] font-bold tracking-[0.08em] uppercase text-[#757575]">
                Full Name
              </span>
              <input
                className={secondaryInput}
                placeholder="e.g. Rahul Sharma"
              />
            </div>

            <div className="space-y-1.5 flex flex-col">
              <span className="text-[11px] font-bold tracking-[0.08em] uppercase text-[#757575]">
                City
              </span>
              <input
                className={secondaryInput}
                placeholder="e.g. New Delhi"
              />
            </div>

            <div className="space-y-1.5 flex flex-col">
              <span className="text-[11px] font-bold tracking-[0.08em] uppercase text-[#757575]">
                Email Address
              </span>
              <input
                className={secondaryInput}
                type="email"
                placeholder="e.g. rahul@company.com"
              />
            </div>

            <div className="space-y-1.5 flex flex-col">
              <span className="text-[11px] font-bold tracking-[0.08em] uppercase text-[#757575]">
                Specialization Details
              </span>
              <textarea
                className="w-full min-h-[120px] rounded-[20px] px-5 py-4 bg-[#FAFAFA] border border-[rgba(17,17,17,0.06)] text-[#111111] font-bold focus:outline-none focus:border-[#91713B] focus:bg-white focus:ring-[4px] focus:ring-[#91713B]/[0.03] transition-all placeholder:text-[#A0A0A0] text-[14px] sm:text-[15px]"
                placeholder="Describe your operational specialization..."
              />
            </div>

            <button
              type="submit"
              className={primaryButton}
            >
              Submit Application
            </button>
          </form>
        )}
      </div>
    </AuthSplitLayout>
  )
}