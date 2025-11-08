// /hooks/useNavigationLoader.tsx
"use client"

import { useEffect } from "react"
import { usePathname, useSearchParams } from "next/navigation"
import { useLoading } from "@/context/LoadingContext"

/**
 * Triggers the loading context when pathname/searchParams change.
 * Works with transitions from App Router where router.events is not available.
 */
export function useNavigationLoader({ minDisplay = 600 }: { minDisplay?: number } = {}) {
  const pathname = usePathname()
  const search = useSearchParams()
  const { start, done } = useLoading()

  useEffect(() => {
    // Start a navigation token; we'll mark it done after a small delay.
    // Use a unique key per navigation to track ref-counting safely.
    const token = `nav:${Date.now()}`
    start(token)

    // Set a small delay, but we will call done from layout once client rendered if needed.
    // This timer ensures spinner shows for at least a perceptible time while page loads.
    const timeout = window.setTimeout(() => {
      done(token)
    }, minDisplay)

    return () => {
      clearTimeout(timeout)
      // ensure we try to mark done (defensive)
      done(token)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pathname, search?.toString()])
}
