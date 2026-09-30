'use client'

import React from 'react'
import { motion } from 'framer-motion'

interface ButtonProps {
  children: React.ReactNode
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  onClick?: React.MouseEventHandler<HTMLButtonElement>
  icon?: React.ReactNode
}

const transition = {
  duration: 0.14,
  ease: [0.16, 1, 0.3, 1] as const
}

export function PrimaryButton({
  children,
  loading = false,
  disabled = false,
  type = 'button',
  onClick
}: ButtonProps) {
  return (
    <motion.button
      type={type}
      disabled={disabled || loading}
      onClick={onClick}
      whileHover={{
        y: -0.5
      }}
      whileTap={{
        scale: 0.985
      }}
      transition={transition}
      className="
        group
        relative
        w-full
        h-[42px]
        sm:h-[44px]
        rounded-[12px]
        bg-gradient-to-b
        from-[#222222]
        to-[#111111]
        hover:from-[#111111]
        hover:to-[#050505]
        text-white
        text-[12.5px]
        sm:text-[13px]
        font-bold
        tracking-[0.02em]
        flex
        items-center
        justify-center
        overflow-hidden
        transition-all
        duration-200
        shadow-[0_4px_12px_rgba(17,17,17,0.04)]
        hover:shadow-[0_8px_24px_rgba(17,17,17,0.08)]
        border
        border-[#111111]
        disabled:opacity-45
        disabled:pointer-events-none
        outline-none
      "
    >
      <div
        className="
          absolute
          inset-0
          bg-gradient-to-b
          from-white/[0.04]
          to-transparent
          opacity-70
        "
      />

      {loading && (
        <motion.div
          className="
            absolute inset-0
            bg-gradient-to-r
            from-transparent
            via-white/[0.12]
            to-transparent
          "
          initial={{ x: '-100%' }}
          animate={{ x: '100%' }}
          transition={{
            duration: 0.9,
            repeat: Infinity,
            ease: 'linear'
          }}
        />
      )}

      <span className="relative z-10">
        {children}
      </span>
    </motion.button>
  )
}

export function SecondaryButton({
  children,
  icon,
  type = 'button',
  onClick
}: ButtonProps) {
  return (
    <motion.button
      type={type}
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
        px-4
        rounded-[12px]
        bg-[#FAFAFA]
        border
        border-[#E2D4C0]
        hover:bg-[#F3F3F3]
        text-[#111]
        text-[12.5px]
        sm:text-[13px]
        font-bold
        tracking-[-0.01em]
        flex
        items-center
        justify-center
        gap-2.5
        transition-all
        focus:outline-none
      "
    >
      {icon && (
        <span className="
          flex
          items-center
          opacity-80
          group-hover:opacity-100
          transition-opacity
        ">
          {icon}
        </span>
      )}

      <span>{children}</span>
    </motion.button>
  )
}