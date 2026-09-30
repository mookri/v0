import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import SignupClient from './client'

export default async function SignupPage() {
  const context = await resolveContext('/signup')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <SignupClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
