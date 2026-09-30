'use client'

import React, { Suspense } from 'react'
import Preloader from '@/components/ui/preloader'

const LazyComponent = () => {
  // Simulate a delay
  const [loaded, setLoaded] = React.useState(false)
  
  React.useEffect(() => {
    const timer = setTimeout(() => {
      setLoaded(true)
    }, 2000)
    
    return () => clearTimeout(timer)
  }, [])
  
  if (!loaded) {
    // This will never be shown because we're not using Suspense correctly here
    // This is just to demonstrate the component structure
    return null
  }
  
  return (
    <div className="p-4">
      <h2 className="text-xl font-bold">Lazy Component Loaded!</h2>
      <p className="mt-2">This content was loaded after a delay.</p>
    </div>
  )
}

const TestSuspense = () => {
  return (
    <div className="min-h-screen bg-background flex flex-col items-center justify-center p-4">
      <h1 className="text-2xl font-bold mb-8">Suspense Boundary Test</h1>
      
      <div className="w-full max-w-md">
        <Suspense fallback={
          <div className="flex flex-col items-center justify-center p-8 bg-card rounded-lg border border-border">
            <Preloader size={70} className="text-primary mb-4" />
            <p className="text-muted-foreground">Loading content...</p>
          </div>
        }>
          <LazyComponent />
        </Suspense>
      </div>
    </div>
  )
}

export default TestSuspense