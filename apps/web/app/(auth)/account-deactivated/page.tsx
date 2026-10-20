import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import { AuthSplitLayout } from '@/components/auth/AuthSplitLayout'

export default async function AccountDeactivatedPage() {
  const context = await resolveContext('/account-deactivated')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <AuthSplitLayout>
          <div className="text-center py-4">
            <div className="w-14 h-14 mx-auto mb-5 rounded-full bg-red-500/[0.04] border border-red-500/20 flex items-center justify-center shadow-[0_0_15px_rgba(239,68,68,0.1)]">
              <svg className="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}><path strokeLinecap="round" strokeLinejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
            </div>
            <h1 className="text-lg font-bold text-zinc-250 mb-1.5 leading-tight">Account deactivated</h1>
            <p className="text-xs text-zinc-400 mb-6 leading-relaxed">Your account has been deactivated. Please reach out to administrative support for reactivation.</p>
            <button className="w-full h-11 rounded-full bg-white text-black text-[13px] font-bold hover:bg-zinc-200 active:scale-[0.98] transition-all cursor-pointer shadow-md">
              Contact support
            </button>
          </div>
        </AuthSplitLayout>
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}

