'use client'

import { ReactNode, useEffect } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { motion } from 'framer-motion'
import { acquireScrollLock } from '@/lib/security/scroll-lock'
import type { AuthState } from './UnifiedAuthConsole'

interface AuthSplitLayoutProps {
  children: ReactNode
  title?: string
  state?: AuthState
  transitionTo?: (state: AuthState) => void
}

export function AuthSplitLayout({
  children,
  title = 'Navdhya',
  state,
  transitionTo
}: AuthSplitLayoutProps) {
  useEffect(() => {
    // Reference-counted body scroll lock. The old in-place mutation
    // leaked when two layouts mounted concurrently: the inner one's
    // unmount restored the wrong value, releasing the outer one's
    // lock. The shared helper ref-counts lockers so the body stays
    // locked while at least one caller is active.
    const handle = acquireScrollLock({ lockHtml: true, capHeight: true });
    return () => {
      handle.release();
    };
  }, [])

  return (
    <div className="h-screen max-h-screen flex bg-[#FCFCFC] text-[#111] overflow-hidden">

      {/* LEFT */}
      <section className="w-full lg:w-[40%] lg:flex-shrink-0 flex flex-col bg-gradient-to-br from-[#FCFCFC] to-[#F7F7F4] relative lg:border-r lg:border-[rgba(17,17,17,0.04)] h-full overflow-y-auto no-scrollbar">

        {/* Subtle, extremely finessed atmospheric background setup for mobile viewports */}
        <div className="absolute inset-0 block lg:hidden pointer-events-none opacity-[0.02] mix-blend-multiply filter grayscale overflow-hidden">
          <Image
            src="/traditional-puja-ceremony-setup.jpg"
            alt="Atmospheric Ritual Background"
            fill
            priority
            className="object-cover object-center"
          />
        </div>

        {/* Header */}
        <header className="relative pt-8 lg:pt-10 pb-4 px-6 sm:px-10 lg:px-12 xl:px-24 flex items-center justify-between flex-shrink-0">
          <div className="w-full max-w-[410px] flex items-center justify-between">
            <Link 
              href="/"
              className="flex items-center"
            >
              <Image
                src="/navdhya-logo.png"
                alt={title}
                width={150}
                height={36}
                priority
                className="h-[28px] sm:h-[30px] lg:h-[28px] w-auto opacity-95 transition-all duration-200"
              />
            </Link>

            {state === 'LOGIN' && (
              <div className="flex items-center gap-3 text-[12.5px] sm:text-[13px] font-bold text-[#6B6B6B]">
                <span className="hidden sm:inline font-semibold">Don't have an account?</span>
                <button 
                  onClick={() => transitionTo?.('SIGNUP')}
                  className="h-8 px-4 rounded-full bg-white border border-[#E2D4C0] hover:bg-[#111111] hover:border-[#111111] hover:text-white transition-all duration-200 text-[#111111] text-[12px] font-bold outline-none shadow-[0_1px_2px_rgba(0,0,0,0.02)]"
                >
                  Sign Up
                </button>
              </div>
            )}

            {state === 'SIGNUP' && (
              <div className="flex items-center gap-3 text-[12.5px] sm:text-[13px] font-bold text-[#6B6B6B]">
                <span className="hidden sm:inline font-semibold">Already have an account?</span>
                <button 
                  onClick={() => transitionTo?.('LOGIN')}
                  className="h-8 px-4 rounded-full bg-white border border-[#E2D4C0] hover:bg-[#111111] hover:border-[#111111] hover:text-white transition-all duration-200 text-[#111111] text-[12px] font-bold outline-none shadow-[0_1px_2px_rgba(0,0,0,0.02)]"
                >
                  Sign In
                </button>
              </div>
            )}

            {state !== 'LOGIN' && state !== 'SIGNUP' && (
              <div className="flex items-center gap-2 text-[12px] font-bold text-[#6B6B6B]">
                <span className="w-1.5 h-1.5 rounded-full bg-[#22C55E] animate-pulse" />
                Secure Link
              </div>
            )}
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 flex items-center px-6 sm:px-10 lg:px-12 xl:px-24 py-6 sm:py-8 lg:py-10">
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{
              duration: 0.24,
              ease: [0.16, 1, 0.3, 1]
            }}
            className="w-full max-w-[410px] relative py-2"
          >
            <div className="px-3 sm:px-4">
              {children}
            </div>
          </motion.div>
        </main>
      </section>

      {/* RIGHT */}
      <aside className="hidden lg:flex lg:w-[60%] bg-[#0B0A08] relative overflow-hidden">

        {/* Ambient ritual photography back-bleed for true MNC-grade luxury */}
        <div className="absolute inset-0 z-0">
          <Image
            src="/traditional-hindu-puja-mandap.jpg"
            alt="Sacred Ritual Mandap Backdrop"
            fill
            priority
            className="object-cover object-center scale-[1.01] brightness-[0.45] contrast-[1.08]"
          />
          {/* Subtle vignette/shading gradients */}
          <div className="absolute inset-0 bg-gradient-to-t from-[#0B0A08] via-transparent to-transparent opacity-80" />
          <div className="absolute inset-0 bg-gradient-to-r from-[#0B0A08]/50 to-transparent" />
        </div>

        {/* soft ambient light flares */}
        <div className="absolute top-[8%] right-[5%] w-[480px] h-[480px] rounded-full bg-[#AF8A4E]/[0.08] blur-[100px] mix-blend-screen" />
        <div className="absolute bottom-[10%] left-[5%] w-[380px] h-[380px] rounded-full bg-[#1A4D2E]/[0.05] blur-[80px] mix-blend-screen" />

        {/* Cosmic Constellation Geometry Overlay */}
        <div className="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40">
          <svg className="w-[85%] h-[85%] text-white/[0.04]" viewBox="0 0 100 100" fill="none" stroke="currentColor" strokeWidth="0.08">
            {/* Outer bounds */}
            <circle cx="50" cy="50" r="45" strokeDasharray="1 2" />
            {/* Mid bounds */}
            <circle cx="50" cy="50" r="35" />
            <circle cx="50" cy="50" r="25" strokeDasharray="3 3" />
            {/* Inner bounds */}
            <circle cx="50" cy="50" r="15" />
            <circle cx="50" cy="50" r="6" />
            {/* Crosshairs & Radials */}
            <line x1="5" y1="50" x2="95" y2="50" />
            <line x1="50" y1="5" x2="50" y2="95" />
            {/* Diagonal lines */}
            <line x1="18.2" y1="18.2" x2="81.8" y2="81.8" strokeDasharray="0.5 1.5" />
            <line x1="18.2" y1="81.8" x2="81.8" y2="18.2" strokeDasharray="0.5 1.5" />
            {/* Concentric arc accents */}
            <path d="M 50 15 A 35 35 0 0 1 85 50" strokeWidth="0.15" />
            <path d="M 15 50 A 35 35 0 0 1 50 85" strokeWidth="0.15" />
          </svg>
        </div>

        {/* message */}
        <div className="relative z-10 flex flex-col justify-center px-24 xl:px-28 w-full text-white">

          <div className="max-w-[640px]">

            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/[0.06] border border-white/[0.1] text-[9px] uppercase tracking-[0.25em] text-white/90 font-bold mb-8">
              <span className="w-1.5 h-1.5 rounded-full bg-[#22C55E]" />
              Console Active
            </div>

            <h2 className="text-[64px] xl:text-[72px] leading-[0.95] tracking-[-0.05em] font-sans font-black text-white">
              Calm.
              <br />
              Frictionless.
              <br />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#DFB675] to-[#B08A4E]">Sanctuary.</span>
            </h2>

            <p className="mt-8 text-[15px] sm:text-[16px] leading-[1.6] text-white/70 max-w-[30ch] font-medium">
              Experience premium, secure access to your personal ritual schedule, samagri logistics, and spiritual guidance.
            </p>
          </div>
        </div>
      </aside>
    </div>
  )
}