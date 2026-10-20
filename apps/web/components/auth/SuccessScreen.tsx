'use client'

import React, { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { motion } from 'framer-motion'

interface SuccessScreenProps {
  animationVariants: any
}

export function SuccessScreen({
  animationVariants
}: SuccessScreenProps) {
  const router = useRouter()

  useEffect(() => {
    const timer = setTimeout(() => {
      router.push('/user/overview')
    }, 2600)

    return () => clearTimeout(timer)
  }, [router])

  return (
    <motion.div
      key="success"
      variants={animationVariants}
      initial="initial"
      animate="animate"
      exit="exit"
      className="relative"
    >
      <div
        className="
          flex
          flex-col
          items-center
          justify-center
          py-8
          text-center
          relative
          overflow-hidden
        "
      >
        {/* outer aura */}
        <motion.div
          className="
            absolute
            w-[260px]
            h-[260px]
            rounded-full
            border
            border-[#91713B]/10
          "
          initial={{
            scale: 0.5,
            opacity: 0
          }}
          animate={{
            scale: [1, 1.8],
            opacity: [0.18, 0]
          }}
          transition={{
            duration: 2.8,
            repeat: Infinity,
            ease: 'easeOut'
          }}
        />

        {/* inner aura */}
        <motion.div
          className="
            absolute
            w-[180px]
            h-[180px]
            rounded-full
            border
            border-[#91713B]/12
          "
          initial={{
            scale: 0.6,
            opacity: 0
          }}
          animate={{
            scale: [1, 1.45],
            opacity: [0.22, 0]
          }}
          transition={{
            duration: 2.4,
            delay: 0.4,
            repeat: Infinity,
            ease: 'easeOut'
          }}
        />

        {/* core */}
        <motion.div
          className="
            relative
            z-10
            w-20
            h-20
            rounded-full
            flex
            items-center
            justify-center
            bg-[#91713B]/10
            border
            border-[#91713B]/20
            shadow-[0_4px_24px_rgba(145,113,59,0.12)]
          "
          initial={{
            scale: 0.82,
            opacity: 0
          }}
          animate={{
            scale: 1,
            opacity: 1
          }}
          transition={{
            duration: 0.8,
            ease: [0.16, 1, 0.3, 1]
          }}
        >
          {/* soft pulse */}
          <motion.div
            className="
              absolute inset-0
              rounded-full
              bg-[#91713B]/12
            "
            animate={{
              scale: [1, 1.14, 1]
            }}
            transition={{
              duration: 2.2,
              repeat: Infinity
            }}
          />

          {/* check */}
          <svg
            className="
              w-8 h-8
              text-[#91713B]
              relative z-10
            "
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={2.8}
          >
            <motion.path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M5 13l4 4L19 7"
              initial={{
                pathLength: 0
              }}
              animate={{
                pathLength: 1
              }}
              transition={{
                duration: 0.8,
                delay: 0.35
              }}
            />
          </svg>
        </motion.div>

        {/* text */}
        <motion.h2
          className="
            mt-6
            text-[20px]
            sm:text-[22px]
            font-bold
            tracking-[-0.03em]
            text-[#111111]
          "
          initial={{
            opacity: 0,
            y: 8
          }}
          animate={{
            opacity: 1,
            y: 0
          }}
          transition={{
            delay: 0.45
          }}
        >
          Access Granted
        </motion.h2>

        <motion.p
          className="
            mt-1.5
            text-[12px]
            sm:text-[12.5px]
            font-medium
            text-[#6B6B6B]
            max-w-[34ch]
            leading-relaxed
          "
          initial={{
            opacity: 0
          }}
          animate={{
            opacity: 1
          }}
          transition={{
            delay: 0.65
          }}
        >
          Authentication successful. Redirecting you to your account...
        </motion.p>

        {/* transition signal */}
        <motion.div
          className="
            mt-6
            h-[2px]
            w-20
            rounded-full
            bg-[#91713B]/18
            overflow-hidden
          "
        >
          <motion.div
            className="
              h-full
              bg-[#91713B]
            "
            initial={{ x: '-100%' }}
            animate={{ x: '100%' }}
            transition={{
              duration: 2.2,
              ease: 'linear'
            }}
          />
        </motion.div>
      </div>
    </motion.div>
  )
}