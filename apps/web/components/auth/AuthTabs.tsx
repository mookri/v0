'use client'

import { ReactNode } from 'react'
import { motion } from 'framer-motion'
import { Mail, Smartphone } from 'lucide-react'

type AuthTab = 'email' | 'phone'

interface AuthTabsProps {
  activeTab: AuthTab
  onTabChange: (tab: AuthTab) => void
  emailContent: ReactNode
  phoneContent: ReactNode
}

const tabs = [
  {
    key: 'phone' as const,
    label: 'Phone',
    icon: Smartphone
  },
  {
    key: 'email' as const,
    label: 'Email',
    icon: Mail
  }
]

export function AuthTabs({
  activeTab,
  onTabChange,
  emailContent,
  phoneContent
}: AuthTabsProps) {
  return (
    <div className="space-y-6">
      {/* Human-designed segmented control */}
      <div
        className="
          relative
          grid
          grid-cols-2
          p-1
          rounded-full
          bg-[#FAFAFA]
          border
          border-[rgba(17,17,17,0.06)]
          backdrop-blur-sm
        "
        role="tablist"
        aria-label="Authentication method"
      >
        {tabs.map((tab) => {
          const Icon = tab.icon
          const active = activeTab === tab.key

          return (
            <button
              key={tab.key}
              type="button"
              role="tab"
              aria-selected={active}
              onClick={() => onTabChange(tab.key)}
              className="
                relative
                h-[40px]
                sm:h-[44px]
                rounded-full
                flex
                items-center
                justify-center
                gap-2
                text-[13px]
                sm:text-[14px]
                font-bold
                transition-colors
                focus:outline-none
              "
            >
              {active && (
                <motion.div
                  layoutId="auth-tab-indicator"
                  className="
                    absolute
                    inset-0
                    rounded-full
                    bg-white
                    border
                    border-[rgba(17,17,17,0.05)]
                    shadow-[0_2px_8px_rgba(0,0,0,0.04)]
                  "
                  transition={{
                    type: 'spring',
                    stiffness: 350,
                    damping: 30
                  }}
                />
              )}

              <span className="relative z-10 flex items-center gap-2">
                <Icon
                  className={`
                    w-3.5 h-3.5 sm:w-4 sm:h-4
                    ${active
                      ? 'text-[#111111]'
                      : 'text-[#888888]'
                    }
                  `}
                />

                <span
                  className={
                    active
                      ? 'text-[#111111]'
                      : 'text-[#888888]'
                  }
                >
                  {tab.label}
                </span>
              </span>
            </button>
          )
        })}
      </div>

      {/* Content */}
      <motion.div
        key={activeTab}
        initial={{ opacity: 0, y: 8 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{
          duration: 0.24,
          ease: [0.16, 1, 0.3, 1]
        }}
        className="min-h-[280px]"
      >
        {activeTab === 'phone'
          ? phoneContent
          : emailContent}
      </motion.div>
    </div>
  )
}