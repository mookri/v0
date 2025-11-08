// /components/ui/Preloader.tsx
"use client"

import { motion, AnimatePresence } from "framer-motion"
import clsx from "clsx"
import { useLoading } from "@/context/LoadingContext"
import Image from "next/image"

export default function Preloader({
  className,
  size = 70,
  logoSrc = "/navdhya-logo.png", 
}: {
  className?: string
  size?: number
  logoSrc?: string
}) {
  const { isLoading } = useLoading()

  return (
    <AnimatePresence>
      {isLoading && (
        <motion.div
          key="global-preloader"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.15 }}
          className={clsx(
            "fixed inset-0 z-[9998] flex items-center justify-center bg-background/75",
            className
          )}
          aria-live="polite"
        >
          <div className="flex flex-col items-center gap-3">
            {/* Slightly Larger Circular Loader with Logo */}
            <div className="relative w-16 h-16 flex items-center justify-center">
              {/* Logo in the center */}
              <div className="absolute inset-0 flex items-center justify-center z-10">
                <Image 
                  src={logoSrc} 
                  alt="Navdhya" 
                  width={size * 0.6} 
                  height={size * 0.6} 
                  priority 
                />
              </div>
              
              {/* Enhanced circular loader */}
              <motion.div
                className="absolute inset-0 rounded-full border-2 border-primary border-t-transparent"
                animate={{ rotate: 360 }}
                transition={{
                  duration: 0.6,
                  repeat: Infinity,
                  ease: "linear"
                }}
              />
            </div>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  )
}