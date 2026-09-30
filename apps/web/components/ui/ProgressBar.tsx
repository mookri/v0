// /components/ui/ProgressBar.tsx
"use client"

import { motion, useAnimation } from "framer-motion"
import { useEffect } from "react"
import { useLoading } from "@/context/LoadingContext"

export default function ProgressBar() {
  const { isLoading } = useLoading()
  const controls = useAnimation()

  useEffect(() => {
    let jitter: any = null

    if (isLoading) {
      // Start a progressive animation: 0% -> ~80% (jitter)
      controls.set({ width: "0%" })
      controls.start({
        width: ["0%", "25%", "50%", "70%", "80%"],
        transition: { duration: 2.0, ease: "easeOut", times: [0, 0.25, 0.6, 0.85, 1.0] },
      })

      // add small slow increase to 90% while loading is active
      jitter = setInterval(() => {
        controls.start({
          width: `${Math.min(98, Math.random() * 20 + 65)}%`,
          transition: { duration: 0.9 },
        })
      }, 1800)
    } else {
      // Complete to 100% then fade out
      controls.start({ width: "100%", transition: { duration: 0.2 } }).then(() => {
        controls.start({ opacity: 0, transition: { duration: 0.25 } })
      })
      if (jitter) clearInterval(jitter)
    }

    return () => {
      if (jitter) clearInterval(jitter)
    }
  }, [isLoading, controls])

  return (
    <div aria-hidden="true" className="fixed top-0 left-0 right-0 h-1 z-[9999] pointer-events-none">
      <motion.div
        animate={controls}
        initial={{ width: "0%", opacity: 1 }}
        className="h-full bg-gradient-to-r from-amber-400 via-amber-500 to-rose-500 shadow-md"
        style={{ transformOrigin: "0% 50%" }}
      />
    </div>
  )
}
