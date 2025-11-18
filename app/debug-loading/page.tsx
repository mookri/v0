'use client'

import { useLoading } from '@/context/LoadingContext'
import { useEffect } from 'react'

export default function DebugLoadingPage() {
  const { isLoading, setForceLoading } = useLoading()

  useEffect(() => {
    console.log('Debug page mounted, isLoading:', isLoading)
  }, [isLoading])

  const showLoader = () => {
    console.log('Setting loading to true')
    setForceLoading(true)
  }

  const hideLoader = () => {
    console.log('Setting loading to false')
    setForceLoading(false)
  }

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4">
      <h1 className="text-2xl font-bold mb-4">Debug Loading Page</h1>
      <p className="text-muted-foreground mb-2">Current isLoading state: {isLoading ? 'true' : 'false'}</p>
      <p className="text-muted-foreground mb-6">Check browser console for logs</p>
      
      <div className="flex flex-col gap-4">
        <button 
          onClick={showLoader}
          className="px-4 py-2 bg-primary text-primary-foreground rounded hover:opacity-90"
        >
          Show Loader
        </button>
        <button 
          onClick={hideLoader}
          className="px-4 py-2 bg-secondary text-secondary-foreground rounded hover:opacity-90"
        >
          Hide Loader
        </button>
      </div>
    </div>
  )
}