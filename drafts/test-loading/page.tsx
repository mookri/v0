'use client'

import { useLoading } from '@/context/LoadingContext'
import { useEffect } from 'react'

export default function TestLoadingPage() {
  const { setForceLoading } = useLoading()

  useEffect(() => {
    // Set loading to true when component mounts
    setForceLoading(true)
    
    // Set loading to false after 3 seconds
    const timer = setTimeout(() => {
      setForceLoading(false)
    }, 3000)
    
    return () => clearTimeout(timer)
  }, [setForceLoading])

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4">
      <h1 className="text-2xl font-bold mb-4">Preloader Test Page</h1>
      <p className="text-muted-foreground">The preloader should be visible now...</p>
      <button 
        onClick={() => setForceLoading(true)}
        className="mt-4 px-4 py-2 bg-primary text-primary-foreground rounded hover:opacity-90"
      >
        Show Preloader
      </button>
      <button 
        onClick={() => setForceLoading(false)}
        className="mt-2 px-4 py-2 bg-secondary text-secondary-foreground rounded hover:opacity-90"
      >
        Hide Preloader
      </button>
    </div>
  )
}