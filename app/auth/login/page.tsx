import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import LoginClient from './client'

export default async function LoginPage() {
  // Resolve context using the server function
  const context = await resolveContext('/auth/login');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <LoginClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}