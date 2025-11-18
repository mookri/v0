# DLO-X File Templates

## Shell Component Template

```tsx
// components/dlo-x/shells/[ShellName].tsx
import { ReactNode } from 'react';

interface [ShellName]Props {
  children: ReactNode;
}

export function [ShellName]({ children }: [ShellName]Props) {
  return (
    <div className="min-h-screen bg-background">
      {/* [ShellName] layout */}
      <main className="flex-grow">
        {children}
      </main>
    </div>
  );
}
```

## Page Component Template

```tsx
// app/[page-name]/page.tsx
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator";

export default function [PageName]Page() {
  // For server components, we need to get context from headers
  // In a real implementation, this would come from middleware
  const context = {
    requestId: '[page-name]-page-request',
    tenantId: 'default' as const,
    userRole: 'User' as const,
    device: 'desktop' as const,
    seed: '[page-name]-page-seed',
    path: '/[page-name]'
  };

  return (
    <LayoutOrchestrator context={context}>
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-3xl font-bold text-foreground">[PageName] Page</h1>
        <p className="mt-4 text-muted-foreground">This is the [page-name] page using DLO-X architecture.</p>
      </div>
    </LayoutOrchestrator>
  );
}
```

## Policy Template

```ts
// lib/dlo-x/[policy-name]-policy.ts
import { PolicyBuilder } from './policy-builder';

export const [policyName]Policy = PolicyBuilder.create()
  .id('[policy-name]-policy')
  .path('/[policy-name]')
  .use('MainShell')
  .priority(10)
  .build();
```

## Layout Policy Template

```ts
// lib/dlo-x/layout-policies.ts
import { LayoutPolicy } from './types';
import { PolicyBuilder } from './policy-builder';

export const layoutPolicies: LayoutPolicy[] = [
  // Add your policies here
  PolicyBuilder.create()
    .id('example-policy')
    .path('/example')
    .use('MainShell')
    .priority(10)
    .build(),
];
```