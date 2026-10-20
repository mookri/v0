// /components/ui/Preloader.tsx
"use client"

import { motion, AnimatePresence } from "framer-motion"
import clsx from "clsx"
import { useLoading } from "@/context/LoadingContext"
import Image from "next/image"

export default function Preloader({
  className,
  size = 84,
  logoSrc = "/logo.svg", // change to your logo path e.g. /UPFLAME.png or /logo.svg
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
          transition={{ duration: 0.28 }}
          className={clsx(
            "fixed inset-0 z-[9998] flex items-center justify-center bg-background/95 backdrop-blur-sm",
            className
          )}
          aria-live="polite"
        >
          <motion.div
            initial={{ scale: 0.9, rotate: -6 }}
            animate={{ scale: [0.96, 1.03, 0.98], rotate: [ -3, 2, 0 ] }}
            transition={{ repeat: Infinity, duration: 1.6, ease: "easeInOut" }}
            className="flex items-center gap-4 flex-col"
            role="status"
            aria-label="Loading"
          >
            <div className="flex items-center justify-center">
              {/* Replace with Image or inline SVG for crispness */}
              <Image src={logoSrc} alt="Navdhya" width={size} height={size} priority />
            </div>

            <div className="text-center">
              <p className="text-sm font-medium text-foreground/80">Preparing the next page…</p>
            </div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  )
}
