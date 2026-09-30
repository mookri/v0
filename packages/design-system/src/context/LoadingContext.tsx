// /context/LoadingContext.tsx
"use client"

import React, { createContext, useCallback, useContext, useEffect, useRef, useState } from "react"

type LoadingContextType = {
  isLoading: boolean
  start: (key?: string) => void
  done: (key?: string) => void
  setForceLoading: (v: boolean) => void
}

const LoadingContext = createContext<LoadingContextType | undefined>(undefined)

export const LoadingProvider = ({ children }: { children: React.ReactNode }) => {
  const [isLoading, setIsLoading] = useState(false)

  // ref-count map to avoid race conditions when many async tasks start/stop
  const countsRef = useRef<Map<string, number>>(new Map())
  const timerRef = useRef<number | null>(null)
  const MIN_VISIBLE_MS = 450 // minimum time loader remains visible to avoid flicker
  const lastShownRef = useRef<number | null>(null)

  const applyState = useCallback((loading: boolean) => {
    if (!loading) {
      // ensure minimum visible time
      const elapsed = lastShownRef.current ? Date.now() - lastShownRef.current : Infinity
      if (elapsed < MIN_VISIBLE_MS) {
        // schedule to hide after remaining time
        if (timerRef.current) window.clearTimeout(timerRef.current)
        timerRef.current = window.setTimeout(() => {
          setIsLoading(false)
          timerRef.current = null
          lastShownRef.current = null
        }, MIN_VISIBLE_MS - elapsed)
        return
      }
      setIsLoading(false)
      lastShownRef.current = null
      return
    }

    // show immediately
    if (timerRef.current) {
      window.clearTimeout(timerRef.current)
      timerRef.current = null
    }
    lastShownRef.current = Date.now()
    setIsLoading(true)
  }, [])

  const start = useCallback((key = "__default__") => {
    const counts = countsRef.current
    counts.set(key, (counts.get(key) || 0) + 1)
    applyState(true)
  }, [applyState])

  const done = useCallback((key = "__default__") => {
    const counts = countsRef.current
    if (!counts.has(key)) {
      // nothing to decrement
      return
    }
    const newVal = (counts.get(key) || 0) - 1
    if (newVal <= 0) counts.delete(key)
    else counts.set(key, newVal)

    if (counts.size === 0) {
      applyState(false)
    }
  }, [applyState])

  // Allows forcibly setting loading (useful for debugging/manual control)
  const setForceLoading = useCallback((v: boolean) => {
    if (v) {
      start("__force__")
    } else {
      done("__force__")
    }
  }, [start, done])

  // cleanup when unmount
  useEffect(() => {
    return () => {
      if (timerRef.current) window.clearTimeout(timerRef.current)
      countsRef.current.clear()
    }
  }, [])

  return (
    <LoadingContext.Provider value={{ isLoading, start, done, setForceLoading }}>
      {children}
    </LoadingContext.Provider>
  )
}

export const useLoading = (): LoadingContextType => {
  const ctx = useContext(LoadingContext)
  if (!ctx) throw new Error("useLoading must be used within LoadingProvider")
  return ctx
}