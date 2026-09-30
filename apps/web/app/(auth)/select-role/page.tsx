import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import AuthRoutePanel from '@/components/auth/AuthRoutePanel'

export default async function SelectRolePage() {
  const context = await resolveContext('/select-role')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <AuthRoutePanel variant="select-role" />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
