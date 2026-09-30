import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import AuthRoutePanel from '@/components/auth/AuthRoutePanel'

export default async function VendorApplicationPage() {
  const context = await resolveContext('/apply/vendor')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <AuthRoutePanel variant="vendor-application" />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
