import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import PinLoginClient from './client'

export default async function PinLoginPage() {
  const context = await resolveContext('/pin-login')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <PinLoginClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
