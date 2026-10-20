import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import { AuthSplitLayout } from '@/components/auth/AuthSplitLayout'
import Link from 'next/link'

export default async function EmailVerifiedPage() {
  const context = await resolveContext('/email-verified')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <AuthSplitLayout>
          <div className="text-center py-4">
            <div className="w-14 h-14 mx-auto mb-5 rounded-full bg-emerald-500/[0.04] border border-emerald-500/20 flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.1)]">
              <svg className="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
            </div>
            <h1 className="text-lg font-bold text-zinc-250 mb-1.5 leading-tight">Email verified!</h1>
            <p className="text-xs text-zinc-400 mb-6 leading-relaxed">Your email address has been successfully verified. Full access to the platform has been activated.</p>
            <Link href="/user/overview" className="h-11 inline-flex items-center justify-center w-full rounded-full bg-white text-black text-[13px] font-bold hover:bg-zinc-200 active:scale-[0.98] transition-all cursor-pointer shadow-md">
              Go to dashboard
            </Link>
          </div>
        </AuthSplitLayout>
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}

