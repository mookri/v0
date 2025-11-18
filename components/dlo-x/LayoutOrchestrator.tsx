// components/dlo-x/LayoutOrchestrator.tsx
'use server'

import { ReactNode } from 'react';
import { resolveLayoutTree } from '../../lib/dlo-x/layout-resolver';
import { RequestContext, ResolvedLayout } from '../../lib/dlo-x/types';
import { layoutRegistry } from '../../lib/dlo-x/layout-registry';
import { TelemetryService } from '../../lib/dlo-x/telemetry';
import { validateShellName, validatePolicyId, validatePath } from '../../lib/dlo-x/security';

interface LayoutOrchestratorProps {
  children: ReactNode;
  context: RequestContext;
}

export async function LayoutOrchestrator({ children, context }: LayoutOrchestratorProps) {
  try {
    // Validate context
    if (!validatePath(context.path)) {
      throw new Error(`Invalid path: ${context.path}`);
    }
    
    // Resolve the layout tree based on the context
    const resolvedLayout: ResolvedLayout = await resolveLayoutTree(context);
    
    // Validate resolved layout
    if (!validatePolicyId(resolvedLayout.policyId)) {
      throw new Error(`Invalid policy ID: ${resolvedLayout.policyId}`);
    }
    
    // Validate each shell name
    for (const shellName of resolvedLayout.shells) {
      if (!validateShellName(shellName)) {
        throw new Error(`Invalid shell name: ${shellName}`);
      }
    }
    
    // Send telemetry event
    TelemetryService.track('layout.resolve', {
      policyId: resolvedLayout.policyId,
      shells: resolvedLayout.shells,
      abTestVariant: resolvedLayout.abTestVariant,
      path: context.path,
      tenantId: context.tenantId,
      userRole: context.userRole
    });

    // Build the layout tree by nesting shells
    let layoutTree: ReactNode = children;
    
    // Wrap children with each shell in reverse order (from innermost to outermost)
    for (let i = resolvedLayout.shells.length - 1; i >= 0; i--) {
      const shellName = resolvedLayout.shells[i];
      const ShellComponent = layoutRegistry[shellName];
      
      if (ShellComponent) {
        layoutTree = <ShellComponent>{layoutTree}</ShellComponent>;
      } else {
        // Fallback to a safe minimal shell if component is not registered
        console.warn(`Shell component ${shellName} not found in registry`);
        layoutTree = <div>{layoutTree}</div>;
      }
    }

    return <>{layoutTree}</>;
  } catch (error) {
    // Send error telemetry
    TelemetryService.track('layout.resolve.error', {
      error: error instanceof Error ? error.message : 'Unknown error',
      path: context.path,
      tenantId: context.tenantId,
      userRole: context.userRole
    });
    
    // Fallback to minimal shell
    console.error('Layout orchestration error:', error);
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <div className="text-center p-8 bg-card rounded-lg border border-border max-w-md">
          <h1 className="text-2xl font-bold text-foreground mb-2">Layout Error</h1>
          <p className="text-muted-foreground mb-4">An error occurred while resolving the layout. Falling back to minimal layout.</p>
          <button
            className="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
            onClick={() => window.location.reload()}
          >
            Refresh Page
          </button>
          <div className="mt-4 text-xs text-muted-foreground">
            {children}
          </div>
        </div>
      </div>
    );
  }
}