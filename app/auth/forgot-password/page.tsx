import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import ForgotPasswordClient from './client'

export default async function ForgotPasswordPage() {
  // Resolve context using the server function
  const context = await resolveContext('/auth/forgot-password');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ForgotPasswordClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}