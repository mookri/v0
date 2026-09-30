import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import ResetPasswordClient from './client'

export default async function ResetPasswordPage() {
  const context = await resolveContext('/reset-password')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ResetPasswordClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
