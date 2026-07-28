import type { ReactNode } from 'react'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { resolveContext } from '@/lib/context-resolver'

interface ProductPageShellProps {
  path: string
  children: ReactNode
}

export async function ProductPageShell({ path, children }: ProductPageShellProps) {
  const context = await resolveContext(path)

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>{children}</LayoutOrchestrator>
    </ErrorBoundary>
  )
}
