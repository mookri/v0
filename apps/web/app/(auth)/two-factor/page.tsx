import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import TwoFactorClient from './client'

export default async function TwoFactorPage() {
  const context = await resolveContext('/two-factor')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <TwoFactorClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
