'use client'

import React, { useState, useEffect } from 'react'
import Preloader from '@/components/ui/preloader'

const TestLoading = () => {
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    // Simulate loading for 3 seconds
    const timer = setTimeout(() => {
      setLoading(false)
    }, 3000)

    return () => clearTimeout(timer)
  }, [])

  if (loading) {
    return (
      <div className="fixed inset-0 flex items-center justify-center bg-background z-50">
        <Preloader size={70} className="text-primary" />
      </div>
    )
  }

  return (
    <div className="p-4">
      <h1 className="text-2xl font-bold">Content Loaded Successfully!</h1>
      <p className="mt-2">The preloader is working correctly.</p>
    </div>
  )
}

export default TestLoading