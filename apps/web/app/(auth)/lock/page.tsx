import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import LockScreenClient from './client'

export default async function LockScreenPage() {
  const context = await resolveContext('/lock')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <LockScreenClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
