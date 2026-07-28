// components/dlo-x/shells/AuthShell.tsx
import { ReactNode } from 'react';

interface AuthShellProps {
  children: ReactNode;
}

export function AuthShell({ children }: AuthShellProps) {
  return (
    <div className="min-h-screen bg-background">
      {children}
    </div>
  );
}
