'use client'

import Link from 'next/link'
import { ArrowLeft, Home, Search, Users } from 'lucide-react'

export default function NotFound() {
  return (
    <div className="min-h-screen bg-background flex items-center justify-center px-4 sm:px-6 py-12">
      <div className="max-w-2xl w-full text-center">
        <div className="mb-8">
          <div className="w-24 h-24 mx-auto mb-6">
            <div className="relative w-full h-full">
              <div className="absolute inset-0 bg-[#0E3F2E] rounded-full flex items-center justify-center">
                <span className="text-white text-4xl font-bold">404</span>
              </div>
            </div>
          </div>
          
          <h1 className="text-3xl sm:text-4xl font-serif font-bold text-foreground mb-4">
            Page Not Found
          </h1>
          
          <p className="text-muted-foreground text-base sm:text-lg mb-8 max-w-lg mx-auto">
            Sorry, we couldn't find the page you're looking for. It might have been removed, had its name changed, or is temporarily unavailable.
          </p>
        </div>
        
        <div className="flex flex-col sm:flex-row gap-4 justify-center mb-10">
          <Link 
            href="/"
            className="inline-flex items-center justify-center gap-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md"
          >
            <Home className="w-4 h-4" />
            <span>Go to Homepage</span>
          </Link>
          
          <Link 
            href="/pandits"
            className="inline-flex items-center justify-center gap-2 bg-card border border-border text-foreground hover:bg-muted font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md"
          >
            <Users className="w-4 h-4" />
            <span>Browse Pandits</span>
          </Link>
        </div>
        
        <div className="border-t border-border pt-8">
          <p className="text-muted-foreground text-sm">
            Need help?{' '}
            <Link href="/contact" className="text-[#0E3F2E] hover:underline">
              Contact our support team
            </Link>{' '}
            or{' '}
            <Link href="/search" className="text-[#0E3F2E] hover:underline">
              try searching
            </Link>{' '}
            for what you need.
          </p>
        </div>
      </div>
    </div>
  )
}