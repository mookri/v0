'use client'

import { motion } from 'framer-motion'

interface AuthSocialButtonsProps {
  onGoogle?: () => void
  onApple?: () => void
  showApple?: boolean
}

function SocialButton({
  children,
  icon,
  onClick
}: {
  children: React.ReactNode
  icon: React.ReactNode
  onClick?: () => void
}) {
  return (
    <motion.button
      type="button"
      onClick={onClick}
      whileHover={{
        y: -0.5
      }}
      whileTap={{
        scale: 0.99
      }}
      transition={{
        duration: 0.2,
        ease: [0.16, 1, 0.3, 1]
      }}
      className="
        group
        w-full
        h-[42px]
        sm:h-[44px]
        px-3
        rounded-[12px]
        bg-[#FAFAFA]
        border
        border-[#E2D4C0]
        hover:bg-[#F3F3F3]
        text-[#111]
        text-[12.5px]
        sm:text-[13px]
        font-bold
        tracking-[-0.015em]
        flex
        items-center
        justify-center
        gap-2
        transition-all
        focus:outline-none
      "
    >
      <span className="flex items-center opacity-80 group-hover:opacity-100 transition-opacity">
        {icon}
      </span>

      <span>{children}</span>
    </motion.button>
  )
}

export function AuthSocialButtons({
  onGoogle,
  onApple,
  showApple = true
}: AuthSocialButtonsProps) {
  return (
    <div className="flex gap-2.5">
      <SocialButton
        onClick={onGoogle}
        icon={
          <svg
            className="w-[16px] h-[16px]"
            viewBox="0 0 24 24"
            fill="none"
          >
            <path
              d="M21.805 10.023h-9.79v3.955h5.607c-.242 1.274-.97 2.354-2.06 3.08v2.55h3.328c1.944-1.79 3.066-4.42 3.066-7.536 0-.674-.06-1.33-.151-2.049z"
              fill="#4285F4"
            />
            <path
              d="M12.015 22c2.777 0 5.108-.917 6.81-2.493l-3.328-2.55c-.917.615-2.08.989-3.482.989-2.686 0-4.965-1.813-5.78-4.248H2.826v2.665A9.984 9.984 0 0012.015 22z"
              fill="#34A853"
            />
            <path
              d="M6.235 13.698A5.988 5.988 0 015.9 12c0-.589.101-1.16.283-1.698V7.637H2.826A9.988 9.988 0 002 12c0 1.607.384 3.126 1.06 4.363l3.175-2.665z"
              fill="#FBBC05"
            />
            <path
              d="M12.015 6.055c1.511 0 2.868.52 3.936 1.54l2.95-2.95C17.117 2.988 14.786 2 12.015 2A9.984 9.984 0 002.826 7.637l3.357 2.665c.815-2.435 3.094-4.247 5.832-4.247z"
              fill="#EA4335"
            />
          </svg>
        }
      >
        Google
      </SocialButton>

      {showApple && (
        <SocialButton
          onClick={onApple}
          icon={
            <svg
              className="w-[16px] h-[16px]"
              viewBox="0 0 24 24"
              fill="currentColor"
            >
              <path d="M16.365 1.43c0 1.14-.414 2.19-1.09 2.96-.73.83-1.92 1.46-3.03 1.37-.14-1.09.42-2.26 1.08-3 .72-.8 1.97-1.38 3.04-1.33zM20.46 17.2c-.52 1.2-.77 1.74-1.44 2.78-.94 1.45-2.26 3.26-3.9 3.28-1.47.01-1.85-.95-3.84-.94-1.99.01-2.4.96-3.86.95-1.64-.03-2.9-1.66-3.84-3.11-2.63-4.03-2.9-8.76-1.29-11.24 1.15-1.77 2.97-2.81 4.68-2.81 1.75 0 2.86.96 4.31.96 1.4 0 2.25-.96 4.3-.96 1.53 0 3.15.84 4.3 2.28-3.77 2.06-3.15 7.44.58 8.81z" />
            </svg>
          }
        >
          Apple
        </SocialButton>
      )}
    </div>
  )
}