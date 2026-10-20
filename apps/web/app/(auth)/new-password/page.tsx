import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import NewPasswordClient from './client'

export default async function NewPasswordPage() {
  const context = await resolveContext('/new-password')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <NewPasswordClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
