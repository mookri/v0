import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import AuthRoutePanel from '@/components/auth/AuthRoutePanel'

export default async function InviteAcceptPage() {
  const context = await resolveContext('/invite')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <AuthRoutePanel variant="invite-accept" />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
