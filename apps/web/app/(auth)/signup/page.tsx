import { redirect } from 'next/navigation'
import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import { isFederated, safeNext } from '@/server/federation'
import SignupClient from './client'

export default async function SignupPage({ searchParams }: { searchParams: Promise<{ redirect?: string }> }) {
  // AUTH_MODE cutover (ADR-0020): there is no signup ceremony in
  // federated deployments — a verified proof auto-provisions at the
  // auth subdomain, so signup is login.
  if (isFederated()) {
    const { redirect: target } = await searchParams
    const next = safeNext(target) ?? '/'
    redirect(`/api/auth/authorize?redirect=${encodeURIComponent(next)}`)
  }
  const context = await resolveContext('/signup')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <SignupClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
