// /components/NavigationLoader.tsx
"use client"

import { useNavigationLoader } from "@/hooks/useNavigationLoader"
import ProgressBar from "@/components/ui/ProgressBar"
import Preloader from "@/components/ui/preloader"
import { Suspense } from "react"

export default function NavigationLoader() {
  return (
    <Suspense fallback={null}>
      <NavigationLoaderContent />
    </Suspense>
  )
}

function NavigationLoaderContent() {
  useNavigationLoader({ minDisplay: 700 })
  
  return (
    <>
      <ProgressBar />
      <Preloader />
    </>
  )
}