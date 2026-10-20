// components/dlo-x/shells/MinimalShell.tsx
import { ReactNode } from 'react';

interface MinimalShellProps {
  children: ReactNode;
}

export function MinimalShell({ children }: MinimalShellProps) {
  return (
    <div className="min-h-screen bg-background">
      <main>{children}</main>
    </div>
  );
}