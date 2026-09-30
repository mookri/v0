import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import LoginClient from './client'

export default async function LoginPage() {
  const context = await resolveContext('/login')
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <LoginClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
