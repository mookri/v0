import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import ForgotPasswordClient from './client'

export default async function ForgotPasswordPage() {
  const context = await resolveContext('/forgot-password')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ForgotPasswordClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
